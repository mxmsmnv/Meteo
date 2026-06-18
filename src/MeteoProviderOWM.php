<?php namespace ProcessWire;

class MeteoProviderOWM extends MeteoProvider {

    public function fetch(float $lat, float $lon, array $opts): array|false {
        $key = $opts['owm_key'] ?? '';
        if (!$key) {
            $this->module->setLastError('OpenWeatherMap API key is missing');
            return false;
        }

        $metric = ($opts['units'] !== 'imperial');
        $lang   = $this->mapLang($opts['language'] ?? 'en');
        $units  = $metric ? 'metric' : 'imperial';

        $cur = $this->module->httpGet('https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
            'lat' => $lat, 'lon' => $lon, 'appid' => $key, 'units' => $units, 'lang' => $lang,
        ]));
        $fc = $this->module->httpGet('https://api.openweathermap.org/data/2.5/forecast?' . http_build_query([
            'lat' => $lat, 'lon' => $lon, 'appid' => $key, 'units' => $units, 'lang' => $lang, 'cnt' => 40,
        ]));

        if (!$cur || !$fc) return false;

        $c = json_decode($cur, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->module->setLastError('OpenWeatherMap current JSON decode failed: ' . json_last_error_msg());
            return false;
        }
        $f = json_decode($fc, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->module->setLastError('OpenWeatherMap forecast JSON decode failed: ' . json_last_error_msg());
            return false;
        }
        if (empty($c['main']) || empty($f['list'])) {
            $this->module->setLastError('OpenWeatherMap response missing weather data');
            return false;
        }

        $lang_orig = $opts['language'] ?? 'en';
        return $this->parse($c, $f, $lat, $lon, $opts, $metric, $lang_orig);
    }

    private function parse(array $c, array $f, float $lat, float $lon, array $opts, bool $metric, string $lang): array {
        $code = (int)($c['weather'][0]['id'] ?? 800);
        $icon = $this->owmCodeToIcon($code);
        $wmoCode = $this->owmToWmo($code);

        $current = [
            'temperature'         => round($c['main']['temp'] ?? 0, 1),
            'feels_like'          => round($c['main']['feels_like'] ?? 0, 1),
            'humidity'            => (int)($c['main']['humidity'] ?? 0),
            'weather_code'        => $wmoCode,
            'weather_label'       => ucfirst($c['weather'][0]['description'] ?? self::wmoLabel($wmoCode, $lang)),
            'weather_icon'        => $icon,
            'wind_speed'          => round(($c['wind']['speed'] ?? 0) * ($metric ? 3.6 : 1), 1),
            'wind_direction'      => (int)($c['wind']['deg'] ?? 0),
            'wind_direction_label'=> self::windDir($c['wind']['deg'] ?? 0),
            'pressure'            => (int)($c['main']['pressure'] ?? 0),
            'pressure_unit'       => 'hPa',
            'cloud_cover'         => (int)($c['clouds']['all'] ?? 0),
            'is_day'              => time() > ($c['sys']['sunrise'] ?? 0) && time() < ($c['sys']['sunset'] ?? 0),
            'precipitation'       => (function() use ($c, $metric): float {
                $mm = (float)($c['rain']['1h'] ?? $c['snow']['1h'] ?? 0);
                return round($metric ? $mm : $mm / 25.4, 2);
            })(),
            'visibility'          => isset($c['visibility']) ? round($c['visibility'] / ($metric ? 1000 : 1609.34), 1) : null,
            'visibility_unit'     => $metric ? 'km' : 'mi',
            'temp_unit'           => $metric ? '°C' : '°F',
            'wind_unit'           => $metric ? 'km/h' : 'mph',
            'precip_unit'         => $metric ? 'mm' : 'in',
        ];

        $now = time();
        $utcOffset = (int)($c['timezone'] ?? 0);
        $hourlyOut = [];
        foreach ($f['list'] as $h) {
            if ((int)$h['dt'] < $now) continue;
            if (count($hourlyOut) >= 8) break;
            $hc = (int)($h['weather'][0]['id'] ?? 800);
            $hourlyOut[] = [
                'time'        => (int)$h['dt'],
                'time_label'  => gmdate('H:i', $h['dt'] + $utcOffset),
                'temperature' => round($h['main']['temp'] ?? 0, 1),
                'weather_code'=> $this->owmToWmo($hc),
                'weather_icon'=> $this->owmCodeToIcon($hc),
                'weather_label'=> ucfirst($h['weather'][0]['description'] ?? ''),
                'precip_prob' => (int)(($h['pop'] ?? 0) * 100),
                'wind_speed'  => round(($h['wind']['speed'] ?? 0) * ($metric ? 3.6 : 1), 1),
                'is_day'      => str_ends_with($h['weather'][0]['icon'] ?? '01d', 'd'),
            ];
        }

        $sysSunrise = (int)($c['sys']['sunrise'] ?? 0);
        $sysSunset  = (int)($c['sys']['sunset'] ?? 0);
        $byDay = [];
        foreach ($f['list'] as $h) {
            $day = gmdate('Y-m-d', $h['dt'] + $utcOffset);
            $byDay[$day][] = $h;
        }
        $dailyOut = [];
        $isFirstDay = true;
        foreach ($byDay as $day => $hours) {
            $temps = array_filter(array_column(array_column($hours, 'main'), 'temp'), fn($v) => $v !== null);
            if (empty($temps)) { $isFirstDay = false; continue; }
            $ts      = gmmktime(0, 0, 0, (int)substr($day, 5, 2), (int)substr($day, 8, 2), (int)substr($day, 0, 4));
            $topHour = $hours[0];
            $hc      = (int)($topHour['weather'][0]['id'] ?? 800);
            $probs   = array_map(fn($h) => (int)(($h['pop'] ?? 0) * 100), $hours);
            $sunrise = $isFirstDay && $sysSunrise ? $sysSunrise : null;
            $sunset  = $isFirstDay && $sysSunset  ? $sysSunset  : null;

            // Calculate daily precip sum from 3-hour blocks
            $precipSum = 0.0;
            foreach ($hours as $h) {
                $precipSum += (float)($h['rain']['3h'] ?? 0) + (float)($h['snow']['3h'] ?? 0);
            }

            // Gather wind speeds
            $windSpeeds = array_filter(array_column(array_column($hours, 'wind'), 'speed'), fn($v) => $v !== null);
            $windMax = empty($windSpeeds) ? 0.0 : round(max($windSpeeds) * ($metric ? 3.6 : 1), 1);

            $dailyOut[] = [
                'time'         => $ts,
                'date_label'   => gmdate('D', $ts),
                'date_full'    => gmdate('D, M j', $ts),
                'temp_max'     => round(max($temps), 1),
                'temp_min'     => round(min($temps), 1),
                'weather_code' => $this->owmToWmo($hc),
                'weather_icon' => $this->owmCodeToIcon($hc),
                'weather_label'=> ucfirst($topHour['weather'][0]['description'] ?? ''),
                'precip_sum'   => round($precipSum, 1),
                'precip_prob'  => max($probs),
                'wind_max'     => $windMax,
                'sunrise'      => $sunrise,
                'sunset'       => $sunset,
                'sunrise_label'=> $sunrise ? gmdate('H:i', $sunrise + $utcOffset) : null,
                'sunset_label' => $sunset  ? gmdate('H:i', $sunset  + $utcOffset) : null,
                'uv_index'     => 0,
            ];
            $isFirstDay = false;
        }

        $tzAbs = abs($utcOffset);
        $tzStr = sprintf('UTC%s%02d:%02d', $utcOffset >= 0 ? '+' : '-', intdiv($tzAbs, 3600), ($tzAbs % 3600) / 60);

        return [
            'location'   => ['lat' => $lat, 'lon' => $lon, 'name' => $opts['location_name'] ?? ($c['name'] ?? ''), 'timezone' => $tzStr],
            'current'    => $current,
            'hourly'     => $hourlyOut,
            'daily'      => array_slice($dailyOut, 0, 7),
            'units'      => ['temperature' => $metric ? '°C' : '°F', 'wind_speed' => $metric ? 'km/h' : 'mph', 'precip' => $metric ? 'mm' : 'in'],
            'updated_at' => time(),
            'provider'   => 'OpenWeatherMap',
            'provider_url' => 'https://openweathermap.org',
        ];
    }

    private function mapLang(string $lang): string {
        return match($lang) { 'uk' => 'ua', 'zh' => 'zh_cn', default => $lang };
    }

    private function owmCodeToIcon(int $code): string {
        if ($code >= 200 && $code < 300) return 'thunderstorm';
        if ($code >= 300 && $code < 400) return 'drizzle';
        if ($code >= 500 && $code < 502) return 'rain';
        if ($code >= 502 && $code < 520) return 'rain-heavy';
        if ($code >= 520 && $code < 600) return 'showers';
        if ($code >= 600 && $code < 700) return 'snow';
        if ($code >= 700 && $code < 800) return 'fog';
        if ($code === 800) return 'clear';
        if ($code === 801) return 'mostly-clear';
        if ($code === 802) return 'partly-cloudy';
        if ($code >= 803) return 'overcast';
        return 'unknown';
    }

    private function owmToWmo(int $owm): int {
        if ($owm === 800) return 0;
        if ($owm === 801) return 1;
        if ($owm === 802) return 2;
        if ($owm >= 803) return 3;
        if ($owm >= 700) return 45;
        if ($owm >= 600) return 71;
        if ($owm >= 521) return 81;
        if ($owm === 520) return 80;
        if ($owm >= 502) return 65;
        if ($owm >= 501) return 63;
        if ($owm >= 500) return 61;
        if ($owm >= 300) return 53;
        if ($owm >= 200) return 95;
        return 0;
    }
}
