<?php
/**
 * La montaña rusa del hero.
 *
 * El riel NO es un dibujo: se genera con el historial real de precios de esta
 * instalación. Dos sitios solo pueden coincidir si compraron los mismos días
 * al mismo precio, y el riel cambia cada semana al registrar una compra.
 *
 * Antes de la cuarta compra no hay curva que dibujar: se muestra una estación
 * con el carrito esperando.
 */

require_once __DIR__ . '/brandkit.php';

const COASTER_W       = 900;   // ancho del viewBox
const COASTER_H       = 250;   // alto del viewBox
/**
 * COASTER_TOP deja aire arriba a propósito. El vehículo se dibuja hasta 50px
 * por encima del punto del riel (el pasajero con los brazos en alto), y encima
 * gira con la pendiente: si el riel sube más, la cabeza se sale del viewBox y
 * el SVG la recorta.
 */
const COASTER_TOP     = 68;    // y del punto más alto que puede tomar el riel
const COASTER_BOTTOM  = 198;   // y del punto más bajo
const COASTER_GROUND  = 235;   // y del suelo
const COASTER_STATION = 160;   // y de la vía plana cuando aún no hay recorrido
const COASTER_MIN_PTS = 4;     // compras mínimas para dibujar el recorrido
/**
 * Tope de compras dibujadas: un año de compras semanales.
 *
 * El riel existe para transmitir las subidas y bajadas. Con varios años, la
 * altura se normaliza entre el precio mínimo y el máximo de todo el historial,
 * y los primeros años se aplanan contra el piso hasta volverse una raya. Se
 * dibujan las más recientes, que son las que todavía tienen textura.
 *
 * Va por número de compras y no por año calendario a propósito: así en enero el
 * riel no vuelve a la estación a esperar la cuarta compra del año.
 */
const COASTER_MAX_PTS = 52;

/**
 * Suaviza la serie con una media móvil de 3 cuando hay muchos puntos.
 * Sin esto, un año completo de precios produce un riel de picos intransitables.
 */
function coaster_smooth(array $v): array {
    $n = count($v);
    if ($n <= 16) return $v;
    $out = [];
    for ($i = 0; $i < $n; $i++) {
        $a = $v[max(0, $i - 1)];
        $b = $v[$i];
        $c = $v[min($n - 1, $i + 1)];
        $out[] = ($a + $b + $c) / 3;
    }
    return $out;
}

/**
 * Catmull-Rom → cúbicas de Bézier: convierte los puntos en curvas montables.
 *
 * Los puntos de control se acotan a la banda [COASTER_TOP, COASTER_BOTTOM]. Sin
 * eso, una serie en zigzag —o un año completo de precios semanales— hace que la
 * curva se pase de los datos hasta 23px por arriba, y el SVG le corta la cabeza
 * al pasajero. Una cúbica siempre queda dentro del casco convexo de sus cuatro
 * puntos de control, así que acotarlos garantiza que el riel se queda en la banda.
 */
function coaster_spline(array $pts): string {
    $n = count($pts);
    if ($n < 2) return '';
    $limit = fn(float $y) => max((float)COASTER_TOP, min((float)COASTER_BOTTOM, $y));

    $d = 'M ' . round($pts[0][0], 1) . ',' . round($pts[0][1], 1);
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)];
        $p1 = $pts[$i];
        $p2 = $pts[$i + 1];
        $p3 = $pts[min($n - 1, $i + 2)];
        $c1x = $p1[0] + ($p2[0] - $p0[0]) / 6;
        $c1y = $limit($p1[1] + ($p2[1] - $p0[1]) / 6);
        $c2x = $p2[0] - ($p3[0] - $p1[0]) / 6;
        $c2y = $limit($p2[1] - ($p3[1] - $p1[1]) / 6);
        $d .= ' C ' . round($c1x, 1) . ',' . round($c1y, 1)
            . ' ' . round($c2x, 1) . ',' . round($c2y, 1)
            . ' ' . round($p2[0], 1) . ',' . round($p2[1], 1);
    }
    return $d;
}

/** Postes de la estructura: solo en los picos y valles, y como máximo seis. */
function coaster_posts(array $pts): array {
    $n = count($pts);
    $ext = [];
    for ($i = 1; $i < $n - 1; $i++) {
        $prev = $pts[$i - 1][1];
        $cur  = $pts[$i][1];
        $next = $pts[$i + 1][1];
        $isPeak   = $cur <= $prev && $cur <= $next;
        $isValley = $cur >= $prev && $cur >= $next;
        if ($isPeak || $isValley) {
            $ext[] = ['x' => $pts[$i][0], 'y' => $cur, 'w' => abs($cur - ($prev + $next) / 2)];
        }
    }
    usort($ext, fn($a, $b) => $b['w'] <=> $a['w']);
    $ext = array_slice($ext, 0, 6);
    usort($ext, fn($a, $b) => $a['x'] <=> $b['x']);
    return $ext;
}

/**
 * Riel a partir de los precios de BTC de cada compra.
 * Devuelve path, postes, duración de la animación y si ya hay recorrido.
 */
function coaster_build(array $prices): array {
    $prices = array_values(array_map('floatval', array_filter($prices, fn($p) => $p > 0)));
    $total  = count($prices);
    if ($total > COASTER_MAX_PTS) {
        $prices = array_slice($prices, -COASTER_MAX_PTS);
    }
    $n = count($prices);

    if ($n < COASTER_MIN_PTS) {
        // Estación: vía plana, el carrito espera la primera compra.
        return [
            'ready'  => false,
            'needed' => COASTER_MIN_PTS - $n,
            'count'  => $n,
            'total'  => $total,
            'path'   => 'M -40,' . COASTER_STATION . ' L 940,' . COASTER_STATION,
            'posts'  => [['x' => 300, 'y' => COASTER_STATION, 'w' => 0],
                         ['x' => 620, 'y' => COASTER_STATION, 'w' => 0]],
            'dur'    => 0,
        ];
    }

    $series = coaster_smooth($prices);
    $min = min($series);
    $max = max($series);
    $span = ($max - $min) ?: 1;
    $x0 = -40.0;
    $x1 = (float)(COASTER_W + 40);
    $usable = COASTER_BOTTOM - COASTER_TOP;

    $pts = [];
    foreach ($series as $i => $p) {
        $x = $x0 + ($x1 - $x0) * ($n === 1 ? 0.5 : $i / ($n - 1));
        // Precio alto = riel alto: el eje Y del SVG crece hacia abajo, así que se invierte.
        $y = COASTER_BOTTOM - (($p - $min) / $span) * $usable;
        $pts[] = [$x, $y];
    }

    return [
        'ready'  => true,
        'needed' => 0,
        'count'  => $n,
        'total'  => $total,
        'path'   => coaster_spline($pts),
        'posts'  => coaster_posts($pts),
        'dur'    => min(18, max(7, 6 + $n * 0.22)),
    ];
}

/** SVG del vehículo. Todos van centrados en (0,0) y se orientan solos sobre el riel. */
function coaster_vehicle_svg(string $kind): string {
    switch ($kind) {
        case 'rocket':
            return '<g transform="translate(0,-24)">
            <path d="M 0,-20 C 8,-12 10,-2 10,4 L -10,4 C -10,-2 -8,-12 0,-20 Z" class="cart-body"/>
            <circle cx="0" cy="-8" r="3.6" class="cart-window"/>
            <path d="M -10,0 L -17,8 L -10,7 Z" class="cart-fin"/>
            <path d="M 10,0 L 17,8 L 10,7 Z" class="cart-fin"/>
            <path d="M -5,5 L 0,17 L 5,5 Z" class="cart-flame"/>
          </g>';
        case 'wagon':
            return '<g transform="translate(0,-22)">
            <line x1="-8" y1="-12" x2="-14" y2="-22" class="cart-arm"/>
            <line x1="8" y1="-12" x2="14" y2="-22" class="cart-arm"/>
            <circle cx="0" cy="-16" r="5.4" class="cart-head"/>
            <path d="M -13,-8 L 13,-8 L 10,6 L -10,6 Z" class="cart-body"/>
            <text x="0" y="3" text-anchor="middle" class="cart-b">₿</text>
            <circle cx="-8" cy="11" r="4" class="cart-wheel"/>
            <circle cx="8" cy="11" r="4" class="cart-wheel"/>
          </g>';
        case 'bolt':
            return '<g transform="translate(0,-20)">
            <circle cx="0" cy="0" r="14" class="cart-body"/>
            <path d="M 2,-9 L -5,1 L 0,1 L -2,9 L 5,-1 L 0,-1 Z" class="cart-window"/>
          </g>';
        case 'coin':
        default:
            return '<g transform="translate(0,-26)">
            <line x1="-9" y1="-13" x2="-16" y2="-24" class="cart-arm"/>
            <line x1="9" y1="-13" x2="16" y2="-24" class="cart-arm"/>
            <circle cx="0" cy="-18" r="6" class="cart-head"/>
            <path d="M -3,-17 Q 0,-14 3,-17" class="cart-smile"/>
            <circle cx="0" cy="0" r="14" class="cart-body"/>
            <text x="0" y="5.5" text-anchor="middle" class="cart-b">₿</text>
            <circle cx="-9" cy="13" r="4" class="cart-wheel"/>
            <circle cx="9" cy="13" r="4" class="cart-wheel"/>
          </g>';
    }
}

/**
 * Pinta la montaña rusa completa.
 * $prices: precios de BTC de cada compra, en orden. $vehicle: coin|rocket|wagon|bolt.
 * $id permite más de una en la misma página (el agregado de la red usa varias).
 */
function coaster_render(array $prices, string $vehicle = 'coin', string $caption = '', string $id = 'trackPath'): string {
    $c = coaster_build($prices);
    $veh = coaster_vehicle_svg(in_array($vehicle, coaster_vehicles(), true) ? $vehicle : 'coin');

    $posts = '';
    foreach ($c['posts'] as $p) {
        $posts .= '<line x1="' . round($p['x'], 1) . '" y1="' . round($p['y'], 1)
                . '" x2="' . round($p['x'], 1) . '" y2="' . COASTER_GROUND . '"/>' . "\n";
    }

    $motion = $c['ready']
        ? '<animateMotion dur="' . round($c['dur'], 1) . 's" repeatCount="indefinite" rotate="auto"'
          . ' keyPoints="0;1" keyTimes="0;1" calcMode="linear"><mpath href="#' . $id . '"/></animateMotion>'
        : '';
    $parked = $c['ready'] ? '' : ' transform="translate(300,' . COASTER_STATION . ')"';

    $out  = '<div class="coaster">' . "\n";
    $out .= '<svg viewBox="0 0 ' . COASTER_W . ' ' . COASTER_H . '" xmlns="http://www.w3.org/2000/svg" class="coaster-svg" role="img" aria-label="' . e(t('coaster_alt')) . '">' . "\n";
    $out .= '<defs><path id="' . e($id) . '" d="' . e($c['path']) . '"/></defs>' . "\n";
    $out .= '<g class="coaster-posts">' . "\n" . $posts;
    $out .= '<line x1="-20" y1="' . COASTER_GROUND . '" x2="920" y2="' . COASTER_GROUND . '" class="coaster-ground"/></g>' . "\n";
    $out .= '<use href="#' . e($id) . '" class="coaster-track"/>' . "\n";
    $out .= '<g class="coaster-cart"' . $parked . '>' . $veh . $motion . '</g>' . "\n";
    $out .= '</svg>' . "\n";
    if ($caption !== '') $out .= '<p class="coaster-caption">' . $caption . '</p>' . "\n";
    $out .= '</div>' . "\n";
    return $out;
}
