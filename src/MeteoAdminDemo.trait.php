<?php namespace ProcessWire;

trait MeteoAdminDemo {

    public function init(): void {
        $action = '';
        foreach (['mt_clear_cache', 'mt_install_demo', 'mt_uninstall_demo'] as $key) {
            if ($this->wire('input')->post($key) === '1') {
                $action = $key;
                break;
            }
        }

        if ($action === '') return;

        if (!$this->wire('user')->isSuperuser()
            || $this->wire('page')->template !== 'admin'
            || !$this->wire('session')->CSRF->validate()
        ) {
            return;
        }

        if ($action === 'mt_clear_cache') {
            $this->clearCache();
            $this->wire('session')->message('Meteo: cache cleared.');
            $this->redirectToConfig();
        }

        if ($action === 'mt_install_demo') {
            $url = $this->installDemo();
            $this->wire('session')->message('Meteo: demo installed at ' . $url);
            $this->redirectToConfig();
        }

        if ($action === 'mt_uninstall_demo') {
            $this->uninstallDemo();
            $this->wire('session')->message('Meteo: demo removed.');
            $this->redirectToConfig();
        }
    }

    public function demoStatus(): array {
        $page = $this->wire('pages')->get('/' . self::DEMO_PAGE . '/');
        $template = $this->wire('templates')->get(self::DEMO_TEMPLATE);
        return [
            'installed' => (bool)($page->id && $template && $template->id),
            'url'       => $page->id ? $page->url : $this->wire('config')->urls->root . self::DEMO_PAGE . '/',
        ];
    }

    public function installDemo(): string {
        $templatePath = $this->wire('config')->paths->templates . self::DEMO_TEMPLATE . '.php';
        file_put_contents($templatePath, $this->demoTemplateSource(), LOCK_EX);

        $templates = $this->wire('templates');
        $template = $templates->get(self::DEMO_TEMPLATE);
        if (!$template || !$template->id) {
            $template = $templates->add(self::DEMO_TEMPLATE);
        }

        $title = $this->wire('fields')->get('title');
        if ($title && !$template->fieldgroup->hasField('title')) {
            $template->fieldgroup->add($title);
            $template->fieldgroup->save();
        }

        $page = $this->wire('pages')->get('/' . self::DEMO_PAGE . '/');
        if (!$page->id) {
            $page = new Page();
            $page->template = $template;
            $page->parent = $this->wire('pages')->get('/');
            $page->name = self::DEMO_PAGE;
            $page->title = 'Meteo Demo';
            $page->save();
        }

        return $page->url;
    }

    public function uninstallDemo(): void {
        $page = $this->wire('pages')->get('/' . self::DEMO_PAGE . '/');
        if ($page->id && $page->template->name === self::DEMO_TEMPLATE) {
            $currentPage = $this->wire('page');
            if (!$currentPage || !$currentPage->id) {
                $this->wire('page', $this->wire('pages')->get('/'));
            }
            $this->wire('pages')->delete($page, true);
            if ($currentPage) {
                $this->wire('page', $currentPage);
            }
        }

        $template = $this->wire('templates')->get(self::DEMO_TEMPLATE);
        if ($template && $template->id) {
            $this->wire('templates')->delete($template);
        }

        $templatePath = $this->wire('config')->paths->templates . self::DEMO_TEMPLATE . '.php';
        if (file_exists($templatePath)) @unlink($templatePath);
    }

    protected function redirectToConfig(): void {
        $this->wire('session')->redirect(
            $this->wire('config')->urls->admin . 'module/edit?name=Meteo&collapse_info=1'
        );
    }

    protected function demoTemplateSource(): string {
        return <<<'PHP'
<?php namespace ProcessWire;

$meteo = $modules->get('Meteo');
$lat = 40.7128;
$lon = -74.0060;
$options = [
    'provider' => 'open_meteo',
    'language' => 'en',
    'timezone' => 'America/New_York',
    'location_name' => 'New York',
];

?>
<div id="html-head" pw-append>
    <?= $meteo->styleTag() ?>
    <style>
        :root {
            color-scheme: light dark;
            --md-sys-color-primary: #2f5da8;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #d8e2ff;
            --md-sys-color-on-primary-container: #001a42;
            --md-sys-color-secondary-container: #dbe2f9;
            --md-sys-color-on-secondary-container: #151b2c;
            --md-sys-color-tertiary-container: #ffd9e2;
            --md-sys-color-on-tertiary-container: #3f0018;
            --md-sys-color-surface: #fbf8ff;
            --md-sys-color-surface-container-low: #f5f2fa;
            --md-sys-color-surface-container: #efedf4;
            --md-sys-color-surface-container-high: #e9e7ef;
            --md-sys-color-on-surface: #1b1b21;
            --md-sys-color-on-surface-variant: #5d6170;
            --md-sys-color-outline-variant: #c5c6d0;
            --md-sys-elevation-1: 0 1px 2px rgba(31, 39, 56, .11), 0 1px 3px rgba(31, 39, 56, .08);
            --md-sys-elevation-2: 0 2px 6px rgba(31, 39, 56, .12), 0 6px 16px rgba(31, 39, 56, .08);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --md-sys-color-primary: #adc6ff;
                --md-sys-color-on-primary: #002f68;
                --md-sys-color-primary-container: #16467f;
                --md-sys-color-on-primary-container: #d8e2ff;
                --md-sys-color-secondary-container: #3f4759;
                --md-sys-color-on-secondary-container: #dbe2f9;
                --md-sys-color-tertiary-container: #703348;
                --md-sys-color-on-tertiary-container: #ffd9e2;
                --md-sys-color-surface: #121318;
                --md-sys-color-surface-container-low: #1a1b20;
                --md-sys-color-surface-container: #1e2026;
                --md-sys-color-surface-container-high: #292b31;
                --md-sys-color-on-surface: #e4e2e9;
                --md-sys-color-on-surface-variant: #c5c6d0;
                --md-sys-color-outline-variant: #464954;
                --md-sys-elevation-1: 0 1px 2px rgba(0, 0, 0, .28), 0 1px 3px rgba(0, 0, 0, .18);
                --md-sys-elevation-2: 0 2px 6px rgba(0, 0, 0, .32), 0 10px 24px rgba(0, 0, 0, .22);
            }
        }

        body {
            margin: 0;
            background: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
            font-family: Roboto, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        #html-body {
            box-sizing: border-box;
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px clamp(16px, 4vw, 40px) 64px;
        }

        #topnav,
        #html-body > hr {
            display: none;
        }

        #headline {
            margin: 0;
            font-size: clamp(32px, 5vw, 56px);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .mt-m3-shell {
            display: grid;
            gap: 24px;
            max-width: 100%;
            min-width: 0;
        }

        #content {
            max-width: none;
            width: 100%;
        }

        .mt-m3-appbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 64px;
            gap: 16px;
            padding: 0 4px;
        }

        .mt-m3-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 14px;
            font-weight: 600;
        }

        .mt-m3-mark {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: 20px;
            background: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-on-primary-container);
            box-shadow: var(--md-sys-elevation-1);
        }

        .mt-m3-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .mt-m3-chip {
            display: inline-flex;
            align-items: center;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 8px;
            background: transparent;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .mt-m3-chip--filled {
            border-color: transparent;
            background: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
        }

        .mt-m3-hero {
            box-sizing: border-box;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 420px);
            gap: 24px;
            align-items: stretch;
            max-width: 100%;
            min-width: 0;
            padding: clamp(20px, 4vw, 36px);
            border-radius: 28px;
            background: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-on-primary-container);
            box-shadow: var(--md-sys-elevation-1);
        }

        .mt-m3-lede {
            max-width: 720px;
            margin: 14px 0 0;
            color: color-mix(in srgb, var(--md-sys-color-on-primary-container) 72%, transparent);
            font-size: 18px;
            line-height: 1.55;
        }

        .mt-m3-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 22px;
        }

        .mt-m3-hero-card {
            align-self: center;
            padding: 18px;
            border-radius: 24px;
            background: color-mix(in srgb, var(--md-sys-color-surface) 86%, transparent);
            box-shadow: var(--md-sys-elevation-2);
        }

        .mt-m3-grid {
            display: grid;
            grid-template-columns: minmax(280px, 420px) minmax(320px, 1fr);
            gap: 20px;
            align-items: start;
            max-width: 100%;
            min-width: 0;
        }

        .mt-m3-panel {
            box-sizing: border-box;
            display: grid;
            gap: 16px;
            max-width: 100%;
            min-width: 0;
            padding: 18px;
            border-radius: 24px;
            background: var(--md-sys-color-surface-container-low);
            box-shadow: var(--md-sys-elevation-1);
        }

        .mt-m3-panel--high {
            background: var(--md-sys-color-surface-container-high);
        }

        .mt-m3-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .mt-m3-panel h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .mt-m3-label {
            color: var(--md-sys-color-on-surface-variant);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .mt-m3-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding: 14px;
            border-radius: 18px;
            background: var(--md-sys-color-surface-container);
        }

        .mt-m3-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .mt-m3-list li {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 16px;
            background: var(--md-sys-color-surface-container);
            color: var(--md-sys-color-on-surface-variant);
            font-size: 14px;
        }

        .mt-m3-list strong {
            color: var(--md-sys-color-on-surface);
            font-weight: 700;
        }

        .mt-m3-panel .mt-card,
        .mt-m3-panel .mt-full,
        .mt-m3-hero-card .mt-minimal {
            box-sizing: border-box;
            max-width: 100% !important;
            min-width: 0 !important;
            width: 100% !important;
        }

        .mt-m3-panel .mt-full {
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .mt-m3-hero,
            .mt-m3-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            #html-body {
                padding-top: 12px;
            }

            .mt-m3-appbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .mt-m3-hero,
            .mt-m3-panel {
                border-radius: 20px;
                padding: 14px;
            }
        }
    </style>
</div>

<h1 id="headline" pw-remove></h1>

<div id="content" pw-replace>
    <div class="mt-m3-shell">
        <header class="mt-m3-appbar" aria-label="Meteo demo toolbar">
            <div class="mt-m3-brand">
                <span class="mt-m3-mark" aria-hidden="true">☁</span>
                <span>Meteo module demo</span>
            </div>
            <nav class="mt-m3-actions" aria-label="Demo shortcuts">
                <a class="mt-m3-chip" href="/narzan/module/edit?name=Meteo&collapse_info=1">Settings</a>
                <a class="mt-m3-chip mt-m3-chip--filled" href="/meteo-demo/">Live demo</a>
            </nav>
        </header>

        <section class="mt-m3-hero">
            <div>
                <h1><?= $sanitizer->entities($page->title) ?></h1>
                <p class="mt-m3-lede">Material Design 3 presentation for the bundled Meteo widgets, including tonal surfaces, adaptive color scheme, and forced light or dark widget themes.</p>
                <div class="mt-m3-hero-meta">
                    <span class="mt-m3-chip mt-m3-chip--filled">Open-Meteo</span>
                    <span class="mt-m3-chip">New York</span>
                    <span class="mt-m3-chip">Auto theme</span>
                </div>
            </div>
            <div class="mt-m3-hero-card">
                <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'auto'], 'minimal') ?>
            </div>
        </section>

        <div class="mt-m3-grid">
            <section class="mt-m3-panel">
                <div class="mt-m3-panel-head">
                    <h2>Card</h2>
                    <span class="mt-m3-label">Auto</span>
                </div>
                <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'auto'], 'card') ?>
            </section>

            <section class="mt-m3-panel mt-m3-panel--high">
                <div class="mt-m3-panel-head">
                    <h2>Full Forecast</h2>
                    <span class="mt-m3-label">Dark</span>
                </div>
                <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'dark'], 'full') ?>
            </section>

            <section class="mt-m3-panel">
                <div class="mt-m3-panel-head">
                    <h2>Minimal Variants</h2>
                    <span class="mt-m3-label">Light / Dark / Auto</span>
                </div>
                <div class="mt-m3-inline">
                    <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'light'], 'minimal') ?>
                    <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'dark'], 'minimal') ?>
                    <?= $meteo->renderWidget($lat, $lon, $options + ['widget_theme' => 'auto'], 'minimal') ?>
                </div>
            </section>

            <section class="mt-m3-panel">
                <div class="mt-m3-panel-head">
                    <h2>Configuration</h2>
                    <span class="mt-m3-label">Runtime</span>
                </div>
                <ul class="mt-m3-list">
                    <li><span>Provider</span><strong>Open-Meteo</strong></li>
                    <li><span>Timezone</span><strong>America/New_York</strong></li>
                    <li><span>Templates</span><strong>card, full, minimal</strong></li>
                    <li><span>Themes</span><strong>auto, light, dark</strong></li>
                </ul>
            </section>
        </div>
    </div>
</div>
PHP;
    }
}
