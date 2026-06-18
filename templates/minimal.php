<?php namespace ProcessWire;
/**
 * Meteo — Minimal Template
 * Inline badge: icon + temp + location.
 */
if (!isset($w) || empty($w)) return;
require_once __DIR__ . '/_icons.php';

$cur   = $w['current'];
$loc   = $w['location'];
$isDay = $cur['is_day'];
$themeClass = isset($mtTheme) ? 'mt-theme-' . preg_replace('/[^a-z]/', '', $mtTheme) : 'mt-theme-auto';
?>
<span class="mt-minimal <?= $isDay ? 'mt-day' : 'mt-night' ?> <?= _mtEsc($themeClass) ?>">
    <span class="mt-minimal__icon"><?= _wwIcon($cur['weather_icon'], $isDay) ?></span>
    <span class="mt-minimal__temp"><?= _mtEsc($cur['temperature']) ?><?= _mtEsc($cur['temp_unit']) ?></span>
    <?php if ($loc['name']): ?><span class="mt-minimal__loc"><?= _mtEsc($loc['name']) ?></span><?php endif; ?>
</span>
