<?php
// config
// long timeout for BTC.
$twoHours = 60 * 60 * 2;
define('HELIO_TIMEOUT', $twoHours);
define('HELIO_WOO_PLUGIN_VERSION', '2.0.0');


// post meta keys:
define('HELIO_TX_ID', 'helio_transaction_signature');
define('HELIO_CURRENCY_SYMBOL', 'helio_currency_symbol');
define('HELIO_BLOCKCHAIN', 'helio_blockchain');
define('HELIO_USDC_AMOUNT', 'helio-usdc-amount');
define('HELIO_ORDER_TOTAL', 'helio-order-total');
define('HELIO_ORDER_CURRENCY', 'helio-order-currency');
define('HELIO_QUOTED_AT', 'helio-quoted-at');
define('HELIO_NONCE', 'helio-nonce');

// config options
define('HELIO_LOGGING_ENABLED', 'logging');
define('HELIO_DEVNET_ENABLED', 'is_devnet');
define('HELIO_MODE_THEME', 'mode_theme');

define('HELIO_PAYLINK_ID_DEVNET', 'paylink_id_devnet');
define('HELIO_PAYLINK_ID_MAINNET', 'paylink_id');

define('HELIO_API_KEY_DEVNET', 'helio_api_key_devnet');
define('HELIO_API_KEY_MAINNET', 'helio_api_key');
define('HELIO_API_SECRET_DEVNET', 'helio_api_secret_devnet');
define('HELIO_API_SECRET_MAINNET', 'helio_api_secret');

define('HELIO_WOO_NONCE', 'woocommerce-process_checkout');
