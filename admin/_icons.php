<?php
declare(strict_types=1);

/** Minimal inline SVG icon set (stroke-based, 24x24 viewBox). Returns raw SVG markup. */
function admin_icon(string $name): string {
    $common = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'music' => '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
        'products' => '<path d="M6 7l1.5-3h9L18 7"/><path d="M4 7h16l-1 13H5L4 7z"/><path d="M9 11a3 3 0 0 0 6 0"/>',
        'shows' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18"/><path d="M8 2v4M16 2v4"/>',
        'videos' => '<rect x="2" y="5" width="15" height="14" rx="2"/><path d="M17 10l5-3v10l-5-3"/>',
        'release' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/>',
        'links' => '<path d="M9 15l6-6"/><path d="M11 6l.7-.7a4 4 0 0 1 5.7 5.7L16 12"/><path d="M13 18l-.7.7a4 4 0 0 1-5.7-5.7L8 12"/>',
        'messages' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 6l10 7 10-7"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];
    $inner = $icons[$name] ?? '';
    return "<svg {$common}>{$inner}</svg>";
}
