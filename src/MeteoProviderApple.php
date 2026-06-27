<?php namespace ProcessWire;

class MeteoProviderApple extends MeteoProvider {

    public function fetch(float $lat, float $lon, array $opts): array|false {
        $teamId = trim((string)($opts['apple_team_id'] ?? ''));
        $serviceId = trim((string)($opts['apple_service_id'] ?? ''));
        $keyId = trim((string)($opts['apple_key_id'] ?? ''));
        $privateKeyPath = trim((string)($opts['apple_private_key_path'] ?? ''));

        if ($teamId === '' || $serviceId === '' || $keyId === '' || $privateKeyPath === '') {
            $this->module->setLastError('Apple WeatherKit credentials are not fully configured.');
            return false;
        }

        $modules = $this->module->wire('modules');
        $tokenForge = is_object($modules) ? $modules->get('TokenForge') : null;
        if (!is_object($tokenForge) || !method_exists($tokenForge, 'createCachedJwt')) {
            $this->module->setLastError('Apple WeatherKit requires TokenForge module.');
            return false;
        }

        $language = $this->resolveLanguage((string)($opts['language'] ?? 'en_US'));
        $dataSets = $this->resolveDataSets((string)($opts['apple_datasets'] ?? ''));

        $cacheKey = 'meteo_apple_weatherkit_' . sha1($teamId . '|' . $serviceId . '|' . $keyId . '|' . $privateKeyPath . '|' . $dataSets);
        try {
            $jwt = $tokenForge->createCachedJwt($cacheKey, [
                'ttl' => (int)($opts['apple_jwt_cache_ttl'] ?? 3300),
                'algorithm' => 'ES256',
                'key_id' => $keyId,
                'private_key_path' => $privateKeyPath,
                'headers' => [
                    'id' => $teamId . '.' . $serviceId,
                ],
                'payload' => [
                    'iss' => $teamId,
                    'iat' => time(),
                    'exp' => time() + 3600,
                    'sub' => $serviceId,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->module->setLastError('Apple WeatherKit JWT generation failed.');
            return false;
        }

        $url = 'https://weatherkit.apple.com/api/v1/weather/' . rawurlencode($language) . '/' . $lat . '/' . $lon;
        $url .= '?' . http_build_query(['dataSets' => $dataSets], '', '&', PHP_QUERY_RFC3986);

        $raw = $this->module->httpGet($url, 2, [
            'Authorization: Bearer ' . $jwt,
            'Accept: application/json',
        ]);
        if (!$raw) return false;

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->module->setLastError('Apple WeatherKit JSON decode failed: ' . json_last_error_msg());
            return false;
        }

        if (empty($data['currentWeather'])) {
            $this->module->setLastError('Apple WeatherKit response is missing current weather data.');
            return false;
        }

        return $this->parse($data, $lat, $lon, $opts);
    }

    private function parse(array $data, float $lat, float $lon, array $opts): array {
        $metric = ($opts['units'] !== 'imperial');
        $lang = $opts['language'] ?? 'en';
        $timezone = $this->normalizeTimezone($opts['timezone'] ?? null);

        $currentWeather = $data['currentWeather'] ?? [];
        $currentCode = (string)($currentWeather['conditionCode'] ?? '');
        $currentWmo = $this->appleConditionToWmo($currentCode);
        $current = [
            'temperature'         => $this->convertTemperature((float)$this->extractValue($currentWeather, ['temperature'], 0.0), $metric),
            'feels_like'          => $this->convertTemperature((float)$this->extractValue($currentWeather, ['temperatureApparent'], 0.0), $metric),
            'humidity'            => $this->normalizePercent((float)$this->extractValue($currentWeather, ['humidity'], 0.0)),
            'weather_code'        => $currentWmo,
            'weather_label'       => self::wmoLabel($currentWmo, $lang),
            'weather_icon'        => self::wmoIcon($currentWmo),
            'wind_speed'          => $this->convertWindSpeed((float)$this->extractValue($currentWeather, ['windSpeed'], 0.0), $metric),
            'wind_direction'      => (int)$this->extractValue($currentWeather, ['windDirection'], 0),
            'wind_direction_label'=> self::windDir((int)$this->extractValue($currentWeather, ['windDirection'], 0)),
            'pressure'            => (float)$this->extractValue($currentWeather, ['pressure'], 0),
            'pressure_unit'       => 'hPa',
            'cloud_cover'         => $this->normalizePercent((float)$this->extractValue($currentWeather, ['cloudCover'], 0.0)),
            'is_day'              => (bool)$this->extractValue($currentWeather, ['isDaytime'], true),
            'precipitation'       => $this->convertPrecipitation((float)$this->extractValue($currentWeather, ['precipitationIntensity'], 0.0), $metric),
            'visibility'          => $this->convertVisibility((float)$this->extractValue($currentWeather, ['visibility'], 0.0), $metric),
            'visibility_unit'     => $metric ? 'km' : 'mi',
            'temp_unit'           => $metric ? '°C' : '°F',
            'wind_unit'           => $metric ? 'km/h' : 'mph',
            'precip_unit'         => $metric ? 'mm' : 'in',
        ];

        $hourly = [];
        $hours = (array)($data['forecastHourly']['hours'] ?? []);
        $now = time();
        foreach ($hours as $h) {
            if (!is_array($h)) continue;
            $ts = $this->extractTimestamp($h['forecastStart'] ?? null);
            if ($ts === null || $ts < $now) continue;
            if (count($hourly) >= 24) break;

            $conditionCode = (string)($h['conditionCode'] ?? '');
            $hourlyWmo = $this->appleConditionToWmo($conditionCode);
            $hourly[] = [
                'time'         => $ts,
                'time_label'   => $this->formatLocalTime($ts, $timezone, 'H:i'),
                'temperature'  => $this->convertTemperature((float)$this->extractValue($h, ['temperature'], 0.0), $metric),
                'weather_code' => $hourlyWmo,
                'weather_icon' => self::wmoIcon($hourlyWmo),
                'weather_label'=> self::wmoLabel($hourlyWmo, $lang),
                'precip_prob'  => (int)$this->normalizePercent((float)$this->extractValue($h, ['precipitationChance'], 0.0)),
                'wind_speed'   => $this->convertWindSpeed((float)$this->extractValue($h, ['windSpeed'], 0.0), $metric),
                'is_day'       => (bool)$this->extractValue($h, ['isDaytime'], true),
            ];
        }

        $daily = [];
        $days = (array)($data['forecastDaily']['days'] ?? []);
        foreach ($days as $d) {
            if (!is_array($d)) continue;
            $ts = $this->extractTimestamp($d['forecastStart'] ?? null);
            if ($ts === null) continue;

            $conditionCode = (string)$this->firstValue($d, [
                ['conditionCode'],
                ['daytimeForecast', 'conditionCode'],
                ['dayForecast', 'conditionCode'],
            ], '');
            $dailyWmo = $this->appleConditionToWmo($conditionCode);
            $daily[] = [
                'time'         => $ts,
                'date_label'   => $this->formatLocalTime($ts, $timezone, 'D'),
                'date_full'    => $this->formatLocalTime($ts, $timezone, 'D, M j'),
                'temp_max'     => $this->convertTemperature((float)$this->extractValue($d, ['temperatureMax'], 0.0), $metric),
                'temp_min'     => $this->convertTemperature((float)$this->extractValue($d, ['temperatureMin'], 0.0), $metric),
                'weather_code' => $dailyWmo,
                'weather_icon' => self::wmoIcon($dailyWmo),
                'weather_label'=> self::wmoLabel($dailyWmo, $lang),
                'precip_sum'   => $this->convertPrecipitation((float)$this->extractValue($d, ['precipitationAmount'], 0.0), $metric),
                'precip_prob'  => (int)$this->normalizePercent((float)$this->extractValue($d, ['precipitationChance'], 0.0)),
                'wind_max'     => $this->convertWindSpeed((float)$this->firstValue($d, [
                    ['windSpeed'],
                    ['daytimeForecast', 'windSpeed'],
                    ['dayForecast', 'windSpeed'],
                ], 0.0), $metric),
                'sunrise'      => $this->extractTimestamp($d['sunrise'] ?? null),
                'sunset'       => $this->extractTimestamp($d['sunset'] ?? null),
                'sunrise_label'=> $this->resolveSunTimeLabel($d, 'sunrise', $timezone),
                'sunset_label' => $this->resolveSunTimeLabel($d, 'sunset', $timezone),
                'uv_index'     => round((float)$this->extractValue($d, ['uvIndex'], 0), 1),
            ];
        }

        $result = [
            'location'    => [
                'lat' => $lat,
                'lon' => $lon,
                'name' => $opts['location_name'] ?? '',
                'timezone' => $timezone,
            ],
            'current'     => $current,
            'hourly'      => array_slice($hourly, 0, 24),
            'daily'       => array_slice($daily, 0, 7),
            'units'       => ['temperature' => $metric ? '°C' : '°F', 'wind_speed' => $metric ? 'km/h' : 'mph', 'precip' => $metric ? 'mm' : 'in'],
            'updated_at'  => time(),
            'provider'    => 'Apple Weather',
            'provider_url'=> 'https://developer.apple.com/weatherkit/',
            'attribution' => 'Weather',
        ];

        if (!empty($opts['apple_debug']) || !empty($opts['debug'])) {
            $result['raw'] = $data;
        }

        return $result;
    }

    private function extractTimestamp(mixed $value): ?int {
        if (is_int($value)) return $value;
        if (is_numeric($value)) return (int)$value;
        if (!is_string($value)) return null;
        $ts = strtotime($value);
        return $ts === false ? null : $ts;
    }

    private function extractValue(array $node, array $path, mixed $default = 0): mixed {
        $current = $node;

        foreach ($path as $segment) {
            if (!is_array($current) || !is_string($segment) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        if (is_array($current) && array_key_exists('value', $current)) {
            $current = $current['value'];
        }

        return is_numeric($current) ? (float)$current : $current;
    }

    private function firstValue(array $node, array $paths, mixed $default = 0): mixed {
        foreach ($paths as $path) {
            if (!is_array($path)) continue;
            $value = $this->extractValue($node, $path, null);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return $default;
    }

    private function normalizePercent(float $value): int {
        if ($value <= 1.0) {
            $value *= 100;
        }
        return (int)round(max(0.0, min(100.0, $value)));
    }

    private function convertTemperature(float $valueC, bool $metric): float {
        return $metric ? round($valueC, 1) : round(($valueC * 9 / 5) + 32, 1);
    }

    private function convertWindSpeed(float $valueMetersPerSecond, bool $metric): float {
        return $metric ? round($valueMetersPerSecond * 3.6, 1) : round($valueMetersPerSecond * 2.23694, 1);
    }

    private function convertVisibility(float $valueMeters, bool $metric): ?float {
        return $valueMeters > 0 ? round($metric ? $valueMeters / 1000 : $valueMeters / 1609.34, 1) : null;
    }

    private function convertPrecipitation(float $value, bool $metric): float {
        return $metric ? round($value, 2) : round($value / 25.4, 2);
    }

    private function normalizeTimezone(?string $timezone): string {
        $timezone = trim((string)$timezone);
        if ($timezone === '') return 'UTC';
        try {
            new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            return 'UTC';
        }
        return $timezone;
    }

    private function formatLocalTime(int $timestamp, string $timezone, string $format): string {
        try {
            $tz = new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }
        return (new \DateTimeImmutable("@{$timestamp}"))->setTimezone($tz)->format($format);
    }

    private function resolveSunTimeLabel(array $day, string $field, string $timezone): ?string {
        $value = $day[$field] ?? null;
        $ts = $this->extractTimestamp($value);
        if ($ts === null) return null;
        return $this->formatLocalTime($ts, $timezone, 'H:i');
    }

    private function resolveLanguage(string $language): string {
        $language = str_replace('-', '_', strtolower(trim($language)));
        $map = [
            'en' => 'en_US',
            'ru' => 'ru_RU',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            'pt' => 'pt_PT',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'zh' => 'zh_CN',
            'ko' => 'ko_KR',
            'nl' => 'nl_NL',
            'pl' => 'pl_PL',
            'uk' => 'uk_UA',
            'tr' => 'tr_TR',
            'ar' => 'ar_SA',
        ];
        if (isset($map[$language])) {
            return $map[$language];
        }
        if (preg_match('/^[a-z]{2}$/', $language)) {
            return $language . '_' . strtoupper($language);
        }
        if (preg_match('/^[a-z]{2}_[a-z]{2}$/', $language)) {
            return $language[0] . $language[1] . '_' . strtoupper(substr($language, 3, 2));
        }
        if (preg_match('/^[a-z]{2}_[a-z]{1}$/', $language)) {
            return substr($language, 0, 2) . '_' . strtoupper(substr($language, 3, 1));
        }
        return 'en_US';
    }

    private function resolveDataSets(string $dataSets): string {
        $allowed = [
            'currentWeather',
            'forecastDaily',
            'forecastHourly',
            'forecastNextHour',
            'weatherAlerts',
        ];
        $requested = array_filter(array_map('trim', explode(',', $dataSets)));
        $requested = array_values(array_unique(array_intersect($requested, $allowed)));
        if (!$requested) {
            $requested = ['currentWeather', 'forecastDaily', 'forecastHourly'];
        }
        return implode(',', $requested);
    }

    private function appleConditionToWmo(string $code): int {
        return match($code) {
            'clear', 'sunny' => 0,
            'mostlyClear', 'mostly_clear' => 1,
            'partlyCloudy', 'partly_cloudy' => 2,
            'cloudy', 'haze', 'smoke', 'dust' => 3,
            'fog' => 45,
            'drizzle', 'lightRain', 'rain' => 61,
            'heavyRain' => 63,
            'snow', 'flake', 'flurries' => 71,
            'sleet', 'snowShower', 'freezingRain' => 73,
            'hail' => 82,
            'thunderstorms' => 95,
            default => 2,
        };
    }
}
