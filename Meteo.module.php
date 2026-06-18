<?php namespace ProcessWire;

require_once __DIR__ . '/src/MeteoWmoDictionary.trait.php';
require_once __DIR__ . '/src/MeteoCacheHttp.trait.php';
require_once __DIR__ . '/src/MeteoRendering.trait.php';
require_once __DIR__ . '/src/MeteoConfigInputfields.trait.php';
require_once __DIR__ . '/src/MeteoAdminDemo.trait.php';
require_once __DIR__ . '/src/MeteoProvider.php';
require_once __DIR__ . '/src/MeteoProviderOpenMeteo.php';
require_once __DIR__ . '/src/MeteoProviderOWM.php';
require_once __DIR__ . '/src/MeteoProviderWAPI.php';

/**
 * Meteo
 *
 * Weather integration for ProcessWire.
 * Returns structured data — no markup, no framework assumptions.
 * Providers: Open-Meteo (free), OpenWeatherMap, WeatherAPI.com
 *
 * @version 1.1.0
 * @license MIT
 */
class Meteo extends WireData implements Module, ConfigurableModule {

    protected const AUTHOR = 'Maxim Semenov';
    protected const WEBSITE = 'https://smnv.org';
    protected const DEMO_TEMPLATE = 'meteo-demo';
    protected const DEMO_PAGE = 'meteo-demo';

    protected ?string $lastError = null;

    use MeteoCacheHttp;
    use MeteoRendering;
    use MeteoConfigInputfields;
    use MeteoAdminDemo;

    public static function getModuleInfo(): array {
        return [
            'title'    => 'Meteo',
            'version'  => 110,
            'summary'  => 'Weather data module. Multiple providers, file cache, i18n.',
            'author'   => self::AUTHOR,
            'href'     => self::WEBSITE,
            'singular' => true,
            'autoload' => 'template=admin',
            'icon'     => 'cloud',
        ];
    }

    public function __construct() {
        parent::__construct();
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Get weather data for coordinates.
     * Tries primary provider, then fallback providers in order.
     *
     * @param float  $lat
     * @param float  $lon
     * @param array  $options  Override per-call: units, language, timezone, cache_time, location_name, provider, fallback_providers
     * @return array|false     Structured weather array or false on error
     */
    public function getWeather(float $lat, float $lon, array $options = []): array|false {
        $opts = array_merge($this->moduleOptions(), $options);
        $latKey = rtrim(rtrim(sprintf('%.6f', $lat), '0'), '.');
        $lonKey = rtrim(rtrim(sprintf('%.6f', $lon), '0'), '.');
        $timezoneKey = (string)($opts['timezone'] ?? '');
        $locationKey = (string)($opts['location_name'] ?? '');

        $primary = trim((string)($opts['provider'] ?? 'open_meteo'));
        if ($primary === '') $primary = 'open_meteo';
        $fallbacks = array_filter(
            array_map('trim', explode(',', $opts['fallback_providers'] ?? '')),
            fn($p) => $p !== '' && $p !== $primary
        );
        $providers = array_values(array_unique(array_merge([$primary], $fallbacks)));
        $errors = [];

        foreach ($providers as $providerName) {
            if (!$this->isKnownProvider($providerName)) {
                $errors[] = "{$providerName}: unknown provider";
                continue;
            }

            $cacheKey = 'weather_' . md5(implode('|', [
                $latKey,
                $lonKey,
                (string)$opts['units'],
                (string)$opts['language'],
                $timezoneKey,
                $locationKey,
                $providerName,
            ]));
            if ((int)$opts['cache_time'] > 0) {
                $cached = $this->cacheGet($cacheKey, (int)$opts['cache_time']);
                if ($cached !== false) { $this->lastError = null; return $cached; }
            }
            $provider = $this->getProvider($providerName);
            $data = $provider->fetch($lat, $lon, $opts);
            if ($data !== false) {
                $this->lastError = null;
                if ((int)$opts['cache_time'] > 0) {
                    $this->cacheSet($cacheKey, $data, (int)$opts['cache_time']);
                }
                return $data;
            }
            $errors[] = "{$providerName}: " . ($this->lastError ?: 'provider returned no data');
        }

        $this->lastError = 'All weather providers failed' . ($errors ? ' (' . implode('; ', $errors) . ')' : '');
        return false;
    }

    /**
     * Geocode a city name → coordinates.
     * Uses Open-Meteo geocoding API (free, no key). Results cached for 24h.
     *
     * @param string $city
     * @param string $language  ISO 639-1 code
     * @param int    $count
     * @return array
     */
    public function geocode(string $city, string $language = 'en', int $count = 5): array {
        $cacheKey = 'geocode_' . md5(strtolower($city) . '_' . $language . '_' . $count);

        if ($this->moduleOptions()['cache_time'] > 0) {
            $cached = $this->cacheGet($cacheKey, 86400); // geocoding: 24h TTL
            if ($cached !== false) return $cached;
        }

        $url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
            'name'     => $city,
            'count'    => $count,
            'language' => $language,
            'format'   => 'json',
        ]);

        $raw = $this->httpGet($url);
        if (!$raw) return [];

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'Geocoding JSON decode failed: ' . json_last_error_msg();
            return [];
        }
        if (empty($data['results'])) return [];

        $result = array_map(fn($r) => [
            'name'         => $r['name'],
            'country'      => $r['country'] ?? '',
            'country_code' => $r['country_code'] ?? '',
            'region'       => $r['admin1'] ?? '',
            'lat'          => (float)$r['latitude'],
            'lon'          => (float)$r['longitude'],
            'timezone'     => $r['timezone'] ?? 'UTC',
            'population'   => $r['population'] ?? 0,
        ], $data['results']);

        if ($this->moduleOptions()['cache_time'] > 0) {
            $this->cacheSet($cacheKey, $result, 86400);
        }

        return $result;
    }

    /**
     * Returns last error message or null if last operation was successful.
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    public function setLastError(?string $error): void {
        $this->lastError = $error;
    }

    // ----------------------------------------------------------------
    // Provider registry
    // ----------------------------------------------------------------

    protected function getProvider(string $name): MeteoProvider {
        static $instances = [];
        if (!isset($instances[$name])) {
            $class = match($name) {
                'openweathermap' => MeteoProviderOWM::class,
                'weatherapi'     => MeteoProviderWAPI::class,
                default          => MeteoProviderOpenMeteo::class,
            };
            $instances[$name] = new $class($this);
        }
        return $instances[$name];
    }

    protected function isKnownProvider(string $name): bool {
        return in_array($name, ['open_meteo', 'openweathermap', 'weatherapi'], true);
    }

    // ----------------------------------------------------------------
    // Defaults
    // ----------------------------------------------------------------

    public function moduleOptions(): array {
        return [
            'provider'          => $this->provider          ?: 'open_meteo',
            'units'             => $this->units              ?: 'metric',
            'language'          => $this->language           ?: 'en',
            'widget_theme'       => $this->widget_theme       ?: 'auto',
            'timezone'          => $this->timezone           ?: '',
            'cache_time'        => (int)($this->cache_time   ?? 1800),
            'owm_key'           => $this->owm_key            ?: '',
            'wapi_key'          => $this->wapi_key           ?: '',
            'fallback_providers'=> $this->fallback_providers ?: '',
        ];
    }

}
