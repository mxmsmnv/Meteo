# Meteo Documentation

Meteo is a weather integration module for ProcessWire. It returns structured data and can optionally render bundled widgets.

## Features

- Providers: Open-Meteo, OpenWeatherMap, WeatherAPI.com.
- Provider fallback chain with per-provider diagnostics.
- File cache with atomic writes and configurable TTL.
- Geocoding via Open-Meteo.
- Bundled `card`, `full`, and `minimal` widgets.
- Widget themes: `auto`, `light`, `dark`.
- One-click Material M3 demo page installer.
- ProcessWire AdminThemeUikit / `pw-design-system` friendly settings UI.

## Requirements

- PHP 8.0+
- `ext-curl`, `ext-json`
- ProcessWire 3.x

## Installation

1. Copy the `Meteo` folder to `/site/modules/`.
2. In ProcessWire admin, run **Modules → Refresh**.
3. Install and configure **Meteo**.

## Quick Start

```php
$meteo = $modules->get('Meteo');

$weather = $meteo->getWeather(40.7128, -74.0060, [
    'location_name' => 'New York',
    'timezone' => 'America/New_York',
]);

echo $weather['current']['temperature'];
```

Render a bundled widget:

```php
echo $modules->Meteo->styleTag();
echo $modules->Meteo->renderWidgetForCity('London', [
    'language' => 'en',
    'widget_theme' => 'auto',
], 'card');
```

## Source Layout

- `Meteo.module.php` — ProcessWire module entry point and public weather/geocoding API.
- `src/MeteoProvider*.php` — provider base class and provider implementations.
- `src/MeteoCacheHttp.trait.php` — HTTP requests and file cache.
- `src/MeteoRendering.trait.php` — bundled widget rendering and template resolution.
- `src/MeteoConfigInputfields.trait.php` — module settings UI.
- `src/MeteoAdminDemo.trait.php` — admin actions and demo page installer/template.
- `src/MeteoWmoDictionary.trait.php` — WMO condition labels and icon mapping.

## Configuration

All options can be set globally in **Modules → Configure → Meteo** and overridden per call.

| Option | Description | Default |
|---|---|---|
| `provider` | Primary weather API | `open_meteo` |
| `fallback_providers` | Comma-separated fallback providers | empty |
| `units` | `metric` or `imperial` | `metric` |
| `language` | ISO 639-1 code | `en` |
| `widget_theme` | Bundled widget theme: `auto`, `light`, `dark` | `auto` |
| `timezone` | IANA timezone. Blank means provider auto-detect where supported. | empty |
| `cache_time` | Cache TTL in seconds. `0` disables cache. | `1800` |
| `owm_key` | OpenWeatherMap API key | empty |
| `wapi_key` | WeatherAPI.com API key | empty |

The settings UI uses ProcessWire Inputfields, UIkit classes, scoped CSS, and `--pw-*` variables with fallbacks, so it fits AdminThemeUikit and `pw-design-system`-style skins.

## Weather Data

```php
$weather = $modules->Meteo->getWeather(48.8566, 2.3522, [
    'provider' => 'openweathermap',
    'fallback_providers' => 'weatherapi,open_meteo',
    'units' => 'imperial',
    'language' => 'fr',
    'cache_time' => 3600,
    'location_name' => 'Paris',
    'timezone' => 'Europe/Paris',
]);
```

`getWeather()` returns an array or `false` on failure.

```php
[
    'current' => [
        'temperature',
        'feels_like',
        'humidity',
        'weather_code',
        'weather_label',
        'weather_icon',
        'wind_speed',
        'wind_direction',
        'wind_direction_label',
        'pressure',
        'pressure_unit',
        'cloud_cover',
        'is_day',
        'precipitation',
        'visibility',
        'visibility_unit',
        'temp_unit',
        'wind_unit',
        'precip_unit',
    ],
    'hourly' => [],
    'daily' => [],
    'location' => ['lat', 'lon', 'name', 'timezone'],
    'units' => ['temperature', 'wind_speed', 'precip'],
    'updated_at' => 0,
    'provider' => '',
    'provider_url' => '',
]
```

Hourly slots contain `time`, `time_label`, `temperature`, `weather_code`, `weather_icon`, `weather_label`, `precip_prob`, `wind_speed`, and `is_day`.

Daily slots contain `time`, `date_label`, `date_full`, `temp_max`, `temp_min`, `weather_code`, `weather_icon`, `weather_label`, `precip_sum`, `precip_prob`, `wind_max`, `sunrise`, `sunset`, `sunrise_label`, `sunset_label`, and `uv_index`.

## Geocoding

```php
$places = $modules->Meteo->geocode('Kyiv', 'uk', 5);
```

Each result contains `name`, `country`, `country_code`, `region`, `lat`, `lon`, `timezone`, and `population`.

## Widgets

Include CSS once:

```php
echo $modules->Meteo->styleTag();
```

Render by coordinates:

```php
echo $modules->Meteo->renderWidget(40.7128, -74.0060, [
    'location_name' => 'New York',
    'timezone' => 'America/New_York',
    'widget_theme' => 'dark',
], 'card');
```

Render by city:

```php
echo $modules->Meteo->renderWidgetForCity('London', [
    'language' => 'en',
    'widget_theme' => 'auto',
], 'full');
```

Bundled templates:

| Template | Description |
|---|---|
| `card` | Compact current weather, details, hourly strip, and daily forecast |
| `full` | Expanded layout with hero, detail tiles, hourly list, and daily forecast |
| `minimal` | Inline badge with icon, temperature, and location |

Bundled templates receive `$mtTheme` and add `mt-theme-auto`, `mt-theme-light`, or `mt-theme-dark`. `auto` follows `prefers-color-scheme`.

## Custom Templates

Place custom widget templates in `/site/templates/Meteo/`.

```php
<?php namespace ProcessWire;

echo $w['current']['temperature'] . $w['current']['temp_unit'];
```

Use it by file name without extension:

```php
echo $modules->Meteo->renderWidget($lat, $lon, [], 'compact');
```

## Demo Page

In **Modules → Configure → Meteo**, use **Install Demo** to create `/meteo-demo/`. The installer creates a `meteo-demo` template and page, renders all bundled widget templates, and can remove them with **Remove Demo**.

## Providers

| Provider | API key | Hourly data |
|---|---:|---|
| Open-Meteo | No | 1-hour intervals |
| OpenWeatherMap | Yes | 3-hour intervals on the free forecast API |
| WeatherAPI.com | Yes | 1-hour intervals |

OpenWeatherMap free forecast data returns 3-hour intervals, so `hourly` contains up to 8 entries for the next 24 hours.

## Cache

Weather responses are stored as JSON files in `/site/assets/cache/Meteo/`.

Weather cache keys include coordinates, units, language, timezone, location label, and provider. Geocoding results share the same cache system with a fixed 24-hour TTL.

## Error Handling

```php
$weather = $modules->Meteo->getWeather($lat, $lon);

if ($weather === false) {
    $log->save('meteo', $modules->Meteo->getLastError());
}
```

HTTP requests retry automatically on `429` and `5xx` responses with exponential backoff.
