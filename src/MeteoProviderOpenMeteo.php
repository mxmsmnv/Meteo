<?php namespace ProcessWire;

class MeteoProviderOpenMeteo extends MeteoProvider {

    public function fetch(float $lat, float $lon, array $opts): array|false {
        $metric = ($opts['units'] !== 'imperial');

        $params = [
            'latitude'           => $lat,
            'longitude'          => $lon,
            'current'            => 'temperature_2m,apparent_temperature,relative_humidity_2m,weather_code,wind_speed_10m,wind_direction_10m,surface_pressure,cloud_cover,is_day,precipitation,visibility',
            'hourly'             => 'temperature_2m,weather_code,precipitation_probability,wind_speed_10m,is_day',
            'daily'              => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,sunrise,sunset,uv_index_max',
            'forecast_days'      => 7,
            'temperature_unit'   => $metric ? 'celsius' : 'fahrenheit',
            'wind_speed_unit'    => $metric ? 'kmh' : 'mph',
            'precipitation_unit' => $metric ? 'mm' : 'inch',
            'timezone'           => $opts['timezone'] ?: 'auto',
            'timeformat'         => 'unixtime',
        ];

        $raw = $this->module->httpGet('https://api.open-meteo.com/v1/forecast?' . http_build_query($params));
        if (!$raw) return false;

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->module->setLastError('Open-Meteo JSON decode failed: ' . json_last_error_msg());
            return false;
        }
        if (empty($data) || !empty($data['error'])) {
            $this->module->setLastError('Open-Meteo error: ' . ($data['reason'] ?? 'empty response'));
            return false;
        }

        return $this->parse($data, $lat, $lon, $opts);
    }

    private function parse(array $data, float $lat, float $lon, array $opts): array {
        $lang      = $opts['language'] ?? 'en';
        $metric    = ($opts['units'] !== 'imperial');
        $c         = $data['current'] ?? [];
        $hourly    = $data['hourly'] ?? [];
        $daily     = $data['daily'] ?? [];
        $code      = (int)($c['weather_code'] ?? 0);
        $utcOffset = (int)($data['utc_offset_seconds'] ?? 0);

        $current = [
            'temperature'         => round($c['temperature_2m'] ?? 0, 1),
            'feels_like'          => round($c['apparent_temperature'] ?? 0, 1),
            'humidity'            => (int)($c['relative_humidity_2m'] ?? 0),
            'weather_code'        => $code,
            'weather_label'       => self::wmoLabel($code, $lang),
            'weather_icon'        => self::wmoIcon($code),
            'wind_speed'          => round($c['wind_speed_10m'] ?? 0, 1),
            'wind_direction'      => (int)($c['wind_direction_10m'] ?? 0),
            'wind_direction_label'=> self::windDir($c['wind_direction_10m'] ?? 0),
            'pressure'            => round($c['surface_pressure'] ?? 0),
            'pressure_unit'       => 'hPa',
            'cloud_cover'         => (int)($c['cloud_cover'] ?? 0),
            'is_day'              => (bool)($c['is_day'] ?? 1),
            'precipitation'       => round($c['precipitation'] ?? 0, 1),
            'visibility'          => isset($c['visibility']) ? round($c['visibility'] / ($metric ? 1000 : 1609.34), 1) : null,
            'visibility_unit'     => $metric ? 'km' : 'mi',
            'temp_unit'           => $metric ? '°C' : '°F',
            'wind_unit'           => $metric ? 'km/h' : 'mph',
            'precip_unit'         => $metric ? 'mm' : 'in',
        ];

        $now = time();
        $hourlyOut = [];
        foreach (($hourly['time'] ?? []) as $i => $ts) {
            if ($ts < $now) continue;
            if (count($hourlyOut) >= 24) break;
            $hc = (int)($hourly['weather_code'][$i] ?? 0);
            $hourlyOut[] = [
                'time'         => $ts,
                'time_label'   => gmdate('H:i', $ts + $utcOffset),
                'temperature'  => round($hourly['temperature_2m'][$i] ?? 0, 1),
                'weather_code' => $hc,
                'weather_icon' => self::wmoIcon($hc),
                'weather_label'=> self::wmoLabel($hc, $lang),
                'precip_prob'  => (int)($hourly['precipitation_probability'][$i] ?? 0),
                'wind_speed'   => round($hourly['wind_speed_10m'][$i] ?? 0, 1),
                'is_day'       => (bool)($hourly['is_day'][$i] ?? 1),
            ];
        }

        $dailyOut = [];
        foreach (($daily['time'] ?? []) as $i => $ts) {
            $dc = (int)($daily['weather_code'][$i] ?? 0);
            $dailyOut[] = [
                'time'         => $ts,
                'date_label'   => gmdate('D', $ts + $utcOffset),
                'date_full'    => gmdate('D, M j', $ts + $utcOffset),
                'temp_max'     => round($daily['temperature_2m_max'][$i] ?? 0, 1),
                'temp_min'     => round($daily['temperature_2m_min'][$i] ?? 0, 1),
                'weather_code' => $dc,
                'weather_icon' => self::wmoIcon($dc),
                'weather_label'=> self::wmoLabel($dc, $lang),
                'precip_sum'   => round($daily['precipitation_sum'][$i] ?? 0, 1),
                'precip_prob'  => (int)($daily['precipitation_probability_max'][$i] ?? 0),
                'wind_max'     => round($daily['wind_speed_10m_max'][$i] ?? 0, 1),
                'sunrise'      => $daily['sunrise'][$i] ?? null,
                'sunset'       => $daily['sunset'][$i] ?? null,
                'sunrise_label'=> isset($daily['sunrise'][$i]) ? gmdate('H:i', $daily['sunrise'][$i] + $utcOffset) : null,
                'sunset_label' => isset($daily['sunset'][$i]) ? gmdate('H:i', $daily['sunset'][$i] + $utcOffset) : null,
                'uv_index'     => round($daily['uv_index_max'][$i] ?? 0, 1),
            ];
        }

        return [
            'location'    => ['lat' => $lat, 'lon' => $lon, 'name' => $opts['location_name'] ?? '', 'timezone' => $data['timezone'] ?? 'UTC'],
            'current'     => $current,
            'hourly'      => $hourlyOut,
            'daily'       => $dailyOut,
            'units'       => ['temperature' => $metric ? '°C' : '°F', 'wind_speed' => $metric ? 'km/h' : 'mph', 'precip' => $metric ? 'mm' : 'in'],
            'updated_at'  => time(),
            'provider'    => 'Open-Meteo',
            'provider_url'=> 'https://open-meteo.com',
        ];
    }
}
