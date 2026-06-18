<?php namespace ProcessWire;

trait MeteoRendering {

    public function styleTag(): string {
        $url = $this->wire('config')->urls->siteModules . 'Meteo/assets/met.css';
        $v   = self::getModuleInfo()['version'];
        return '<link rel="stylesheet" href="' . htmlspecialchars($url . '?v=' . $v, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function renderWidget(float $lat, float $lon, array $options = [], string $template = 'card'): string {
        $weather = $this->getWeather($lat, $lon, $options);
        if (!$weather) return '<div class="mt-error">Weather data unavailable.</div>';

        $file = $this->resolveTemplate($template);
        if (!$file) return '<div class="mt-error">Template not found: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '</div>';

        ob_start();
        $w = $weather;
        $mtOptions = array_merge($this->moduleOptions(), $options);
        $mtTheme = $this->normalizeWidgetTheme($mtOptions['widget_theme'] ?? 'auto');
        include $file;
        return ob_get_clean() ?: '';
    }

    public function renderWidgetForCity(string $city, array $options = [], string $template = 'card'): string {
        $opts = array_merge($this->moduleOptions(), $options);
        $locs = $this->geocode($city, $opts['language'], 1);
        if (empty($locs)) return '<div class="mt-error">City not found: ' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8') . '</div>';
        $loc = $locs[0];
        $options['location_name'] = $options['location_name'] ?? ($loc['name'] . ', ' . $loc['country']);
        return $this->renderWidget($loc['lat'], $loc['lon'], $options, $template);
    }

    protected function resolveTemplate(string $t): string|false {
        if (str_starts_with($t, '/')) {
            $real = realpath($t);
            if (!$real || !is_file($real)) return false;
            $allowedRoots = [
                realpath($this->wire('config')->paths->templates . 'Meteo'),
                realpath(__DIR__ . '/templates'),
            ];
            foreach (array_filter($allowedRoots) as $root) {
                if ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR)) return $real;
            }
            return false;
        }
        $t = basename(preg_replace('/[^a-zA-Z0-9_\-]/', '', $t));
        if ($t === '') return false;
        $site = $this->wire('config')->paths->templates . "Meteo/{$t}.php";
        if (file_exists($site)) return $site;
        $mod = __DIR__ . "/templates/{$t}.php";
        if (file_exists($mod)) return $mod;
        return false;
    }

    protected function normalizeWidgetTheme(string $theme): string {
        return in_array($theme, ['auto', 'light', 'dark'], true) ? $theme : 'auto';
    }

    protected function widgetThemeClass(string $theme): string {
        return 'mt-theme-' . $this->normalizeWidgetTheme($theme);
    }
}
