<?php
include 'core/Exchange.php';

class bitstamp extends Exchange {

    public function __construct($options=array()){
        parent::__construct($options);
        $this->requiredCredentials = array(
            'api_key' => true,
            'api_secret' => true,
            'uid' => false,
            'login' => false,
            'password' => false,
            'twofa' => false, // 2-factor authentication(one-time password key)
        );
    }
    
    public function get_ticker($pair = "ALL") {
        return $this->fetch_ticker($pair);        
    }
    
    public function get_my_last_trade($pair){
        $pair = strtolower($pair);
        // POST Call to the private API /user_transactions/{pair}
        $response = $this->privatePostUserTransactionsPair (array (
            'pair' => str_replace('_', '', $pair),
            'limit' => 1,
         ));
        return $this->parse_trades($response, $pair, null, null);
    }

    public function sell($pair, $rate, $amount){
        $pair = strtolower($pair);
        return $this->create_order($pair, 'limit', 'sell', $amount, $rate);
    }

    public function buy($pair, $rate, $amount){
        $pair = strtolower($pair);
        return $this->create_order($pair, 'limit', 'buy', $amount, $rate);
    }

    public function get_order_book($pair){
        $pair = strtolower($pair);
        return $this->fetch_order_book($pair);
    }

    public function get_balances(){
        return $this->fetch_balance();
    }
    
    public function describe() {
        return array_replace_recursive (parent::describe(), array (
            'id' => 'bitstamp',
            'name' => 'BITSTAMP',
            'countries' => array ( 'JP', 'SG', 'VN' ),
            'version' => '2',
            'rateLimit' => 1000,
            'proxy' => 'https://www.bitstamp.net/api/v2',
            'proxy_v1' => 'https://www.bitstamp.net/api',
            'has' => array (
                'CORS' => false,
                'fetchTickers' => true,
                'fetchOrder' => true,
                'fetchOrders' => true,
                'fetchOpenOrders' => true,
                'fetchClosedOrders' => true,
                'fetchMyTrades' => true,
                'withdraw' => true,
            ),
            'urls' => array (
                'logo' => 'https://www.bitstamp.net/s/images/Bitstamp_XS_cropped.pngg',
                'api' => 'https://www.bitstamp.net/api/v2',
                'www' => 'https://www.bitstamp.net',
                'doc' => array (
                    'https://www.bitstamp.net/api/',
                ),
                'fees' => 'https://www.bitstamp.net/fee_schedule/',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'ticker/{pair}',
                        'transactions/{pair}',
                        'order_book/{pair}',
                    ),
                ),
                'private' => array (
                    'get' => array (
                    ),
                    'post' => array (
                        'balance/',
                        'balance/{pair}/',
                        'user_transactions/',
                        'user_transactions/{pair}/',
                        'open_orders/all/',
                        'open_orders/{pair}/',
                        'cancel_order/',
                        'cancel_all_orders/',
                        'buy/{pair}/',
                        'buy/market/{pair}/',
                        'sell/{pair}/',
                        'sell/market/{pair}/',
                        'bitcoin_withdrawal/',
                        'ltc_withdrawal/',
                        'eth_withdrawal/',
                        'eth_address/',
                        'bitcoin_deposit_address/',
                        'ltc_address/',
                        'bch_withdrawal/',
                        'bch_address/',
                        'xrp_withdrawal/',
                        'xrp_address/',
                        'withdrawal/open/',
                    ),
                    'put' => array (
                    ),
                ),
            ),
            'skipJsonOnStatusCodes' => [401],
            'exceptions' => array (
                'messages' => array (
                    'API Authentication failed' => '\\bcxt\\AuthenticationError',
                    'Nonce is too small' => '\\bcxt\\InvalidNonce',
                    'Order not found' => '\\bcxt\\OrderNotFound',
                    'user' => array (
                        'not_enough_free_balance' => '\\bcxt\\InsufficientFunds',
                    ),
                    'price' => array (
                        'must_be_positive' => '\\bcxt\\InvalidOrder',
                    ),
                    'quantity' => array (
                        'less_than_order_size' => '\\bcxt\\InvalidOrder',
                    ),
                ),
            ),
            'commonCurrencies' => array (
                'WIN' => 'WCOIN',
            ),
        ));
    }

    /**
     * {@inheritDoc}
     * @see \bcxt\Exchange::fetch_balance()
     * @desc get account balances
     * @author Jean-Charles Duhail
     */
    public function fetch_balance ($params = array()) {
        // POST Call to the private API /balance/
        $balances = $this->privatePostBalance ($params);
        $result = array ( 'info' => $balances );
        if(count($balances)>0){
            foreach($balances as $key => $val){
                $split = explode('_', $key);
                if(count($split)>1 && $split[1]!='balance') continue;
                $code = strtoupper($split[0]);
                $total = floatval ($val);
                $result[$code] = $total;
            }
        }
        return $result;
    }
    
    /**
     * @name fetch_order_book
     * @desc call to the public GET API order book function
     * @param string $symbol
     * @param integer $limit
     * @param array $params
     * @return array
     * @author Jean-Charles Duhail
     */
    public function fetch_order_book ($symbol, $limit = null, $params = array()) {
        $orderbook = $this->publicGetOrderBookPair (array_merge (array (
            'pair' => str_replace('_', '', $symbol),
        ), $params));
        return $this->parse_order_book($orderbook);
    }

    /**
     * @name parse_ticker
     * @desc parse the return of the API call to match our system
     * @param array $ticker
     * @param array $market
     * @param string $symbol
     * @author Jean-Charles Duhail
     * @return array
     */
    public function parse_ticker ($ticker, $market = null, $symbol = null) {
        $timestamp = $this->milliseconds();
        $last = null;
        if (is_array ($ticker) && array_key_exists ('last', $ticker)) {
            if ($ticker['last']) {
                     $last = $this->safe_float($ticker, 'last');
            }
        }

        if ($market === null && $symbol === null) {
            $marketId = $this->safe_string($ticker, 'id');
            if (is_array ($this->markets_by_id) && array_key_exists ($marketId, $this->markets_by_id)) {
                $market = $this->markets_by_id[$marketId];
            } else {
                $baseId = $this->safe_string($ticker, 'base_currency');
                $quoteId = $this->safe_string($ticker, 'quoted_currency');
                $base = $this->common_currency_code($baseId);
                $quote = $this->common_currency_code($quoteId);
                if (is_array ($this->markets) && array_key_exists ($symbol, $this->markets)) {
                    $market = $this->markets[$symbol];
                } else {
                    $symbol = $base . '/' . $quote;
                }
            }
        }
        if ($market !== null)
            $symbol = $market['symbol'];
        $change = null;
        $percentage = null;
        $average = null;
        $open = $this->safe_float($ticker, 'open');
        if ($open !== null && $last !== null) {
            $change = $last - $open;
            $average = $this->sum ($last, $open) / 2;
            if ($open > 0) {
                $percentage = $change / $open * 100;
            }
        }

        return array (
            'id' => $symbol,
            'last' => $this->safe_float($ticker, 'last'),
            'lowestAsk' => $this->safe_float($ticker, 'ask'),
            'highestBid' => $this->safe_float($ticker, 'bid'),
            'percentChange' => $percentage,
            'baseVolume' => $this->safe_float($ticker, 'volume'),
            'quoteVolume' => null,
            'isFrozen' => null,
            'high24hr' => null,
            'low24hr' => null,
            'info' => $ticker,
        );
    }

    /**
     * @name fetch_ticker
     * @desc get a pair informations
     * @param string $symbol
     * @param array $params
     * @author Jean-Charles Duhail
     * @return array
     */
    public function fetch_ticker ($symbol, $params = array()) {
        // GET Call to the public API /ticker/{pair}
        $ticker = $this->publicGetTickerPair (array_merge (array (
            'pair' => strtolower(str_replace('_', '', $symbol)),
        ), $params));
        return $this->parse_ticker($ticker,null,$symbol);
    }

    /**
     * @name parse_trade
     * @desc parse the trade API result
     * @param array $trade
     * @param array $market
     * @return array
     * @author Jean-Charles Duhail
     */
    public function parse_trades($trades, $market = null, $since = null, $limit = null) {
        
        $money = explode('_', $market)[1];
        $type = $trades[0][$money] < 0 ? 'buy' : 'sell';
        
        return [0=>
            ['tradeID' => (string) $trades[0]['order_id'],
            'type' => $type,
            'rate' => $this->safe_float($trades[0], $market),
            'amount' => $this->safe_float($trades[0], $money),
            'info' => $trades]
            ];
    }

    /**
     * @name fetch_trades
     * @desc call the public API transaction history
     * @param string $symbol
     * @param integer $since
     * @param integer $limit
     * @param array $params
     * @return array
     * @author Jean-Charles Duhail
     */
    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array()) {
        $market = array('symbol'=>str_replace('/', '', $symbol));
        // GET Call to the public API /ticker/{pair}
        $response = $this->publicGetTransactionsPair (array_merge (array (
            'pair' => $market['symbol'],
        ), $params));
        
        return $this->parse_trades($response, $market, $since, $limit);
    }

    /**
     * @name fetch_my_trades
     * @desc call the private API user transaction history
     * @param string $symbol
     * @param integer $since
     * @param integer $limit
     * @param array $params
     * @return array
     * @author Jean-Charles Duhail
     */
    public function fetch_my_trades ($symbol = null, $since = null, $limit = null, $params = array()) {
        $market = array('symbol'=>str_replace('_', '', $symbol));
        // POST Call to the private API /user_transactions/{pair}
        $response = $this->privatePostUserTransactions ();
        /*$response = $this->privatePostUserTransactionsPair (array_merge (array (
            'pair' => $market['symbol'],
        ), $params));
        */
        return $this->parse_trades($response, $market, $since, $limit);
    }

    /**
     * {@inheritDoc}
     * @desc create an order
     * @author Jean-Charles Duhail
     */
    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array()) {
        
        $order = array (
            'pair' => strtolower(str_replace('_', '', $symbol)),
            'amount' => $amount,
        );
        
        if($side=='buy'){
            switch($type){
                case 'limit':
                    $order['price'] = $price;
                    $response = $this->privatePostBuyPair (array_merge ($order, $params));
                    break;
                case 'market':
                    $order['price'] = $price;
                    $response = $this->privatePostBuyMarketPair (array_merge ($order, $params));
                    break;
                default:
                    break;
            }
        }else{
            switch($type){
                case 'limit':
                    $order['price'] = $price;
                    $response = $this->privatePostSellPair (array_merge ($order, $params));
                    break;
                case 'market':
                    $response = $this->privatePostSellMarketPair (array_merge ($order, $params));
                    break;
                default:
                    break;
            }
        }
        
        return $this->parse_order($response);
    }

    /**
     * {@inheritDoc}
     * @desc cancel a order by its ID
     * @author Jean-Charles Duhail
     */
    public function cancel_order ($symbol = null, $id, $params = array()) {
        $result = $this->privatePostCancelOrder (array_merge (array (
            'id' => $id,
        ), $params));
        $order = $this->parse_order($result);
        if ($order['status'] === 'closed')
            throw new OrderNotFound ($this->id . ' ' . $this->json ($order));
        return $order;
    }

    /**
     * @name cancel_orders
     * @desc cancel all orders, exists only in v1 api
     * @author Jean-Charles Duhail
     * @return boolean
     */
    public function cancel_orders(){
        $proxy = $this->proxy;
        $this->proxy = $this->proxy_v1;
        $res = $this->privatePostCancelAllOrders ();
        $this->proxy = $proxy;
        return $res;
    }
    
    /**
     * @name parse_order
     * @desc parse order to fit our system
     * @param array $order
     * @param array $market
     * @return array
     * @auth Jean-Charles Duhail
     */
    public function parse_order ($order, $market = null) {
        return array (
            'orderNumber' => (string) $order['id'],
            'info' => $order,
        );
    }

    /**
     * {@inheritDoc}
     * @see \bcxt\Exchange::fetch_orders()
     * @desc get orders from private API
     * @author Jean-Charles Duhail 
     */
    public function fetch_orders ($symbol = null, $since = null, $limit = null, $params = array()) {
        $request = array();
        $market = array('symbol' => $symbol);
        $status = $this->safe_value($params, 'status');
        if ($status) {
            $params = $this->omit ($params, 'status');
            if ($status === 'open') {
                $request['status'] = 'live';
            } else if ($status === 'closed') {
                $request['status'] = 'filled';
            } else if ($status === 'canceled') {
                $request['status'] = 'cancelled';
            }
        }
        if ($limit !== null)
            $request['limit'] = $limit;
        
        if($symbol == null){
            $result = $this->privatePostOpenOrdersAll (array_merge ($request, $params));
        }else{
            $request = array ('pair' => strtolower(str_replace('/', '', $symbol)));
            $result = $this->privatePostOpenOrdersPair (array_merge ($request, $params));
        }
        return $this->parse_orders($result, $market, $since, $limit);
    }

    /**
     * @name withdraw
     * @desc withrawal functions
     * @param string $code
     * @param float $amount
     * @param string $address
     * @param string $tag
     * @param array $params
     * @return array
     * @author Jean-Charles Duhail
     */
    public function withdraw ($code, $amount, $address, $tag = null, $params = array ()) {
        $this->check_address($address);
        $request = array (
            'address' => $address,
            'amount' => floatval ($amount),
        );
        
        switch($code){
            case 'BTC':
                $response = $this->privatePostBitcoinWithdrawal (array_merge ($request, $params));
                break;
            case 'LTC':
                $response = $this->privatePostLtcWithdrawal (array_merge ($request, $params));
                break;
            case 'ETH':
                $response = $this->privatePostEthWithdrawal (array_merge ($request, $params));
                break;
            case 'XRP':
                $response = $this->privatePostXrpWithdrawal (array_merge ($request, $params));
                break;
            case 'BCH':
                $response = $this->privatePostBchWithdrawal (array_merge ($request, $params));
                break;
            default:
                break;
        }
        
        return array (
            'info' => $response,
            'id' => $this->safe_string($response, 'id'),
        );
    }

    /**
     * @name fetch_deposit_address
     * @desc get deposit address
     * @param string $code
     * @param array $params
     * @return array
     * @author Jean-Charles Duhail
     */
    public function fetch_deposit_address ($code, $params = array ()) {

        switch($code){
            case 'BTC':
                $this->proxy = 'https://www.bitstamp.net/api'; // This not in API v2
                $response = $this->privatePostBitcoinDepositAddress ($params);
                break;
            case 'LTC':
                $response = $this->privatePostLtcAddress ($params);
                break;
            case 'ETH':
                $response = $this->privatePostEthAddress ($params);
                break;
            case 'XRP':
                $response = $this->privatePostXrpAddress ($params);
                break;
            case 'BCH':
                $response = $this->privatePostBchAddress ($params);
                break;
            default:
                break;
        }

        if (is_array ($response)) {
            $address = $this->safe_string($response, 'address');
            $tag = $this->safe_string($response, 'destination_tag');
        }else{
            $address = $response;
            $tag = '';
        }
        
        return array (
            'currency' => $code,
            'address' => $this->check_address($address),
            'tag' => $tag,
            'info' => $response,
        );
        
    }
    
    
    public function nonce() {
        //return $this->milliseconds();
        return $this->microseconds();
    }

    /**
     * {@inheritDoc}
     * @see \bcxt\Exchange::sign()
     * @desc connexion to the API
     * @auth Jean-Charles Duhail
     */
    public function sign ($path, $api = 'public', $method = 'GET', $params = array(), $headers = null, $body = null) {
        $url = '/' . $this->implode_params($path, $params);
        $query = $this->omit ($params, $this->extract_params($path));
        $headers = array (
            'X-Bitstamp-API-Version' => $this->version,
            'Content-Type' => 'application/json',
        );
        if ($api === 'public') {
            if ($query)
                $url .= '?' . $this->urlencode ($query);
        } else if (($api === 'private') || ($api === 'wapi')) {
            $this->check_required_credentials();
            $nonce = $this->nonce();
            $signature = strtoupper($this->hmac ($nonce.$this->api_id.$this->api_key, $this->api_secret));
            $query = $this->urlencode (array_merge (array (
                'key' => $this->api_key,
                'signature' => $signature,
                'nonce' => $nonce,
            ), $params));
            $headers = array ();
            if (($method === 'GET') || ($method === 'DELETE') || ($api === 'wapi')) {
                $url .= '?' . $query;
            } else {
                $body = $query;
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
        } else {
            if ($params)
                $url .= '?' . $this->urlencode ($params);
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body, $response = null) {
        if ($code >= 200 && $code <= 299)
            return;
        $messages = $this->exceptions['messages'];
        if ($code === 401) {
            // expected non-json $response
            if (is_array ($messages) && array_key_exists ($body, $messages))
                throw new $messages[$body] ($this->id . ' ' . $body);
            else
                return;
        }
        if ($response === null)
            if (($body[0] === '{') || ($body[0] === '['))
                $response = json_decode ($body, $as_associative_array = true);
            else
                return;
        $feedback = $this->id . ' ' . $this->json ($response);
        if ($code === 404) {
            // array ( "$message" => "Order not found" )
            $message = $this->safe_string($response, 'message');
            if (is_array ($messages) && array_key_exists ($message, $messages))
                throw new $messages[$message] ($feedback);
        } else if ($code === 422) {
            // array of error $messages is returned in 'user' or 'quantity' property of 'errors' object, e.g.:
            // array ( "$errors" => { "user" => ["not_enough_free_balance"] )}
            // array ( "$errors" => { "quantity" => ["less_than_order_size"] )}
            if (is_array ($response) && array_key_exists ('errors', $response)) {
                $errors = $response['errors'];
                $errorTypes = ['user', 'quantity', 'price'];
                for ($i = 0; $i < count ($errorTypes); $i++) {
                    $errorType = $errorTypes[$i];
                    if (is_array ($errors) && array_key_exists ($errorType, $errors)) {
                        $errorMessages = $errors[$errorType];
                        for ($j = 0; $j < count ($errorMessages); $j++) {
                            $message = $errorMessages[$j];
                            if (is_array ($messages[$errorType]) && array_key_exists ($message, $messages[$errorType]))
                                throw new $messages[$errorType][$message] ($feedback);
                        }
                    }
                }
            }
        }
    }
}