<?php namespace ProcessWire;
/**
 * Meteo — Full Template
 * Hero current + detail tiles + 24h hourly + 7-day with temperature bar.
 */
if (!isset($w) || empty($w)) return;
require_once __DIR__ . '/_icons.php';

$cur    = $w['current'];
$daily  = $w['daily'];
$hourly = $w['hourly'];
$loc    = $w['location'];
$isDay  = $cur['is_day'];
$unit   = $cur['temp_unit'];
$themeClass = isset($mtTheme) ? 'mt-theme-' . preg_replace('/[^a-z]/', '', $mtTheme) : 'mt-theme-auto';

$uvLabel = match(true) {
    ($daily[0]['uv_index'] ?? 0) >= 11 => 'Extreme',
    ($daily[0]['uv_index'] ?? 0) >= 8  => 'Very High',
    ($daily[0]['uv_index'] ?? 0) >= 6  => 'High',
    ($daily[0]['uv_index'] ?? 0) >= 3  => 'Moderate',
    default => 'Low',
};
?>
<div class="mt-full <?= $isDay ? 'mt-day' : 'mt-night' ?> <?= _mtEsc($themeClass) ?>">

    <div class="mt-full__hero">
        <div class="mt-full__hero-left">
            <?php if ($loc['name']): ?><h2 class="mt-full__city"><?= _mtEsc($loc['name']) ?></h2><?php endif; ?>
            <div class="mt-full__temp-big"><?= _mtEsc($cur['temperature']) ?><span class="mt-unit"><?= _mtEsc($unit) ?></span></div>
            <div class="mt-full__condition"><?= _mtEsc($cur['weather_label']) ?></div>
            <?php if (!empty($daily)): ?>
            <div class="mt-full__range">H: <?= _mtEsc($daily[0]['temp_max']) ?>° &nbsp; L: <?= _mtEsc($daily[0]['temp_min']) ?>°</div>
            <?php endif; ?>
        </div>
        <div class="mt-full__hero-right">
            <div class="mt-full__icon-xl"><?= _wwIcon($cur['weather_icon'], $isDay) ?></div>
        </div>
    </div>

    <div class="mt-full__details-grid">
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Feels Like</div><div class="mt-detail-tile__val"><?= _mtEsc($cur['feels_like']) ?><?= _mtEsc($unit) ?></div></div>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Humidity</div><div class="mt-detail-tile__val"><?= _mtEsc($cur['humidity']) ?>%</div></div>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Wind</div><div class="mt-detail-tile__val"><?= _mtEsc($cur['wind_speed']) ?> <?= _mtEsc($cur['wind_unit']) ?></div><div class="mt-detail-tile__sub"><?= _mtEsc($cur['wind_direction_label']) ?></div></div>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Pressure</div><div class="mt-detail-tile__val"><?= _mtEsc($cur['pressure']) ?> <small><?= _mtEsc($cur['pressure_unit'] ?? 'hPa') ?></small></div></div>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Cloud Cover</div><div class="mt-detail-tile__val"><?= _mtEsc($cur['cloud_cover']) ?>%</div></div>
        <?php if (!empty($daily[0]['uv_index'])): ?>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">UV Index</div><div class="mt-detail-tile__val"><?= _mtEsc($daily[0]['uv_index']) ?></div><div class="mt-detail-tile__sub"><?= _mtEsc($uvLabel) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($daily[0]['sunrise_label'])): ?>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Sunrise</div><div class="mt-detail-tile__val"><?= _mtEsc($daily[0]['sunrise_label']) ?></div></div>
        <div class="mt-detail-tile"><div class="mt-detail-tile__label">Sunset</div><div class="mt-detail-tile__val"><?= _mtEsc($daily[0]['sunset_label']) ?></div></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($hourly)): ?>
    <div class="mt-full__section-title">Next 24 Hours</div>
    <div class="mt-full__hourly">
        <div class="mt-hourly-scroll">
        <?php foreach ($hourly as $h): ?>
            <div class="mt-hourly-item">
                <div class="mt-hourly-time"><?= _mtEsc($h['time_label']) ?></div>
                <div class="mt-hourly-icon"><?= _wwIcon($h['weather_icon'], $h['is_day']) ?></div>
                <div class="mt-hourly-temp"><?= _mtEsc($h['temperature']) ?>°</div>
                <div class="mt-hourly-rain"><?= $h['precip_prob'] > 15 ? _wwMetaIcon('precip') . _mtEsc($h['precip_prob']) . '%' : '' ?></div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($daily)): ?>
    <?php
    $allMin = min(array_column($daily, 'temp_min'));
    $allMax = max(array_column($daily, 'temp_max'));
    $range  = max(1, $allMax - $allMin);
    ?>
    <div class="mt-full__section-title">7-Day Forecast</div>
    <div class="mt-full__daily">
        <?php foreach ($daily as $i => $d): ?>
        <div class="mt-daily-row">
            <div class="mt-daily-day"><?= _mtEsc($i === 0 ? 'Today' : $d['date_label']) ?></div>
            <div class="mt-daily-icon"><?= _wwIcon($d['weather_icon']) ?></div>
            <div class="mt-daily-label"><?= _mtEsc($d['weather_label']) ?></div>
            <div class="mt-daily-rain"><?= $d['precip_prob'] > 15 ? _wwMetaIcon('precip') . _mtEsc($d['precip_prob']) . '%' : '' ?></div>
            <div class="mt-daily-bar">
                <?php
                $left  = round(($d['temp_min'] - $allMin) / $range * 100);
                $width = round(($d['temp_max'] - $d['temp_min']) / $range * 100);
                ?>
                <div class="mt-daily-bar__track">
                    <div class="mt-daily-bar__fill" style="left:<?= _mtEsc($left) ?>%;width:<?= _mtEsc(max(10, $width)) ?>%"></div>
                </div>
            </div>
            <div class="mt-daily-temps">
                <span class="mt-daily-high"><?= _mtEsc($d['temp_max']) ?>°</span>
                <span class="mt-daily-low"><?= _mtEsc($d['temp_min']) ?>°</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="mt-card__footer">
        <a href="<?= _mtEsc($w['provider_url']) ?>" target="_blank" rel="noopener" class="mt-source"><?= _mtEsc($w['provider']) ?></a>
    </div>
</div>
