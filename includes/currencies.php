<?php
/**
 * Monedas soportadas.
 *
 * `cg` indica si CoinGecko entrega el precio de BTC directamente en esa moneda.
 * Para las que no, el precio local se deriva del precio en USD multiplicado por
 * el tipo de cambio de respaldo que el dueño mantiene en el panel.
 */
function currencies(): array {
    return [
        'MXN' => ['sym' => '$',  'name' => 'Peso mexicano',   'cg' => true,  'dec' => 2],
        'USD' => ['sym' => '$',  'name' => 'Dólar',           'cg' => true,  'dec' => 2],
        'EUR' => ['sym' => '€',  'name' => 'Euro',            'cg' => true,  'dec' => 2],
        'GBP' => ['sym' => '£',  'name' => 'Libra',           'cg' => true,  'dec' => 2],
        'CAD' => ['sym' => '$',  'name' => 'Dólar canadiense','cg' => true,  'dec' => 2],
        'BRL' => ['sym' => 'R$', 'name' => 'Real',            'cg' => true,  'dec' => 2],
        'ARS' => ['sym' => '$',  'name' => 'Peso argentino',  'cg' => true,  'dec' => 2],
        'CLP' => ['sym' => '$',  'name' => 'Peso chileno',    'cg' => true,  'dec' => 0],
        'COP' => ['sym' => '$',  'name' => 'Peso colombiano', 'cg' => false, 'dec' => 0],
        'PEN' => ['sym' => 'S/', 'name' => 'Sol',             'cg' => false, 'dec' => 2],
    ];
}

function currency_code(): string {
    $c = strtoupper(get_setting('currency', 'MXN'));
    return isset(currencies()[$c]) ? $c : 'MXN';
}

function currency_info(?string $code = null): array {
    $code = $code ?: currency_code();
    return currencies()[$code] ?? currencies()['MXN'];
}

function currency_symbol(?string $code = null): string {
    return currency_info($code)['sym'];
}

/** ¿La moneda del sitio es el dólar? Si sí, el tipo de cambio deja de tener sentido. */
function is_usd_site(): bool {
    return currency_code() === 'USD';
}
