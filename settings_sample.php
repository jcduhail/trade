<?php
//max log file size in bytes
define('max_log_size','1000000');
//max number of log files in rotation
define('log_rotate','0');

ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/log/php-error.log");

define('action_log', __DIR__ . '/log/action.log');
define('error_log', __DIR__ . '/log/error.log');
define('transaction_log', __DIR__ . '/log/transaction.log');
define('market_log', __DIR__ . '/log/market.log');
define('data_log', __DIR__ . '/log/data.txt');
define('satochi_log', __DIR__ .'/log/satochi.txt');
define('gains_log', __DIR__ .'/log/gains.txt');

//number of graph data points to store
define('graph_data_points_max',30*24*4);

// number of iteration used for average_price
define('avg_number',180);

define('force_buy_at',0.00000000);
define('force_sell_at',0.00000000);

// name of the function to call to calc threshold
//define('theshold_method','get_average_price');
define('theshold_method','get_median_price');
// name of the function to call script method
define('script_method','script_v1');
//define('script_method','script_v2');

// The variation in percentage of coin value to put new orders (sell at average_price + satochi_varation_rate ; buy at average_price - satochi_varation_rate
define('variation_rate',0.70);
// The MAX variation in percentage of coin value to put new orders (sell at average_price + satochi_varation_rate ; buy at average_price - satochi_varation_rate
define('max_variation_rate',1.4);

// Number of decimal allowed for the api (example, usually 8 for satochi, 5 for EURUSD in bitstamp)
define('nb_decimal',5);

// name of the function to call to calc delta
define('delta_method','simple_delta');
//define('delta_method','get_delta');

// Define the max value of difference for loss sell
define('max_loss',0.5);

define('money_in','USD');
define('money','EUR');
define('max_sale',1000);
define('poloniex_key', '');
define('poloniex_secret', '');
define('poloniex_apid', '');
define('bitstamp_key', '');
define('bitstamp_secret', '');
define('bitstamp_apid', '');

define('dev_mode',1);
define('debug_mode',1);
// If debug mode + verbose mode then the debug will be printed
define('verbose_mode',1);
// If want to put buy order at the same first highest buy orders in order book (sum > 2 BTC)
define('focus_higher_buy',0);
?>
