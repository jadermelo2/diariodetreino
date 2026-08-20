<?php
/**
 * icons.php — ícones de linha (SVG) usados em todo o app, no lugar de
 * emojis. Todos em viewBox 24×24, stroke="currentColor" (herdam a cor do
 * texto ao redor via CSS), sem preenchimento — combina com o tema
 * Tactical (funcional, sem cor decorativa própria).
 *
 * Uso: <?= svg_icon('home') ?>  ou  <?= svg_icon('home', 'icon minha-classe') ?>
 *
 * Espelhado em assets/js/icons.js pros trechos gerados via JavaScript
 * (não dá pra reusar o mesmo array dali sem um passo de build, então os
 * dois arquivos mantêm os mesmos ícones em formatos equivalentes).
 */

const ICONS = [
    'home' => '<path d="M4 11.5L12 4l8 7.5"/><path d="M6 10v9.5a.5.5 0 0 0 .5.5H9v-6h6v6h2.5a.5.5 0 0 0 .5-.5V10"/>',

    'play' => '<path d="M7 4.5v15l13-7.5-13-7.5z"/>',

    'trending-up' => '<polyline points="3,17 9.5,10.5 13.5,14.5 21,7"/><polyline points="15,7 21,7 21,13"/>',

    'scale' => '<rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="13" r="3.2"/><path d="M12 13l2.2-2.8"/><path d="M9.5 4v2.5"/><path d="M14.5 4v2.5"/>',

    'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 9.5h17"/><path d="M8 3.5v3"/><path d="M16 3.5v3"/>',

    'dumbbell' => '<path d="M6.5 8v8"/><path d="M17.5 8v8"/><path d="M4 10.5v3"/><path d="M20 10.5v3"/><path d="M8.5 12h7"/>',

    'clipboard' => '<rect x="5" y="4.5" width="14" height="16.5" rx="1.5"/><rect x="9" y="3" width="6" height="3" rx="1"/><path d="M8.5 10.5h7"/><path d="M8.5 14h7"/><path d="M8.5 17.5h4.5"/>',

    'save' => '<path d="M4.5 4.5h12l3 3v12a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5z"/><path d="M7.5 4.5v5h7v-5"/><rect x="7.5" y="13.5" width="9" height="6"/>',

    'flame' => '<path d="M12 21c-3.5 0-6-2.5-6-6 0-2.8 1.8-4.8 2.8-7.5.4 1.8 1.5 2.3 1.5 2.3-.5-2.8 1-4.8 3.3-6.3-1 2.8 0 4.6 2 6.5 1.3 1.3 2.4 2.9 2.4 5 0 3.5-2.5 6-6 6z"/>',

    'trophy' => '<path d="M8 4.5h8v4a4 4 0 0 1-8 0v-4z"/><path d="M8 5.5H5.2A2.8 2.8 0 0 0 8 9.5"/><path d="M16 5.5h2.8A2.8 2.8 0 0 1 16 9.5"/><path d="M12 12.5v3"/><path d="M8.5 19.5h7"/><path d="M10 19.5v-2a2 2 0 0 1 4 0v2"/>',

    'pencil' => '<path d="M4.5 19.5l.8-3.4L15.8 5.6l2.6 2.6L7.9 18.7l-3.4.8z"/><path d="M14 7.4l2.6 2.6"/>',

    'alert-triangle' => '<path d="M12 3.5l9.2 16H2.8L12 3.5z"/><path d="M12 10v4.5"/><circle cx="12" cy="17.3" r="0.9" fill="currentColor" stroke="none"/>',

    'check' => '<polyline points="4.5,13 9,17.5 19.5,6"/>',

    'check-circle' => '<circle cx="12" cy="12" r="9"/><polyline points="7.5,12.5 10.5,15.5 16.5,8.5"/>',

    'flag' => '<path d="M5.5 3v18"/><path d="M5.5 4h13l-3 4 3 4h-13"/>',

    'ruler' => '<rect x="2.5" y="9" width="19" height="6" rx="1"/><path d="M6.5 9v2.5"/><path d="M10.5 9v2.5"/><path d="M14.5 9v2.5"/><path d="M18.5 9v2.5"/>',

    'chevron-right' => '<polyline points="9,5 16,12 9,19"/>',
];

/**
 * Monta o markup do ícone. $class default 'icon' já traz o tamanho
 * (1em × 1em, ver theme.css) pra herdar o font-size do elemento ao redor.
 */
function svg_icon(string $name, string $class = 'icon'): string
{
    $inner = ICONS[$name] ?? '';
    return '<svg class="' . htmlspecialchars($class) . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true" focusable="false">' . $inner . '</svg>';
}
