<?php
/**
 * Catálogo de marca: estilos visuales, tipografías y vehículos del hero.
 *
 * Vive aparte porque el instalador lo necesita antes de que exista la base de
 * datos, así que no puede depender de functions.php. Las descripciones van por
 * idioma aquí mismo para no inflar los archivos de lang.
 */
require_once __DIR__ . '/i18n.php';

/**
 * Los cuatro estilos no son cuatro paletas: cambian la estructura (radios,
 * sombras, bordes, tipografía y densidad). Es la diferencia entre parecerse y
 * no parecerse a primera vista.
 */
function skin_catalog(): array {
    return [
        'classic' => [
            'name' => ['es' => 'Clásico',   'en' => 'Classic'],
            'desc' => ['es' => 'Tarjetas redondeadas con sombra suave y acento sólido. Cercano y legible.',
                       'en' => 'Rounded cards with a soft shadow and a solid accent. Warm and readable.'],
        ],
        'terminal' => [
            'name' => ['es' => 'Terminal',  'en' => 'Terminal'],
            'desc' => ['es' => 'Monoespaciada, esquinas rectas, retícula visible y fondo oscuro por default. Se lee como una consola.',
                       'en' => 'Monospaced, square corners, visible grid and a dark ground by default. Reads like a console.'],
        ],
        'editorial' => [
            'name' => ['es' => 'Editorial', 'en' => 'Editorial'],
            'desc' => ['es' => 'Títulos con serif, mucho aire, líneas finas en lugar de tarjetas. Parece una publicación.',
                       'en' => 'Serif headings, generous air, hairlines instead of cards. Feels like a publication.'],
        ],
        'minimal' => [
            'name' => ['es' => 'Minimal',   'en' => 'Minimal'],
            'desc' => ['es' => 'Sin sombras ni rellenos, tipografía chica y mucho espacio. Todo el peso en los números.',
                       'en' => 'No shadows or fills, small type, lots of space. All the weight on the numbers.'],
        ],
    ];
}

function skins(): array { return array_keys(skin_catalog()); }

function font_catalog(): array {
    return [
        'system'  => ['name' => ['es' => 'Del sistema', 'en' => 'System'],
                      'desc' => ['es' => 'La tipografía nativa de cada dispositivo. Carga instantánea, cero peticiones externas.',
                                 'en' => 'Each device\'s native face. Instant load, zero external requests.'],
                      'css' => ''],
        'grotesk' => ['name' => ['es' => 'Grotesca',    'en' => 'Grotesque'],
                      'desc' => ['es' => 'Archivo para títulos e Inter para texto. Técnica y compacta.',
                                 'en' => 'Archivo for headings and Inter for text. Technical and compact.'],
                      'css' => 'family=Archivo:wght@600;800&family=Inter:wght@400;600'],
        'serif'   => ['name' => ['es' => 'Serif',       'en' => 'Serif'],
                      'desc' => ['es' => 'Fraunces para títulos y Source Serif para texto. Editorial y cálida.',
                                 'en' => 'Fraunces for headings and Source Serif for text. Editorial and warm.'],
                      'css' => 'family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600'],
        'mono'    => ['name' => ['es' => 'Monoespaciada','en' => 'Monospace'],
                      'desc' => ['es' => 'IBM Plex Mono en todo. Los números se alinean solos.',
                                 'en' => 'IBM Plex Mono throughout. The numbers align themselves.'],
                      'css' => 'family=IBM+Plex+Mono:wght@400;500;600;700'],
    ];
}

function font_pairs(): array { return array_keys(font_catalog()); }

/** Familias CSS de cada pareja tipográfica. */
function font_stacks(string $pair): array {
    $sys = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    switch ($pair) {
        case 'grotesk': return ['head' => '"Archivo", ' . $sys, 'body' => '"Inter", ' . $sys];
        case 'serif':   return ['head' => '"Fraunces", Georgia, "Times New Roman", serif',
                                'body' => '"Source Serif 4", Georgia, "Times New Roman", serif'];
        case 'mono':    return ['head' => '"IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, monospace',
                                'body' => '"IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, monospace'];
        default:        return ['head' => $sys, 'body' => $sys];
    }
}

function vehicle_catalog(): array {
    return [
        'coin'   => ['name' => ['es' => 'Moneda con pasajero', 'en' => 'Coin with a rider'], 'glyph' => '🪙'],
        'rocket' => ['name' => ['es' => 'Cohete',              'en' => 'Rocket'],            'glyph' => '🚀'],
        'wagon'  => ['name' => ['es' => 'Vagoneta',            'en' => 'Wagon'],             'glyph' => '🎢'],
        'bolt'   => ['name' => ['es' => 'Rayo Lightning',      'en' => 'Lightning bolt'],    'glyph' => '⚡'],
    ];
}

function coaster_vehicles(): array { return array_keys(vehicle_catalog()); }

/** Nombre traducido de una entrada de catálogo. */
function kit_name(array $catalog, string $key): string {
    $loc = current_locale();
    return $catalog[$key]['name'][$loc] ?? ($catalog[$key]['name']['es'] ?? $key);
}

function kit_desc(array $catalog, string $key): string {
    $loc = current_locale();
    return $catalog[$key]['desc'][$loc] ?? ($catalog[$key]['desc']['es'] ?? '');
}

/** Sugerencia de nombre de sitio a partir del día elegido: "Martes de Satoshi". */
function suggested_site_name(int $day): string {
    $names = tr('days_cap');
    $d = $names[$day] ?? $names[4];
    return current_locale() === 'en' ? $d . ' Sats' : $d . ' de Satoshi';
}
