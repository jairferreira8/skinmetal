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
        'members' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.3"/><path d="M15.5 14.2c2.4.3 4.5 2.2 4.5 5.8"/>',
        'gallery' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
        'about' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-5"/><circle cx="12" cy="8.2" r="0.2" fill="currentColor"/>',
        'media' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/>',
        'characters' => '<path d="M4 21v-2a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v2"/><circle cx="9" cy="8" r="4"/><path d="M17 3.5a4 4 0 0 1 0 7.8"/><path d="M18 14.5c2.2.5 4 2.2 4 4.5v2h-3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];
    $inner = $icons[$name] ?? '';
    return "<svg {$common}>{$inner}</svg>";
}
