<?php namespace ProcessWire;
/**
 * Meteo — SVG Icon Library
 * Include this once, then call _wwIcon($slug, $isDay)
 */
if (function_exists('ProcessWire\_wwIcon')) return;

function _mtEsc(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function _wwIcon(string $icon, bool $isDay = true): string {
    static $icons;
    if (!$icons) $icons = _wwIconMap();
    $key = $icon . (!$isDay && isset($icons[$icon . '-night']) ? '-night' : '');
    return '<svg viewBox="0 0 64 64" class="mt-svg" aria-hidden="true">' . ($icons[$key] ?? $icons['unknown'] ?? '') . '</svg>';
}

function _wwIconSmall(string $icon, bool $isDay = true): string {
    return '<span class="mt-weather-icon mt-icon--xs">' . _wwIcon($icon, $isDay) . '</span>';
}

function _wwMetaIcon(string $name): string {
    static $meta;
    if (!$meta) $meta = _wwMetaMap();
    return '<svg viewBox="0 0 24 24" class="mt-svg" aria-hidden="true">' . ($meta[$name] ?? '') . '</svg>';
}

function _wwMetaMap(): array {
    return [
        'humidity'    => '<path d="M12 3a9 9 0 0 0-2 17.7V16H8l4-5 4 5h-2v4.7A9 9 0 0 0 12 3z" fill="#93C5FD"/>',
        'wind'        => '<path d="M3 12h12a2 2 0 0 0 0-4 2 2 0 0 0-1.7 1" stroke="#93C5FD" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 16h8a1.5 1.5 0 0 0 0-3" stroke="#93C5FD" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 20h10a2.5 2.5 0 0 0 0-5 2.5 2.5 0 0 0-1 0.2" stroke="#93C5FD" stroke-width="2" fill="none" stroke-linecap="round"/>',
        'pressure'    => '<circle cx="12" cy="12" r="9" stroke="#93C5FD" stroke-width="2" fill="none"/><path d="M12 7v5l3 3" stroke="#93C5FD" stroke-width="2" stroke-linecap="round"/>',
        'visibility'  => '<circle cx="12" cy="12" r="4" fill="none" stroke="#93C5FD" stroke-width="2"/><path d="M2 12S5 5 12 5s10 7 10 7-3 7-10 7-10-7-10-7z" fill="none" stroke="#93C5FD" stroke-width="2"/>',
        'precip'      => '<path d="M8 18V4m0 0L4 8m4-4 4 4" stroke="#93C5FD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 18V10m0 0-4 4m4-4 4 4" stroke="#93C5FD" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'sunrise'     => '<circle cx="12" cy="16" r="5" fill="#FBBF24"/><path d="M12 4v4M5 8l2 2M19 8l-2 2" stroke="#FBBF24" stroke-width="2" stroke-linecap="round"/>',
        'sunset'      => '<circle cx="12" cy="14" r="5" fill="#F59E0B"/><path d="M12 4v3M5 8l2 2M19 8l-2 2" stroke="#F59E0B" stroke-width="2" stroke-linecap="round"/>',
        'uv'          => '<circle cx="12" cy="12" r="5" fill="#F59E0B"/><g stroke="#FBBF24" stroke-width="2" stroke-linecap="round"><line x1="12" y1="3" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="21"/><line x1="3" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="21" y2="12"/></g>',
    ];
}

function _wwIconMap(): array {
    return [
        'clear' => '<circle cx="32" cy="32" r="11" fill="#FBBF24"/>
            <g stroke="#FBBF24" stroke-width="2.5" stroke-linecap="round">
                <line x1="32" y1="6" x2="32" y2="12"/><line x1="32" y1="52" x2="32" y2="58"/>
                <line x1="6" y1="32" x2="12" y2="32"/><line x1="52" y1="32" x2="58" y2="32"/>
                <line x1="14" y1="14" x2="18" y2="18"/><line x1="46" y1="46" x2="50" y2="50"/>
                <line x1="50" y1="14" x2="46" y2="18"/><line x1="18" y1="46" x2="14" y2="50"/>
            </g>',
        'clear-night' => '<path d="M42 20a20 20 0 1 0-6 36 14 14 0 0 0 6-36z" fill="#E2E8F0"/>',
        'mostly-clear' => '<circle cx="22" cy="28" r="9" fill="#FBBF24"/>
            <g stroke="#FBBF24" stroke-width="2" stroke-linecap="round">
                <line x1="22" y1="10" x2="22" y2="15"/><line x1="22" y1="41" x2="22" y2="46"/>
                <line x1="4" y1="28" x2="9" y2="28"/><line x1="35" y1="28" x2="40" y2="28"/>
                <line x1="9" y1="15" x2="13" y2="19"/><line x1="31" y1="37" x2="35" y2="41"/>
            </g>
            <ellipse cx="40" cy="42" rx="18" ry="10" fill="#E2E8F0"/>
            <ellipse cx="30" cy="46" rx="14" ry="8" fill="#F1F5F9"/>',
        'mostly-clear-night' => '<path d="M38 18a16 16 0 1 0-4 28 10 10 0 0 0 4-28z" fill="#CBD5E1"/>
            <ellipse cx="42" cy="44" rx="18" ry="10" fill="#64748B"/>
            <ellipse cx="32" cy="48" rx="14" ry="8" fill="#475569"/>',
        'partly-cloudy' => '<circle cx="20" cy="28" r="10" fill="#FBBF24"/>
            <ellipse cx="40" cy="38" rx="20" ry="11" fill="#CBD5E1"/>
            <ellipse cx="28" cy="43" rx="16" ry="9" fill="#E2E8F0"/>',
        'partly-cloudy-night' => '<path d="M34 16a14 14 0 1 0-4 26 10 10 0 0 0 4-26z" fill="#94A3B8"/>
            <ellipse cx="40" cy="40" rx="20" ry="11" fill="#475569"/>
            <ellipse cx="28" cy="45" rx="16" ry="9" fill="#334155"/>',
        'overcast' => '<ellipse cx="32" cy="28" rx="22" ry="12" fill="#94A3B8"/>
            <ellipse cx="22" cy="38" rx="18" ry="10" fill="#CBD5E1"/>
            <ellipse cx="42" cy="40" rx="16" ry="9" fill="#E2E8F0"/>',
        'fog' => '<g stroke="#94A3B8" stroke-width="3" stroke-linecap="round">
                <line x1="10" y1="22" x2="54" y2="22"/>
                <line x1="14" y1="31" x2="50" y2="31"/>
                <line x1="10" y1="40" x2="54" y2="40"/>
                <line x1="16" y1="49" x2="48" y2="49"/>
            </g>',
        'drizzle' => '<ellipse cx="32" cy="22" rx="20" ry="11" fill="#94A3B8"/>
            <g stroke="#93C5FD" stroke-width="2.5" stroke-linecap="round">
                <line x1="22" y1="38" x2="20" y2="48"/><line x1="32" y1="38" x2="30" y2="48"/><line x1="42" y1="38" x2="40" y2="48"/>
            </g>',
        'rain' => '<ellipse cx="32" cy="20" rx="22" ry="12" fill="#64748B"/>
            <g stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="38" x2="14" y2="52"/><line x1="28" y1="38" x2="24" y2="52"/>
                <line x1="38" y1="38" x2="34" y2="52"/><line x1="48" y1="38" x2="44" y2="52"/>
            </g>',
        'rain-heavy' => '<ellipse cx="32" cy="18" rx="24" ry="13" fill="#475569"/>
            <g stroke="#1D4ED8" stroke-width="3" stroke-linecap="round">
                <line x1="14" y1="36" x2="10" y2="54"/><line x1="24" y1="36" x2="20" y2="54"/>
                <line x1="34" y1="36" x2="30" y2="54"/><line x1="44" y1="36" x2="40" y2="54"/><line x1="54" y1="36" x2="50" y2="54"/>
            </g>',
        'snow' => '<ellipse cx="32" cy="20" rx="22" ry="12" fill="#CBD5E1"/>
            <g fill="#BFDBFE">
                <circle cx="20" cy="40" r="3"/><circle cx="32" cy="42" r="3"/>
                <circle cx="44" cy="40" r="3"/><circle cx="26" cy="52" r="2.5"/><circle cx="38" cy="52" r="2.5"/>
            </g>',
        'snow-heavy' => '<ellipse cx="32" cy="18" rx="24" ry="12" fill="#CBD5E1"/>
            <g fill="#93C5FD">
                <circle cx="14" cy="38" r="3"/><circle cx="26" cy="40" r="3"/><circle cx="38" cy="38" r="3"/><circle cx="50" cy="38" r="3"/>
                <circle cx="20" cy="52" r="3"/><circle cx="32" cy="54" r="3"/><circle cx="44" cy="52" r="3"/>
            </g>',
        'showers' => '<circle cx="18" cy="22" r="10" fill="#FBBF24"/>
            <ellipse cx="42" cy="30" rx="18" ry="10" fill="#64748B"/>
            <g stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round">
                <line x1="26" y1="44" x2="22" y2="56"/><line x1="36" y1="42" x2="32" y2="56"/><line x1="46" y1="44" x2="42" y2="56"/>
            </g>',
        'showers-heavy' => '<ellipse cx="32" cy="20" rx="24" ry="12" fill="#475569"/>
            <g stroke="#1E40AF" stroke-width="3" stroke-linecap="round">
                <line x1="16" y1="36" x2="12" y2="54"/><line x1="26" y1="36" x2="22" y2="54"/>
                <line x1="36" y1="36" x2="32" y2="54"/><line x1="46" y1="36" x2="42" y2="54"/>
            </g>',
        'snow-showers' => '<circle cx="16" cy="20" r="10" fill="#FBBF24"/>
            <ellipse cx="40" cy="28" rx="20" ry="10" fill="#94A3B8"/>
            <g fill="#BFDBFE">
                <circle cx="24" cy="44" r="3"/><circle cx="36" cy="46" r="3"/><circle cx="48" cy="44" r="3"/>
            </g>',
        'thunderstorm' => '<ellipse cx="32" cy="18" rx="24" ry="13" fill="#334155"/>
            <polygon points="36,32 27,32 31,44 22,44 34,60 30,46 39,46" fill="#FCD34D"/>',
        'thunderstorm-hail' => '<ellipse cx="32" cy="16" rx="24" ry="12" fill="#1E293B"/>
            <polygon points="36,28 27,28 31,40 22,40 34,54 30,42 39,42" fill="#FCD34D"/>
            <g fill="#BAE6FD">
                <circle cx="16" cy="52" r="3"/><circle cx="46" cy="54" r="3"/><circle cx="54" cy="46" r="2.5"/>
            </g>',
        'unknown' => '<circle cx="32" cy="32" r="26" fill="none" stroke="#94A3B8" stroke-width="3"/>
            <text x="32" y="40" text-anchor="middle" font-size="26" fill="#94A3B8">?</text>',
    ];
}
