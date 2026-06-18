<?php namespace ProcessWire;

trait MeteoConfigInputfields {

    public static function getModuleConfigInputfields(array $data): InputfieldWrapper {
        $modules = wire('modules');
        $wrap    = new InputfieldWrapper();
        $data    = array_merge([
            'provider'          => 'open_meteo',
            'units'             => 'metric',
            'language'          => 'en',
            'widget_theme'       => 'auto',
            'timezone'          => '',
            'cache_time'        => 1800,
            'owm_key'           => '',
            'wapi_key'          => '',
            'fallback_providers'=> '',
        ], $data);

        $cachePath = wire('config')->paths->cache . 'Meteo/';
        $cacheFiles = is_dir($cachePath) ? (glob($cachePath . 'mt_*.json') ?: []) : [];
        $cacheCount = count($cacheFiles);
        $cacheSize  = 0;
        foreach ($cacheFiles as $cf) $cacheSize += filesize($cf);
        $cacheSizeKb = round($cacheSize / 1024, 1);

        $demo = $modules->get('Meteo');
        $status = $demo instanceof self ? $demo->demoStatus() : ['installed' => false, 'url' => ''];
        $csrfName  = wire('session')->CSRF->getTokenName();
        $csrfValue = wire('session')->CSRF->getTokenValue();
        $adminUrl  = wire('config')->urls->admin . 'module/edit?name=Meteo';
        $providerLabels = [
            'open_meteo' => 'Open-Meteo',
            'openweathermap' => 'OpenWeatherMap',
            'weatherapi' => 'WeatherAPI.com',
        ];

        $f = $modules->get('InputfieldMarkup');
        $f->label = 'Overview';
        $f->value = '<style>'
            . '.mt-admin-settings{--mts-card:var(--pw-blocks-background,#fff);--mts-text:var(--pw-text-color,#111827);--mts-muted:var(--pw-muted-color,#64748b);--mts-border:var(--pw-border-color,#d7dde8);--mts-soft:var(--pw-inputs-background,#f1f5f9);--mts-accent:var(--pw-main-color,#eb1d61);--mts-good-bg:var(--pw-alert-success,rgba(15,118,110,.12));--mts-warn-bg:var(--pw-alert-warning,rgba(180,83,9,.12));display:grid;gap:14px;margin:0 0 4px}'
            . '.mt-admin-settings__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}'
            . '.mt-admin-settings__card{min-width:0;padding:12px 14px;border:1px solid var(--mts-border);border-radius:8px;background:var(--mts-card);box-shadow:0 1px 2px rgba(15,23,42,.05)}'
            . '.mt-admin-settings__label{display:block;margin:0 0 5px;color:var(--mts-muted);font-size:11px;font-weight:700;letter-spacing:0;text-transform:uppercase}'
            . '.mt-admin-settings__value{display:block;color:var(--mts-text);font-size:15px;font-weight:700;line-height:1.25}'
            . '.mt-admin-settings__note{display:block;margin-top:4px;color:var(--mts-muted);font-size:12px;line-height:1.35}'
            . '.mt-admin-settings__pill{display:inline-flex;align-items:center;min-height:24px;padding:0 9px;border-radius:999px;background:var(--mts-soft);color:var(--mts-text);font-size:12px;font-weight:700;white-space:nowrap}'
            . '.mt-admin-settings__pill--ok{background:var(--mts-good-bg);color:var(--mts-text)}.mt-admin-settings__pill--warn{background:var(--mts-warn-bg);color:var(--mts-text)}'
            . '@media(prefers-color-scheme:dark){.mt-admin-settings{--mts-card:var(--pw-blocks-background,#1f2937);--mts-text:var(--pw-text-color,#f8fafc);--mts-muted:var(--pw-muted-color,#cbd5e1);--mts-border:var(--pw-border-color,#374151);--mts-soft:var(--pw-inputs-background,#111827)}}'
            . 'body.AdminThemeUikitDark .mt-admin-settings,.AdminThemeUikitDark .mt-admin-settings,.uk-light .mt-admin-settings{--mts-card:var(--pw-blocks-background,#1f2937);--mts-text:var(--pw-text-color,#f8fafc);--mts-muted:var(--pw-muted-color,#cbd5e1);--mts-border:var(--pw-border-color,#374151);--mts-soft:var(--pw-inputs-background,#111827)}'
            . '</style><div class="mt-admin-settings pw-wrap"><div class="mt-admin-settings__grid uk-grid-small">'
            . '<div class="mt-admin-settings__card uk-card uk-card-default uk-card-small"><span class="mt-admin-settings__label uk-text-muted">Provider</span><span class="mt-admin-settings__value">' . htmlspecialchars($providerLabels[$data['provider']] ?? $data['provider'], ENT_QUOTES, 'UTF-8') . '</span><span class="mt-admin-settings__note">Fallbacks: ' . htmlspecialchars($data['fallback_providers'] ?: 'none', ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="mt-admin-settings__card uk-card uk-card-default uk-card-small"><span class="mt-admin-settings__label uk-text-muted">Display</span><span class="mt-admin-settings__value">' . htmlspecialchars(strtoupper($data['units']) . ' · ' . $data['language'] . ' · ' . $data['widget_theme'], ENT_QUOTES, 'UTF-8') . '</span><span class="mt-admin-settings__note">Timezone: ' . htmlspecialchars($data['timezone'] ?: 'auto', ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="mt-admin-settings__card uk-card uk-card-default uk-card-small"><span class="mt-admin-settings__label uk-text-muted">Cache</span><span class="mt-admin-settings__value">' . (int)$data['cache_time'] . ' seconds</span><span class="mt-admin-settings__note">' . $cacheCount . ' files · ' . $cacheSizeKb . ' KB</span></div>'
            . '<div class="mt-admin-settings__card uk-card uk-card-default uk-card-small"><span class="mt-admin-settings__label uk-text-muted">API Keys</span><span class="mt-admin-settings__value"><span class="mt-admin-settings__pill uk-label ' . ($data['owm_key'] ? 'mt-admin-settings__pill--ok uk-label-success' : 'mt-admin-settings__pill--warn uk-label-warning') . '">OWM ' . ($data['owm_key'] ? 'set' : 'missing') . '</span> <span class="mt-admin-settings__pill uk-label ' . ($data['wapi_key'] ? 'mt-admin-settings__pill--ok uk-label-success' : 'mt-admin-settings__pill--warn uk-label-warning') . '">WAPI ' . ($data['wapi_key'] ? 'set' : 'missing') . '</span></span><span class="mt-admin-settings__note">Only required for non-Open-Meteo providers.</span></div>'
            . '<div class="mt-admin-settings__card uk-card uk-card-default uk-card-small"><span class="mt-admin-settings__label uk-text-muted">Demo</span><span class="mt-admin-settings__value"><span class="mt-admin-settings__pill uk-label ' . ($status['installed'] ? 'mt-admin-settings__pill--ok uk-label-success' : 'mt-admin-settings__pill--warn uk-label-warning') . '">' . ($status['installed'] ? 'Installed' : 'Not installed') . '</span></span><span class="mt-admin-settings__note">/meteo-demo/</span></div>'
            . '</div></div>';
        $wrap->add($f);

        // --- Basics ---
        $basics = $modules->get('InputfieldFieldset');
        $basics->label = 'Basics';
        $basics->description = 'Default provider, localization, units, timezone, and bundled widget theme.';

        $f = $modules->get('InputfieldSelect');
        $f->attr('name', 'provider');
        $f->label = 'Weather Provider';
        $f->description = 'Primary provider used when no per-call override is supplied.';
        $f->addOptions([
            'open_meteo'     => 'Open-Meteo (free, no key)',
            'openweathermap' => 'OpenWeatherMap (free tier, API key required)',
            'weatherapi'     => 'WeatherAPI.com (free tier, API key required)',
        ]);
        $f->attr('value', $data['provider']);
        $f->columnWidth = 50;
        $basics->add($f);

        $f = $modules->get('InputfieldSelect');
        $f->attr('name', 'units');
        $f->label = 'Units';
        $f->addOptions([
            'metric'   => 'Metric (°C, km/h, mm)',
            'imperial' => 'Imperial (°F, mph, in)',
        ]);
        $f->attr('value', $data['units']);
        $f->columnWidth = 25;
        $basics->add($f);

        $f = $modules->get('InputfieldSelect');
        $f->attr('name', 'widget_theme');
        $f->label = 'Widget Theme';
        $f->description = 'Visual theme for bundled templates. Auto follows the visitor color scheme.';
        $f->addOptions([
            'auto'  => 'Auto',
            'light' => 'Light',
            'dark'  => 'Dark',
        ]);
        $f->attr('value', $data['widget_theme']);
        $f->columnWidth = 25;
        $basics->add($f);

        $f = $modules->get('InputfieldSelect');
        $f->attr('name', 'language');
        $f->label = 'Language';
        $f->description = 'Language for weather condition labels and city names.';
        $f->addOptions([
            'en' => 'English','ru' => 'Русский','de' => 'Deutsch','fr' => 'Français','es' => 'Español',
            'pt' => 'Português','it' => 'Italiano','ja' => '日本語','zh' => '中文','ko' => '한국어',
            'nl' => 'Nederlands','pl' => 'Polski','uk' => 'Українська','tr' => 'Türkçe','ar' => 'العربية',
        ]);
        $f->attr('value', $data['language']);
        $f->columnWidth = 50;
        $basics->add($f);

        $f = $modules->get('InputfieldText');
        $f->attr('name', 'timezone');
        $f->label = 'Default Timezone';
        $f->description = 'IANA timezone, for example America/New_York. Leave blank to auto-detect from coordinates where supported.';
        $f->attr('value', $data['timezone']);
        $f->columnWidth = 50;
        $basics->add($f);
        $wrap->add($basics);

        // --- Reliability ---
        $reliability = $modules->get('InputfieldFieldset');
        $reliability->label = 'Reliability & Cache';
        $reliability->description = 'Fallbacks and cache settings keep pages fast when providers are slow or unavailable.';

        $f = $modules->get('InputfieldText');
        $f->attr('name', 'fallback_providers');
        $f->label = 'Fallback Providers';
        $f->description = 'Comma-separated provider names to try if primary fails. Example: weatherapi,open_meteo';
        $f->notes = 'Allowed values: open_meteo, openweathermap, weatherapi.';
        $f->attr('value', $data['fallback_providers']);
        $f->columnWidth = 50;
        $reliability->add($f);

        $f = $modules->get('InputfieldInteger');
        $f->attr('name', 'cache_time');
        $f->label = 'Cache Duration';
        $f->description = 'API responses cached in /site/assets/cache/Meteo/. Set 0 to disable.';
        $f->notes = "Current cache: {$cachePath} · {$cacheCount} files · {$cacheSizeKb} KB";
        $f->attr('value', $data['cache_time']);
        $f->columnWidth = 25;
        $reliability->add($f);

        $f = $modules->get('InputfieldMarkup');
        $f->label = 'Cache Actions';
        if ($cacheCount > 0) {
            $f->value  = '<form method="post" action="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline">'
                . '<input type="hidden" name="mt_clear_cache" value="1">'
                . '<input type="hidden" name="' . htmlspecialchars($csrfName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($csrfValue, ENT_QUOTES, 'UTF-8') . '">'
                . '<button type="submit" class="uk-button uk-button-default uk-button-small ui-button ui-widget" onclick="return confirm(\'Clear ' . $cacheCount . ' cached files?\')">'
                . '<span class="ui-button-text">Clear ' . $cacheCount . ' files (' . $cacheSizeKb . ' KB)</span></button>'
                . '</form>';
        } else {
            $f->value = '<span style="opacity:.65;font-size:13px">No cached files.</span>';
        }
        $f->columnWidth = 25;
        $reliability->add($f);
        $wrap->add($reliability);

        // --- API Keys ---
        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = 'API Keys';
        $fieldset->description = 'Only needed when OpenWeatherMap or WeatherAPI.com is used as primary or fallback provider.';
        $fieldset->collapsed = Inputfield::collapsedYes;

        $f = $modules->get('InputfieldText');
        $f->attr('name', 'owm_key');
        $f->label = 'OpenWeatherMap API Key';
        $f->description = 'Used by provider openweathermap.';
        $f->notes = 'Get a free key at openweathermap.org/api.';
        $f->attr('value', $data['owm_key']);
        $f->attr('type', 'password');
        $f->columnWidth = 50;
        $fieldset->add($f);

        $f = $modules->get('InputfieldText');
        $f->attr('name', 'wapi_key');
        $f->label = 'WeatherAPI.com API Key';
        $f->description = 'Used by provider weatherapi.';
        $f->notes = 'Get a free key at weatherapi.com.';
        $f->attr('value', $data['wapi_key']);
        $f->attr('type', 'password');
        $f->columnWidth = 50;
        $fieldset->add($f);
        $wrap->add($fieldset);

        // --- Demo ---
        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = 'Demo';
        $fieldset->description = 'Install or remove a Material Design 3 demo page for all bundled widgets.';

        $value = '<p style="margin-top:0">Demo page: <code>/meteo-demo/</code>. It renders card, full, and minimal widgets with theme variants.</p>';
        if ($status['installed']) {
            $value .= '<p><a class="uk-button uk-button-primary uk-button-small ui-button ui-widget" href="' . htmlspecialchars($status['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
                . '<span class="ui-button-text">Open Demo</span></a></p>';
            $value .= '<form method="post" action="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline">'
                . '<input type="hidden" name="mt_uninstall_demo" value="1">'
                . '<input type="hidden" name="' . htmlspecialchars($csrfName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($csrfValue, ENT_QUOTES, 'UTF-8') . '">'
                . '<button type="submit" class="uk-button uk-button-danger uk-button-small ui-button ui-widget" onclick="return confirm(\'Remove Meteo demo page and template?\')">'
                . '<span class="ui-button-text">Remove Demo</span></button></form>';
        } else {
            $value .= '<form method="post" action="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline">'
                . '<input type="hidden" name="mt_install_demo" value="1">'
                . '<input type="hidden" name="' . htmlspecialchars($csrfName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($csrfValue, ENT_QUOTES, 'UTF-8') . '">'
                . '<button type="submit" class="uk-button uk-button-primary uk-button-small ui-button ui-widget">'
                . '<span class="ui-button-text">Install Demo</span></button></form>';
        }

        $f = $modules->get('InputfieldMarkup');
        $f->label = 'Demo Page';
        $f->value = $value;
        $fieldset->add($f);
        $wrap->add($fieldset);
        // --- Info ---
        $f = $modules->get('InputfieldMarkup');
        $f->label = 'Usage';
        $usageExamples = [
            'Raw weather data' => <<<'CODE'
$meteo = $modules->get('Meteo');
$weather = $meteo->getWeather(40.7128, -74.0060, [
    'location_name' => 'New York',
    'timezone' => 'America/New_York',
]);

echo $weather['current']['temperature'];
echo $weather['current']['weather_label'];
CODE,
            'City search + forecast' => <<<'CODE'
$meteo = $modules->get('Meteo');
$places = $meteo->geocode('London', 'en', 3);

if ($places) {
    $place = $places[0];
    $weather = $meteo->getWeather($place['lat'], $place['lon'], [
        'location_name' => $place['name'] . ', ' . $place['country'],
        'timezone' => $place['timezone'],
    ]);
}
CODE,
            'Bundled widget with theme' => <<<'CODE'
echo $modules->Meteo->styleTag();

echo $modules->Meteo->renderWidget(40.7128, -74.0060, [
    'location_name' => 'New York',
    'timezone' => 'America/New_York',
    'widget_theme' => 'dark', // auto | light | dark
], 'card'); // card | full | minimal
CODE,
            'Provider fallback' => <<<'CODE'
$weather = $modules->Meteo->getWeather(48.8566, 2.3522, [
    'provider' => 'openweathermap',
    'fallback_providers' => 'weatherapi,open_meteo',
    'units' => 'imperial',
    'language' => 'fr',
    'cache_time' => 3600,
]);
CODE,
            'Custom template' => <<<'CODE'
// /site/templates/Meteo/compact.php receives $w and $mtTheme.
echo $modules->Meteo->renderWidget(52.52, 13.405, [
    'location_name' => 'Berlin',
    'widget_theme' => 'auto',
], 'compact');
CODE,
            'Error handling' => <<<'CODE'
$weather = $modules->Meteo->getWeather($lat, $lon);

if ($weather === false) {
    $log->save('meteo', $modules->Meteo->getLastError());
    echo '<p>Weather data unavailable.</p>';
}
CODE,
        ];

        $usageHtml = '<style>'
            . '.mt-admin-usage{--mtu-bg:var(--pw-main-background,#f8fafc);--mtu-card:var(--pw-blocks-background,#fff);--mtu-text:var(--pw-text-color,#111827);--mtu-muted:var(--pw-muted-color,#64748b);--mtu-border:var(--pw-border-color,#d7dde8);--mtu-code:var(--pw-code-color,#0f172a);--mtu-code-bg:var(--pw-code-background,#eef2f8);display:grid;gap:14px}'
            . '.mt-admin-usage__intro{margin:0;color:var(--mtu-muted);font-size:13px;line-height:1.5}'
            . '.mt-admin-usage__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}'
            . '.mt-admin-usage__card{overflow:hidden;border:1px solid var(--mtu-border);border-radius:8px;background:var(--mtu-card);box-shadow:0 1px 2px rgba(15,23,42,.05)}'
            . '.mt-admin-usage__card h4{margin:0;padding:10px 12px;border-bottom:1px solid var(--mtu-border);color:var(--mtu-text);font-size:13px;font-weight:700}'
            . '.mt-admin-usage__card pre{margin:0;padding:12px;overflow:auto;background:var(--mtu-code-bg);color:var(--mtu-code);font:12px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;tab-size:4;white-space:pre}'
            . '.mt-admin-usage__shape{padding:12px;border:1px solid var(--mtu-border);border-radius:8px;background:var(--mtu-card)}'
            . '.mt-admin-usage__shape h4{margin:0 0 5px;color:var(--mtu-text);font-size:13px;font-weight:700}'
            . '.mt-admin-usage__shape p{margin:0 0 10px;color:var(--mtu-muted);font-size:13px;line-height:1.45}'
            . '.mt-admin-usage__shape dl{display:grid;grid-template-columns:minmax(90px,max-content) 1fr;gap:6px 12px;margin:0;color:var(--mtu-muted);font-size:12px;line-height:1.4}'
            . '.mt-admin-usage__shape dt{color:var(--mtu-text);font-weight:700}'
            . '.mt-admin-usage__shape dd{margin:0}'
            . '@media(prefers-color-scheme:dark){.mt-admin-usage{--mtu-bg:var(--pw-main-background,#111827);--mtu-card:var(--pw-blocks-background,#1f2937);--mtu-text:var(--pw-text-color,#f8fafc);--mtu-muted:var(--pw-muted-color,#cbd5e1);--mtu-border:var(--pw-border-color,#374151);--mtu-code:var(--pw-code-color,#e5edf8);--mtu-code-bg:var(--pw-code-background,#0f172a)}}'
            . 'body.AdminThemeUikitDark .mt-admin-usage,.AdminThemeUikitDark .mt-admin-usage,.uk-light .mt-admin-usage{--mtu-bg:var(--pw-main-background,#111827);--mtu-card:var(--pw-blocks-background,#1f2937);--mtu-text:var(--pw-text-color,#f8fafc);--mtu-muted:var(--pw-muted-color,#cbd5e1);--mtu-border:var(--pw-border-color,#374151);--mtu-code:var(--pw-code-color,#e5edf8);--mtu-code-bg:var(--pw-code-background,#0f172a)}'
            . '</style>'
            . '<div class="mt-admin-usage pw-wrap">'
            . '<p class="mt-admin-usage__intro uk-text-muted">Common Meteo integration patterns. All examples can use global module settings or per-call overrides.</p>'
            . '<div class="mt-admin-usage__grid">';

        foreach ($usageExamples as $title => $code) {
            $usageHtml .= '<section class="mt-admin-usage__card uk-card uk-card-default uk-card-small"><h4>'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                . '</h4><pre><code>'
                . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
                . '</code></pre></section>';
        }

        $usageHtml .= '</div><section class="mt-admin-usage__shape">'
            . '<h4>Returned data shape</h4>'
            . '<p>This is a reference for the array returned by getWeather(). It is not live cache status; cached responses use the same shape.</p>'
            . '<dl>'
            . '<dt>current</dt><dd>Current conditions: temperature, feels_like, humidity, weather_label, wind_speed.</dd>'
            . '<dt>hourly</dt><dd>Forecast slots for the next 24 hours.</dd>'
            . '<dt>daily</dt><dd>Daily forecast for up to 7 days.</dd>'
            . '<dt>location</dt><dd>Resolved location metadata: lat, lon, name, timezone.</dd>'
            . '<dt>themes</dt><dd>Widget display options: auto, light, dark. These are settings, not provider data.</dd>'
            . '</dl></section></div>';

        $f->value = $usageHtml;
        $wrap->add($f);
        return $wrap;
    }
}
