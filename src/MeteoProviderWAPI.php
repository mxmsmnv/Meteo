<?php namespace ProcessWire;

class MeteoProviderWAPI extends MeteoProvider {

    public function fetch(float $lat, float $lon, array $opts): array|false {
        $key  = $opts['wapi_key'] ?? '';
        if (!$key) {
            $this->module->setLastError('WeatherAPI.com API key is missing');
            return false;
        }

        $lang = $opts['language'] ?? 'en';
        $raw  = $this->module->httpGet('https://api.weatherapi.com/v1/forecast.json?' . http_build_query([
            'key'  => $key,
            'q'    => "$lat,$lon",
            'days' => 7,
            'aqi'  => 'no',
            'lang' => $lang,
        ]));

        if (!$raw) return false;
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->module->setLastError('WeatherAPI.com JSON decode failed: ' . json_last_error_msg());
            return false;
        }
        if (empty($data['current'])) {
            $this->module->setLastError('WeatherAPI.com response missing current weather data');
            return false;
        }

        return $this->parse($data, $lat, $lon, $opts);
    }

    private function parse(array $data, float $lat, float $lon, array $opts): array {
        $metric  = ($opts['units'] !== 'imperial');
        $lang    = $opts['language'] ?? 'en';
        $c       = $data['current'];
        $isDay   = (bool)$c['is_day'];
        $code    = (int)($c['condition']['code'] ?? 1000);
        $wmoCode = $this->wapiToWmo($code);

        $current = [
            'temperature'         => round($metric ? $c['temp_c'] : $c['temp_f'], 1),
            'feels_like'          => round($metric ? $c['feelslike_c'] : $c['feelslike_f'], 1),
            'humidity'            => (int)$c['humidity'],
            'weather_code'        => $wmoCode,
            'weather_label'       => $c['condition']['text'] ?? self::wmoLabel($wmoCode, $lang),
            'weather_icon'        => self::wmoIcon($wmoCode),
            'wind_speed'          => round($metric ? $c['wind_kph'] : $c['wind_mph'], 1),
            'wind_direction'      => (int)$c['wind_degree'],
            'wind_direction_label'=> $c['wind_dir'] ?? self::windDir($c['wind_degree']),
            'pressure'            => $metric ? (int)($c['pressure_mb'] ?? 0) : round((float)($c['pressure_in'] ?? 0), 2),
            'pressure_unit'       => $metric ? 'hPa' : 'inHg',
            'cloud_cover'         => (int)$c['cloud'],
            'is_day'              => $isDay,
            'precipitation'       => round($metric ? ($c['precip_mm'] ?? 0) : ($c['precip_in'] ?? 0), 1),
            'visibility'          => round($metric ? ($c['vis_km'] ?? 0) : ($c['vis_miles'] ?? 0), 1),
            'visibility_unit'     => $metric ? 'km' : 'mi',
            'temp_unit'           => $metric ? '°C' : '°F',
            'wind_unit'           => $metric ? 'km/h' : 'mph',
            'precip_unit'         => $metric ? 'mm' : 'in',
        ];

        $hourlyOut = [];
        $localNow = (int)($data['location']['localtime_epoch'] ?? time());
        foreach (($data['forecast']['forecastday'] ?? []) as $day) {
            foreach (($day['hour'] ?? []) as $h) {
                if ((int)$h['time_epoch'] < $localNow) continue;
                if (count($hourlyOut) >= 24) break 2;
                $hc = (int)($h['condition']['code'] ?? 1000);
                $hourlyOut[] = [
                    'time'         => (int)$h['time_epoch'],
                    'time_label'   => substr($h['time'] ?? '', 11, 5),
                    'temperature'  => round($metric ? $h['temp_c'] : $h['temp_f'], 1),
                    'weather_code' => $this->wapiToWmo($hc),
                    'weather_icon' => self::wmoIcon($this->wapiToWmo($hc)),
                    'weather_label'=> $h['condition']['text'] ?? '',
                    'precip_prob'  => max((int)($h['chance_of_rain'] ?? 0), (int)($h['chance_of_snow'] ?? 0)),
                    'wind_speed'   => round($metric ? $h['wind_kph'] : $h['wind_mph'], 1),
                    'is_day'       => (bool)$h['is_day'],
                ];
            }
        }

        $tzId = $data['location']['tz_id'] ?? 'UTC';

        $dailyOut = [];
        foreach (($data['forecast']['forecastday'] ?? []) as $d) {
            $dc = (int)($d['day']['condition']['code'] ?? 1000);
            $srStr = $d['astro']['sunrise'] ?? '';
            $ssStr = $d['astro']['sunset']  ?? '';

            $sunriseTime = $this->parseLocalTime($d['date'], $srStr, $tzId);
            $sunsetTime  = $this->parseLocalTime($d['date'], $ssStr, $tzId);

            $dailyOut[] = [
                'time'         => (int)$d['date_epoch'],
                'date_label'   => $this->formatLocalDate($d['date'], $tzId, 'D'),
                'date_full'    => $this->formatLocalDate($d['date'], $tzId, 'D, M j'),
                'temp_max'     => round($metric ? $d['day']['maxtemp_c'] : $d['day']['maxtemp_f'], 1),
                'temp_min'     => round($metric ? $d['day']['mintemp_c'] : $d['day']['mintemp_f'], 1),
                'weather_code' => $this->wapiToWmo($dc),
                'weather_icon' => self::wmoIcon($this->wapiToWmo($dc)),
                'weather_label'=> $d['day']['condition']['text'] ?? '',
                'precip_sum'   => round($metric ? ($d['day']['totalprecip_mm'] ?? 0) : ($d['day']['totalprecip_in'] ?? 0), 1),
                'precip_prob'  => max((int)($d['day']['daily_chance_of_rain'] ?? 0), (int)($d['day']['daily_chance_of_snow'] ?? 0)),
                'wind_max'     => round($metric ? $d['day']['maxwind_kph'] : $d['day']['maxwind_mph'], 1),
                'sunrise'      => $sunriseTime,
                'sunset'       => $sunsetTime,
                'sunrise_label'=> $sunriseTime ? $this->formatLocalTimestamp($sunriseTime, $tzId, 'H:i') : null,
                'sunset_label' => $sunsetTime  ? $this->formatLocalTimestamp($sunsetTime, $tzId, 'H:i')  : null,
                'uv_index'     => round($d['day']['uv'] ?? 0, 1),
            ];
        }

        return [
            'location'    => ['lat' => $lat, 'lon' => $lon, 'name' => $opts['location_name'] ?? (($data['location']['name'] ?? '') . ', ' . ($data['location']['country'] ?? '')), 'timezone' => $data['location']['tz_id'] ?? 'UTC'],
            'current'     => $current,
            'hourly'      => array_slice($hourlyOut, 0, 24),
            'daily'       => $dailyOut,
            'units'       => ['temperature' => $metric ? '°C' : '°F', 'wind_speed' => $metric ? 'km/h' : 'mph', 'precip' => $metric ? 'mm' : 'in'],
            'updated_at'  => time(),
            'provider'    => 'WeatherAPI.com',
            'provider_url'=> 'https://weatherapi.com',
        ];
    }

    /**
     * Parse sunrise/sunset time string in local timezone → Unix timestamp.
     * Uses DateTimeZone for reliable conversion regardless of server locale.
     */
    private function parseLocalTime(string $date, string $timeStr, string $tzId): ?int {
        if ($timeStr === '') return null;

        try {
            $tz = new \DateTimeZone($tzId);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }

        $parsed = \DateTime::createFromFormat('Y-m-d h:i A', $date . ' ' . $timeStr, $tz)
               ?: \DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $timeStr, $tz);

        return $parsed ? (int)$parsed->format('U') : null;
    }

    private function formatLocalTimestamp(int $timestamp, string $tzId, string $format): string {
        try {
            $tz = new \DateTimeZone($tzId);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone($tz)->format($format);
    }

    private function formatLocalDate(string $date, string $tzId, string $format): string {
        try {
            $tz = new \DateTimeZone($tzId);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date, $tz);
        return $parsed ? $parsed->format($format) : gmdate($format, strtotime($date) ?: time());
    }

    private function wapiToWmo(int $code): int {
        return match($code) {
            1000 => 0, 1003 => 2, 1006 => 3, 1009 => 3,
            1030, 1135, 1147 => 45,
            1063, 1180, 1183 => 61, 1186, 1189 => 63, 1192, 1195 => 65,
            1198, 1201 => 67,
            1204, 1207 => 68,
            1066, 1210, 1213 => 71, 1216, 1219 => 73, 1222, 1225 => 75,
            1069, 1072, 1168, 1171 => 53,
            1087, 1273, 1276, 1279, 1282 => 95,
            1114, 1117, 1237, 1261, 1264 => 77,
            1150, 1153 => 51, 1240, 1243 => 80, 1246 => 82,
            1249, 1252 => 85, 1255, 1258 => 86,
            default => 2,
        };
    }
}
