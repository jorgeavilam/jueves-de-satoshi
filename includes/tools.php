<?php
/**
 * Catálogo de herramientas: dónde se compra y dónde se recibe.
 * El dueño elige una de cada tipo desde Admin → Herramientas, y la página
 * pública se arma con lo elegido. Las descripciones viven aquí por idioma
 * para no inflar los archivos de lang con textos de terceros.
 */
function exchange_catalog(): array {
    return [
        'aureo' => [
            'name' => 'Aureo Bitcoin', 'url' => 'https://www.aureobitcoin.com/es', 'x' => 'AureoBitcoin', 'icon' => '🪙',
            'es' => 'Plataforma mexicana para comprar Bitcoin de forma puntual. Su comisión ya incluye todos los fees y envía la compra directamente a tu wallet, por Lightning u on-chain. Matemática simple, sin sorpresas.',
            'en' => 'Mexican platform for one-off Bitcoin purchases. Its fee already includes everything and it sends the purchase straight to your wallet, over Lightning or on-chain. Simple math, no surprises.',
        ],
        'bitso' => [
            'name' => 'Bitso', 'url' => 'https://bitso.com', 'x' => 'Bitso', 'icon' => '🇲🇽',
            'es' => 'Exchange latinoamericano regulado, con depósitos en moneda local y retiros on-chain o Lightning. Útil si además quieres mover pesos con SPEI.',
            'en' => 'Regulated Latin American exchange with local-currency deposits and on-chain or Lightning withdrawals. Handy if you also move local money around.',
        ],
        'binance' => [
            'name' => 'Binance', 'url' => 'https://www.binance.com', 'x' => 'binance', 'icon' => '🟡',
            'es' => 'El exchange más grande del mundo por volumen. Comisiones bajas y muchas formas de pago, a cambio de una interfaz con muchas más opciones de las que este ejercicio necesita.',
            'en' => 'The largest exchange in the world by volume. Low fees and many payment methods, at the cost of an interface with far more options than this practice needs.',
        ],
        'kraken' => [
            'name' => 'Kraken', 'url' => 'https://www.kraken.com', 'x' => 'krakenfx', 'icon' => '🐙',
            'es' => 'Exchange con buena reputación de seguridad y retiros Lightning. Interfaz sobria y comisiones claras.',
            'en' => 'Exchange with a solid security reputation and Lightning withdrawals. Sober interface and clear fees.',
        ],
        'coinbase' => [
            'name' => 'Coinbase', 'url' => 'https://www.coinbase.com', 'x' => 'coinbase', 'icon' => '🔵',
            'es' => 'De los más sencillos para empezar y el más común en Estados Unidos. Sus comisiones en compras simples son más altas que la media.',
            'en' => 'One of the easiest places to start and the most common in the United States. Its fees on simple purchases run above average.',
        ],
        'strike' => [
            'name' => 'Strike', 'url' => 'https://strike.me', 'x' => 'strike', 'icon' => '⚡',
            'es' => 'Compras recurrentes de Bitcoin con retiro por Lightning y comisiones muy bajas. Pensado justo para acumular poco a poco.',
            'en' => 'Recurring Bitcoin buys with Lightning withdrawal and very low fees. Built precisely for accumulating little by little.',
        ],
        'lemon' => [
            'name' => 'Lemon', 'url' => 'https://www.lemon.me', 'x' => 'lemoncashapp', 'icon' => '🍋',
            'es' => 'App argentina para comprar Bitcoin con pesos y retirar por Lightning. Popular para compras chicas y frecuentes.',
            'en' => 'Argentine app to buy Bitcoin with local pesos and withdraw over Lightning. Popular for small, frequent buys.',
        ],
        'river' => [
            'name' => 'River', 'url' => 'https://river.com', 'x' => 'river', 'icon' => '🏞️',
            'es' => 'Servicio enfocado exclusivamente en Bitcoin, con compras recurrentes automáticas y retiro on-chain sin comisión.',
            'en' => 'Bitcoin-only service with automatic recurring buys and free on-chain withdrawals.',
        ],
    ];
}

function wallet_catalog(): array {
    return [
        'wos' => [
            'name' => 'Wallet of Satoshi', 'url' => 'https://www.walletofsatoshi.com/', 'x' => 'walletofsatoshi', 'icon' => '⚡', 'custodial' => true,
            'es' => 'Wallet Lightning sencillo y amigable. Ideal para empezar sin complicaciones técnicas: las compras llegan en segundos.',
            'en' => 'Simple, friendly Lightning wallet. Ideal to start with no technical fuss: purchases land in seconds.',
        ],
        'blink' => [
            'name' => 'Blink', 'url' => 'https://www.blink.sv/', 'x' => 'blinkbtc', 'icon' => '👁️', 'custodial' => true,
            'es' => 'Wallet Lightning nacido del proyecto de Bitcoin Beach en El Salvador. Fácil de usar y con cuenta en dólares aparte.',
            'en' => 'Lightning wallet born out of the Bitcoin Beach project in El Salvador. Easy to use, with a separate dollar account.',
        ],
        'phoenix' => [
            'name' => 'Phoenix', 'url' => 'https://phoenix.acinq.co/', 'x' => 'PhoenixWallet', 'icon' => '🔥', 'custodial' => false,
            'es' => 'Wallet Lightning de custodia propia: la comodidad de Lightning sin entregar tus llaves. Cobra fees de canal al recibir.',
            'en' => 'Self-custodial Lightning wallet: the convenience of Lightning without handing over your keys. Charges channel fees on receipt.',
        ],
        'muun' => [
            'name' => 'Muun', 'url' => 'https://muun.com/', 'x' => 'MuunWallet', 'icon' => '🌙', 'custodial' => false,
            'es' => 'Wallet de custodia propia que maneja on-chain y Lightning en un solo saldo, con respaldo por frase de recuperación.',
            'en' => 'Self-custodial wallet handling on-chain and Lightning in a single balance, backed up with a recovery phrase.',
        ],
        'bluewallet' => [
            'name' => 'BlueWallet', 'url' => 'https://bluewallet.io/', 'x' => 'bluewalletio', 'icon' => '💙', 'custodial' => false,
            'es' => 'Wallet de código abierto con soporte on-chain y Lightning, y opciones avanzadas para quien quiere aprender.',
            'en' => 'Open-source wallet with on-chain and Lightning support, plus advanced options for those who want to learn.',
        ],
        'trust' => [
            'name' => 'Trust Wallet', 'url' => 'https://trustwallet.com/', 'x' => 'TrustWallet', 'icon' => '🛡️', 'custodial' => false,
            'es' => 'Wallet multi-moneda de custodia propia. Práctico si ya lo usas para otras cosas, aunque no está enfocado en Bitcoin.',
            'en' => 'Self-custodial multi-currency wallet. Handy if you already use it for other things, though it is not Bitcoin-focused.',
        ],
        'phantom' => [
            'name' => 'Phantom', 'url' => 'https://phantom.com/', 'x' => 'phantom', 'icon' => '👻', 'custodial' => false,
            'es' => 'Wallet multi-cadena de custodia propia que agregó soporte de Bitcoin. Cómodo si ya vives en ese ecosistema.',
            'en' => 'Self-custodial multi-chain wallet that added Bitcoin support. Comfortable if you already live in that ecosystem.',
        ],
        'ledger' => [
            'name' => 'Ledger', 'url' => 'https://www.ledger.com/', 'x' => 'Ledger', 'icon' => '🔐', 'custodial' => false,
            'es' => 'Wallet de hardware: las llaves nunca salen del dispositivo. El estándar para montos que ya te quitarían el sueño.',
            'en' => 'Hardware wallet: the keys never leave the device. The standard once the amount would cost you sleep.',
        ],
        'trezor' => [
            'name' => 'Trezor', 'url' => 'https://trezor.io/', 'x' => 'Trezor', 'icon' => '🗄️', 'custodial' => false,
            'es' => 'Wallet de hardware de código abierto, de los más antiguos y auditados del mercado.',
            'en' => 'Open-source hardware wallet, one of the oldest and most audited on the market.',
        ],
        'tangem' => [
            'name' => 'Tangem', 'url' => 'https://tangem.com/', 'x' => 'Tangem', 'icon' => '💳', 'custodial' => false,
            'es' => 'Wallet de hardware en forma de tarjeta, sin batería ni pantalla. Se usa acercándola al teléfono.',
            'en' => 'Hardware wallet in card form, with no battery or screen. You tap it against your phone.',
        ],
        'sparrow' => [
            'name' => 'Sparrow', 'url' => 'https://sparrowwallet.com/', 'x' => 'SparrowWallet', 'icon' => '🐦', 'custodial' => false,
            'es' => 'Wallet de escritorio para usuarios avanzados: control total de las transacciones, coin control y conexión a tu propio nodo.',
            'en' => 'Desktop wallet for advanced users: full transaction control, coin control and a connection to your own node.',
        ],
    ];
}

/** Herramienta elegida, resuelta a un arreglo listo para pintar. Null si está apagada. */
function selected_tool(string $kind): ?array {
    $key = get_setting('tool_' . $kind, '');
    if ($key === 'none' || $key === '') return null;
    $cat = $kind === 'exchange' ? exchange_catalog() : wallet_catalog();
    $loc = current_locale();
    if ($key === 'other') {
        $name = get_setting('tool_' . $kind . '_name', '');
        if ($name === '') return null;
        return [
            'name'  => $name,
            'url'   => get_setting('tool_' . $kind . '_url', ''),
            'x'     => '',
            'icon'  => $kind === 'exchange' ? '🪙' : '⚡',
            'desc'  => get_setting('tool_' . $kind . '_desc', ''),
            'custodial' => null,
        ];
    }
    if (!isset($cat[$key])) return null;
    $tool = $cat[$key];
    return [
        'name' => $tool['name'],
        'url'  => $tool['url'],
        'x'    => $tool['x'] ?? '',
        'icon' => $tool['icon'],
        'desc' => $tool[$loc] ?? $tool['es'],
        'custodial' => $tool['custodial'] ?? null,
    ];
}
