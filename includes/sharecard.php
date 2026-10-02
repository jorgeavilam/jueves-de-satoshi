<?php
/**
 * Imagen para compartir (og:image) de 1200×630.
 *
 * Como el riel de la montaña rusa, la tarjeta sale de los datos de ESTA
 * instalación: su nombre, su acento y la curva de precios de sus compras. Dos
 * sitios no comparten imagen aunque usen el mismo código.
 *
 * Se dibuja al doble de tamaño y se reduce al final: GD no suaviza líneas
 * gruesas ni rellenos, y el reescalado hace ese trabajo.
 *
 * La fuente es Inter (SIL OFL 1.1, includes/fonts/OFL.txt). Vive en includes/
 * porque solo la lee el servidor.
 */

const OG_W     = 1200;
const OG_H     = 630;
const OG_SCALE = 2;

function og_font(string $weight): string {
    return __DIR__ . '/fonts/Inter-' . ($weight === 'bold' ? 'ExtraBold' : 'Regular') . '.ttf';
}

/** El hosting tiene lo necesario: GD con FreeType y las dos fuentes. */
function og_card_available(): bool {
    return function_exists('imagecreatetruecolor') && function_exists('imagettftext')
        && is_readable(og_font('bold')) && is_readable(og_font('regular'));
}

/** Un acento muy oscuro desaparece sobre el fondo: se aclara lo necesario. */
function og_accent(string $hex): array {
    [$r, $g, $b] = hex_to_rgb($hex);
    $lum = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    if ($lum < 0.35) {
        $k = (0.35 - $lum) / 0.65 + 0.25;
        $r += (255 - $r) * $k; $g += (255 - $g) * $k; $b += (255 - $b) * $k;
    }
    return [(int)$r, (int)$g, (int)$b];
}

/** Texto con la línea base en $y. $size en px de la tarjeta (sin escalar). */
function og_text($im, float $size, int $x, int $y, int $color, string $font, string $text, string $align = 'left'): void {
    $s = $size * OG_SCALE * 0.75; // GD mide en puntos a 96 dpi
    $box = imagettfbbox($s, 0, $font, $text);
    $w = $box[2] - $box[0];
    $px = $x * OG_SCALE;
    if ($align === 'right') $px -= $w;
    imagettftext($im, $s, 0, (int)$px, $y * OG_SCALE, $color, $font, $text);
}

/** Tamaño mayor que deja caber el texto en $maxW px, sin bajar de $min. */
function og_fit(string $font, string $text, float $size, float $maxW, float $min): float {
    while ($size > $min) {
        $box = imagettfbbox($size * 0.75, 0, $font, $text);
        if ($box[2] - $box[0] <= $maxW) break;
        $size -= 2;
    }
    return $size;
}

/** Catmull-Rom muestreado: la curva pasa por cada precio, sin picos de polilínea. */
function og_curve(array $pts, int $steps = 10): array {
    $n = count($pts);
    if ($n < 3) return $pts;
    $out = [];
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)]; $p1 = $pts[$i]; $p2 = $pts[$i + 1]; $p3 = $pts[min($n - 1, $i + 2)];
        for ($k = 0; $k < $steps; $k++) {
            $t = $k / $steps; $t2 = $t * $t; $t3 = $t2 * $t;
            $out[] = [
                0.5 * (2 * $p1[0] + (-$p0[0] + $p2[0]) * $t + (2 * $p0[0] - 5 * $p1[0] + 4 * $p2[0] - $p3[0]) * $t2 + (-$p0[0] + 3 * $p1[0] - 3 * $p2[0] + $p3[0]) * $t3),
                0.5 * (2 * $p1[1] + (-$p0[1] + $p2[1]) * $t + (2 * $p0[1] - 5 * $p1[1] + 4 * $p2[1] - $p3[1]) * $t2 + (-$p0[1] + 3 * $p1[1] - 3 * $p2[1] + $p3[1]) * $t3),
            ];
        }
    }
    $out[] = $pts[$n - 1];
    return $out;
}

/**
 * Puntos del riel dentro de la banda [$top, $bottom]: precio alto, riel alto.
 * Usa la misma regla que la montaña rusa: las últimas 52 compras, suavizadas
 * si son muchas, y una vía plana mientras no haya recorrido.
 */
function og_rail(array $prices, int $x0, int $x1, int $top, int $bottom): array {
    $prices = array_values(array_filter(array_map('floatval', $prices), fn($p) => $p > 0));
    if (count($prices) > COASTER_MAX_PTS) $prices = array_slice($prices, -COASTER_MAX_PTS);
    $mid = (int)(($top + $bottom) / 2);
    if (count($prices) < COASTER_MIN_PTS) return [[$x0, $mid], [$x1, $mid]];
    $v = coaster_smooth($prices);
    $min = min($v); $max = max($v); $span = $max - $min ?: 1;
    $n = count($v); $pts = [];
    foreach ($v as $i => $p) {
        $pts[] = [$x0 + ($x1 - $x0) * $i / ($n - 1), $bottom - ($bottom - $top) * ($p - $min) / $span];
    }
    return og_curve($pts);
}

function og_polygon($im, array $flat, int $color): void {
    // PHP 8 dejó opcional el número de puntos y 8.1 lo marcó como obsoleto
    if (PHP_VERSION_ID >= 80000) imagefilledpolygon($im, $flat, $color);
    else imagefilledpolygon($im, $flat, (int)(count($flat) / 2), $color);
}

/**
 * Dibuja la tarjeta y devuelve el PNG.
 *
 * $c: site, subtitle, big, tone (pos|neg|text), line, prices, host, owner, accent.
 * Si `big` viene vacío, la tarjeta muestra `line` como mensaje principal.
 */
function og_render(array $c): string {
    $W = OG_W * OG_SCALE; $H = OG_H * OG_SCALE;
    $im = imagecreatetruecolor($W, $H);
    imagealphablending($im, true);

    [$ar, $ag, $ab] = og_accent($c['accent'] ?? '#F7931A');
    $bg     = imagecolorallocate($im, 0x14, 0x12, 0x10);
    $text   = imagecolorallocate($im, 0xF2, 0xED, 0xE6);
    $soft   = imagecolorallocate($im, 0xB2, 0xA9, 0x9D);
    $accent = imagecolorallocate($im, $ar, $ag, $ab);
    $tones  = [
        'pos'  => imagecolorallocate($im, 0x2B, 0xC4, 0x6A),
        'neg'  => imagecolorallocate($im, 0xE5, 0x57, 0x4A),
        'text' => $text,
    ];
    imagefilledrectangle($im, 0, 0, $W, $H, $bg);
    imagefilledrectangle($im, 0, 0, $W, 10 * OG_SCALE, $accent);

    $bold = og_font('bold'); $reg = og_font('regular');
    $pad = 72; $maxW = OG_W - 2 * $pad;

    // Encabezado: nombre del sitio y de qué trata esta página
    $site = (string)$c['site'];
    og_text($im, og_fit($bold, $site, 58, $maxW, 34), $pad, 118, $text, $bold, $site);
    $sub = (string)($c['subtitle'] ?? '');
    if ($sub !== '') og_text($im, og_fit($reg, $sub, 30, $maxW, 20), $pad, 170, $soft, $reg, $sub);

    // La cifra que se comparte
    $big = (string)($c['big'] ?? '');
    $line = (string)($c['line'] ?? '');
    if ($big !== '') {
        og_text($im, og_fit($bold, $big, 128, $maxW, 64), $pad, 318, $tones[$c['tone'] ?? 'text'] ?? $text, $bold, $big);
        if ($line !== '') og_text($im, og_fit($reg, $line, 30, $maxW, 18), $pad, 372, $text, $reg, $line);
    } elseif ($line !== '') {
        og_text($im, og_fit($bold, $line, 48, $maxW, 24), $pad, 300, $text, $bold, $line);
    }

    // El riel: el recorrido de precios de esta instalación
    $rail = og_rail($c['prices'] ?? [], $pad, OG_W - $pad, 418, 540);
    $flat = [];
    foreach ($rail as [$x, $y]) { $flat[] = (int)($x * OG_SCALE); $flat[] = (int)($y * OG_SCALE); }
    $fill = $flat;
    array_push($fill, (int)((OG_W - $pad) * OG_SCALE), 560 * OG_SCALE, $pad * OG_SCALE, 560 * OG_SCALE);
    og_polygon($im, $fill, imagecolorallocatealpha($im, $ar, $ag, $ab, 100));
    $th = 7 * OG_SCALE;
    imagesetthickness($im, $th);
    for ($i = 2; $i < count($flat); $i += 2) {
        imageline($im, $flat[$i - 2], $flat[$i - 1], $flat[$i], $flat[$i + 1], $accent);
    }
    imagesetthickness($im, 1);
    for ($i = 0; $i < count($flat); $i += 2) {
        imagefilledellipse($im, $flat[$i], $flat[$i + 1], $th, $th, $accent); // uniones redondas
    }
    $last = end($rail);
    imagefilledellipse($im, (int)($last[0] * OG_SCALE), (int)($last[1] * OG_SCALE), 22 * OG_SCALE, 22 * OG_SCALE, $accent);
    imagefilledellipse($im, (int)($last[0] * OG_SCALE), (int)($last[1] * OG_SCALE), 10 * OG_SCALE, 10 * OG_SCALE, $bg);

    // Pie: dónde verlo y de quién es
    og_text($im, 24, $pad, 604, $soft, $reg, (string)($c['host'] ?? ''));
    if (!empty($c['owner'])) og_text($im, 24, OG_W - $pad, 604, $soft, $reg, (string)$c['owner'], 'right');

    $out = imagecreatetruecolor(OG_W, OG_H);
    imagecopyresampled($out, $im, 0, 0, 0, 0, OG_W, OG_H, $W, $H);
    ob_start();
    imagepng($out, null, 6);
    return (string)ob_get_clean();
}
