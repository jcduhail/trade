<?php
//max log file size in bytes
define('max_log_size','1000');
//max number of log files in rotation
define('log_rotate','2');
define('action_log','log/action.log');
define('error_log','log/error.log');
define('transaction_log', 'log/transaction.log');
define('market_log', 'log/market.log');
define('data_log', 'log/data.txt');
define('satochi_log', 'log/satochi.txt');
define('gains_log', 'log/gains.txt');

//number of graph data points to store
define('graph_data_points_max',96*12);

// number of iteration used for average_price
define('avg_number',5);

// name of the function to call to calc threshold
define('theshold_method','get_average_price');
//define('theshold_method','get_median_price');


// The variation of satochi in percentage of coin value to put new orders (sell at average_price + satochi_varation_rate ; buy at average_price - satochi_varation_rate
define('satochi_variation_rate',1);
// The MAX variation of satochi in percentage of coin value to put new orders (sell at average_price + satochi_varation_rate ; buy at average_price - satochi_varation_rate
define('satochi_max_variation_rate',3);

// name of the function to call to calc delta
define('delta_method','simple_delta');
//define('delta_method','get_delta');
// name of the function to call script method
define('script_method','script_v1');
//define('script_method','script_v2');

// Define the max value of difference for loss sell
define('max_loss',0.00000003);

define('force_buy_at',0.00000000);
define('force_sell_at',0.00000000);
define('money','DOGE');
define('max_sale',1000);
define('key', '');
define('secret', '');
define('dev_mode',0);
define('debug_mode',0);
?>
