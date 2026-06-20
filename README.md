# Meteo

Weather module for [ProcessWire CMS](https://processwire.com/). Meteo gets current weather and forecast data by coordinates or city name, normalizes provider responses into one array format, caches API responses, and can render ready-made weather widgets.

![Meteo](assets/Meteo.png)

## Features

- Weather providers: Open-Meteo, OpenWeatherMap, WeatherAPI.com.
- Provider fallback chain if the primary provider fails.
- Current, hourly, and daily forecast data.
- City geocoding.
- File cache with configurable TTL.
- Bundled widgets: `card`, `full`, `minimal`.
- Widget themes: `auto`, `light`, `dark`.
- One-click Material M3 demo page installer.
- ProcessWire AdminThemeUikit / `pw-design-system` friendly settings UI.

## Install

Copy `Meteo` to `/site/modules/`, refresh modules in ProcessWire admin, then install and configure **Meteo**.

## Links

- [Usage and API](docs/USAGE.md)
- [Changelog](CHANGELOG.md)

## Author

Maxim Semenov

[smnv.org](https://smnv.org)

[maxim@smnv.org](mailto:maxim@smnv.org)

## Support

If this project helps your work, consider supporting future development:

- [GitHub Sponsors](https://github.com/sponsors/mxmsmnv)
- [smnv.org/sponsor](https://smnv.org/sponsor/)

## License

MIT
