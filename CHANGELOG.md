# Changelog

## 1.1.0 — 2026-06-18

### Added
- Added widget theme modes: `auto`, `light`, and `dark`.
- Added one-click Material M3 demo page install/remove from module settings.
- Added redesigned module settings UI with `pw-design-system` / AdminThemeUikit token support.

### Fixed
- Improved provider fallback diagnostics and unknown provider handling.
- Fixed weather cache keys for timezone/location-specific output.
- Fixed WeatherAPI date parsing for daily labels and sunrise/sunset.
- Hardened JSON/cache handling and custom template resolution.
- Removed PHP 8.5 deprecated `curl_close()` usage.

### Changed
- Split module internals into focused `src/` files.
- Moved usage and API documentation to `docs/USAGE.md` and kept README as a short entry point.

## 1.0.1 — 2026-05-27

### Fixed
- Fixed wind direction labels.
- Fixed fallback provider cache reads/writes.
- Fixed OWM timezone offset formatting.
- Added CSRF protection for cache clearing.
- Moved trait loading to file scope.
- Made `styleTag()` use the module version for cache busting.

## 1.0.0 — 2026-05-25

### Added
- Initial stable release.
- Added Open-Meteo, OpenWeatherMap, and WeatherAPI.com providers.
- Added structured weather output, geocoding, file cache, fallback providers, and error reporting.
- Added bundled `card`, `full`, and `minimal` widget templates with inline SVG icons.
