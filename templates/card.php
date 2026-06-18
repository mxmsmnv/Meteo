<?php namespace ProcessWire;
/**
 * Meteo — Card Template
 * Current + hourly strip + 7-day forecast.
 * Variable $w = full weather data array from Meteo::getWeather()
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
?>
<div class="mt-card <?= $isDay ? 'mt-day' : 'mt-night' ?> <?= _mtEsc($themeClass) ?>">

    <div class="mt-card__header">
        <?php if ($loc['name']): ?>
        <div class="mt-location">
            <svg class="mt-icon-pin" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0a5 5 0 0 0-5 5c0 4.5 5 11 5 11s5-6.5 5-11a5 5 0 0 0-5-5zm0 7a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
            <?= _mtEsc($loc['name']) ?>
        </div>
        <?php endif; ?>
        <div class="mt-updated"><?= _mtEsc(date('H:i', $w['updated_at'])) ?></div>
    </div>

    <div class="mt-card__current">
        <div class="mt-weather-icon mt-icon--lg"><?= _wwIcon($cur['weather_icon'], $isDay) ?></div>
        <div class="mt-card__temps">
            <div class="mt-temp-main"><?= _mtEsc($cur['temperature']) ?><span class="mt-unit"><?= _mtEsc($unit) ?></span></div>
            <div class="mt-weather-label"><?= _mtEsc($cur['weather_label']) ?></div>
            <div class="mt-feels-like">
                <?php if (!empty($daily)): ?>
                &#8593;<?= _mtEsc($daily[0]['temp_max'] ?? '-') ?>°
                &nbsp;&#8595;<?= _mtEsc($daily[0]['temp_min'] ?? '-') ?>°
                &nbsp;·
                <?php endif; ?>
                Feels <?= _mtEsc($cur['feels_like']) ?><?= _mtEsc($unit) ?>
            </div>
        </div>
    </div>

    <div class="mt-card__details">
        <div class="mt-detail">
            <span class="mt-detail__icon"><?= _wwMetaIcon('humidity') ?></span>
            <span class="mt-detail__val"><?= _mtEsc($cur['humidity']) ?>%</span>
            <span class="mt-detail__lbl">Humidity</span>
        </div>
        <div class="mt-detail">
            <span class="mt-detail__icon"><?= _wwMetaIcon('wind') ?></span>
            <span class="mt-detail__val"><?= _mtEsc($cur['wind_speed']) ?>&nbsp;<small><?= _mtEsc($cur['wind_unit']) ?></small></span>
            <span class="mt-detail__lbl"><?= _mtEsc($cur['wind_direction_label']) ?></span>
        </div>
        <div class="mt-detail">
            <span class="mt-detail__icon"><?= _wwMetaIcon('pressure') ?></span>
            <span class="mt-detail__val"><?= _mtEsc($cur['pressure']) ?>&nbsp;<small><?= _mtEsc($cur['pressure_unit'] ?? 'hPa') ?></small></span>
            <span class="mt-detail__lbl">Pressure</span>
        </div>
        <?php if ($cur['visibility'] !== null): ?>
        <div class="mt-detail">
            <span class="mt-detail__icon"><?= _wwMetaIcon('visibility') ?></span>
            <span class="mt-detail__val"><?= _mtEsc($cur['visibility']) ?>&nbsp;<small><?= _mtEsc($cur['visibility_unit'] ?? 'km') ?></small></span>
            <span class="mt-detail__lbl">Visibility</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($daily[0]['sunrise_label'])): ?>
    <div class="mt-card__sun">
        <span><?= _wwMetaIcon('sunrise') ?> <?= _mtEsc($daily[0]['sunrise_label']) ?></span>
        <span><?= _wwMetaIcon('sunset') ?> <?= _mtEsc($daily[0]['sunset_label']) ?></span>
        <?php if (!empty($daily[0]['uv_index'])): ?><span><?= _wwMetaIcon('uv') ?> UV&nbsp;<?= _mtEsc($daily[0]['uv_index']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($hourly)): ?>
    <div class="mt-card__hourly">
        <div class="mt-hourly-scroll">
        <?php foreach (array_slice($hourly, 0, 12) as $h): ?>
            <div class="mt-hourly-item">
                <div class="mt-hourly-time"><?= _mtEsc($h['time_label']) ?></div>
                <div class="mt-hourly-icon"><?= _wwIcon($h['weather_icon'], $h['is_day']) ?></div>
                <div class="mt-hourly-temp"><?= _mtEsc($h['temperature']) ?>°</div>
                <div class="mt-hourly-rain"><?= $h['precip_prob'] > 20 ? _wwMetaIcon('precip') . _mtEsc($h['precip_prob']) . '%' : '' ?></div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($daily)): ?>
    <div class="mt-card__daily">
        <?php foreach ($daily as $d): ?>
        <div class="mt-daily-row">
            <div class="mt-daily-day"><?= _mtEsc($d['date_label']) ?></div>
            <div class="mt-daily-icon"><?= _wwIcon($d['weather_icon']) ?></div>
            <div class="mt-daily-label"><?= _mtEsc($d['weather_label']) ?></div>
            <div class="mt-daily-rain"><?= $d['precip_prob'] > 20 ? _wwMetaIcon('precip') . _mtEsc($d['precip_prob']) . '%' : '' ?></div>
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
