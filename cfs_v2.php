<?php
// crypto froth scrapper api class
include 'poloniex_api.php';
include 'bitstamp.php';
include 'settings.php';
include 'Logger.php';
include 'Graph.php';
include 'Data.php';
class cfs_v2 extends poloniex{
use Logger;
    
    protected $money;
    protected $nbtries;
    protected $graph;
    protected $satochi_graph;
    protected $gains_graph;
    static  $satochi = 100000000;
    protected $data;
    protected $lastOrders;
    private $apiObj;
    
    public function __construct($apiClass) {

        $this->apiObj = new $apiClass(
            ['api_key'=>constant($apiClass.'_key'),
             'api_secret'=>constant($apiClass.'_secret'),
             'api_id'=>constant($apiClass.'_apid')
            ]);
        $this->init();
        $this->money = money;
		$this->nbtries = 0;
			
        $this->graph = new Graph(data_log);
        $this->satochi_graph = new Graph(satochi_log);
        $this->gains_graph = new Graph(gains_log);
        $this->data = new Data();
	}
		
	public function __destruct(){
        $this->close();
		 
	}

	public function launch(){
		$this->{script_method}();
	}

	public function get_high_buy_order(){
		$order_book = $this->apiObj->get_order_book(money.'_'.money_in);
		foreach($order_book['bids'] as $order){
			if($order[0] * $order[1] > 2){
				return $order[0];
			}
		}
		return false;
	}
	
	public function script_v1(){
			$this->nbtries++;
		
		$myLastTrade = $this->get_my_last_trade(money.'_'.money_in);
		
		//$this->calc_and_log_gains($myLastTrade);
		
		
		$market = $this->get_ticker(money.'_'.money_in);
	
		if(method_exists($this->apiObj, 'cancel_orders')){
		    // Cancel all orders
		    $this->apiObj->cancel_orders();
		}else{
		    // Cance one by one
		    $myOrders = $this->apiObj->get_open_orders(money.'_'.money_in);
		    
		    if($myOrders && !dev_mode){
		        foreach($myOrders as $order){
		            $this->apiObj->cancel_order(money.'_'.money_in, $order['orderNumber']);
		        }
		    }
		}
		
		$myBalances = $this->get_balances();
		
		$myMoney = $myBalances[money];
		print 'mymoney = '.$myMoney.PHP_EOL;
		
		$myMoneyIn = $myBalances[money_in];
		
        //$satochi_value = number_format($myMoneyIn + $myMoney*$market['highestBid'],8);
        //$this->satochi_graph->saveValue(array($satochi_value,$myMoneyIn,$myMoney*$market['highestBid']));
		
		// Determine if need to recalibrate the threshold
		$average_price = $this->{theshold_method}($myLastTrade[0]['type'], $market);
		
		print 'average price = '.$average_price.PHP_EOL;
		
        //$this->graph->saveValue(array($market['highestBid'],$market['lowestAsk'],$average_price));
		
		
		if($myLastTrade[0]['type']=='buy'){
            $delta = $this->{delta_method}('buy',$average_price,$market['highestBid']);
            print 'delta = '.$delta.PHP_EOL;
            $rate = number_format($average_price+$delta,nb_decimal);
            print 'rate = '.$rate.PHP_EOL;
            if($myLastTrade[0]['rate']==$rate) $rate = $rate+0.01;

			if(max_loss == 0 || $myLastTrade[0]['rate']-$rate < max_loss){
				// If the current highest big is higher than our calculated rate, we sell at the highest rate
				if($market['highestBid']>$rate) $rate = $market['highestBid'];
				if($rate*$myMoney>0.0001){
					// If the selling price is higher than our previous sell + %satochi_varation_rate, we don't try to sell to much high
					if(($myLastTrade[0]['rate'] + $delta) < $market['highestBid']) $rate = $market['highestBid']; 
					$this->sell(money.'_'.money_in, $rate, $myMoney);
				}
			}else{
				$this->sell(money.'_'.money_in, $myLastTrade[0]['rate'] - max_loss, $myMoney);
			}
			
			print 'MyMoneyIn = '.$myMoneyIn.PHP_EOL;
			
			// If i still have BTC, it means i can put a BUY order on it 		
			if($myMoneyIn > 0.01){
                $average_price = $this->get_average_price('sell', $market);
                $delta = $this->{delta_method}('sell',$average_price,$market['lowestAsk']);
				$rate = number_format($average_price-$delta,nb_decimal);
				if($myLastTrade[0]['rate']==$rate) $rate = number_format($rate-0.00000001,8);
				
				// If the current lowest ask is lower than our calculated rate, we buy at the lowest rate
				if($market['lowestAsk']<$rate) $rate = $market['lowestAsk'];
				$amount = intval($myMoneyIn / $rate);
				$this->buy(money.'_'.money_in, $rate, $amount);
			}
			
			
		}else{
            $delta = $this->{delta_method}('sell',$average_price,$market['lowestAsk']);
        
			$rate = number_format($average_price-$delta,nb_decimal);
			if($myLastTrade[0]['rate']==$rate) $rate = $rate-0.01;
			
			// If the current lowest ask is lower than our calculated rate, we buy at the lowest rate
			if($market['lowestAsk']<$rate) $rate = $market['lowestAsk'];
			
			// TODO: To implement for bitstamp
			/*if(focus_higher_buy){
				if($new_rate = $this->get_high_buy_order()){
					$rate = $new_rate;
				}
			}*/
			
			$amount = intval($myMoneyIn / $rate);
			if($myMoneyIn>0.01){
				$this->buy(money.'_'.money_in, $rate, $amount);
			}
			
			// If i still have money currency, it means i can put a sell order on it
			if($myMoney>0){
                $average_price = $this->get_average_price('buy', $market);
                $delta = $this->{delta_method}('buy',$average_price,$market['highestBid']);
				$rate = number_format($average_price+$delta,5);
				if($myLastTrade[0]['rate']==$rate) $rate = $rate+0.01;
				if(max_loss == 0 || $myLastTrade[0]['rate']-$rate < max_loss){
					// If the current highest big is higher than our calculated rate, we sell at the highest rate
					if($market['highestBid']>$rate) $rate = $market['highestBid'];
					// If the target sell price is higher than our previous sell + %satochi_varation_rate, we don't try to sell to much high
					if($myLastTrade[0]['rate']+$delta < $rate) $rate = $myLastTrade[0]['rate']+$delta; 
					$this->sell(money.'_'.money_in, $rate, $myMoney);
				}
			}
		}		
	}	
	
	public function reput_orders($type){
		if(debug_mode) $this->write('f','Curve has not moved, reput previous orders (if we still have)');
		
		if($this->lastOrders && count($this->lastOrders)>0){
			foreach($this->lastOrders as $order){
				if($order['type']==$type){
					$this->{$type}(money_in.'_'.money, $order['rate'], $order['amount']);
				}
			}
			return true;
		}else{
			return false;
		}
	}
	
	public function get_nb_tries(){
		return $this->nbtries;
	}
	
	public function get_my_last_trade($pair) {
	    $res  = $this->apiObj->get_my_last_trade($pair);
        if (isset($res['error']) || empty($res) || count($res)==0){
          $msg = 'function get_my_last_trade: connection to api error';
          $this->write('f_e', $msg);   
          $this->write('f_e',json_encode($res)); 
          throw new Exception($msg);
          
        }
        else{
            $this->write('f',' ** My last trade is '.$res[0]['type'].' '.$this->money.' for '.$res[0]['rate']);
		}
		return $res;
	}
	
	public function get_ticker($pair = "ALL") {
	
	    $res  = $this->apiObj->get_ticker($pair);

	   if (isset($res['error']) || empty($res)|| count($res)==0){
          $msg = 'function get_ticker: connection to api error';
          $this->write('f_e', $msg);   
          $this->write('f_e', json_encode($res)); 
          throw new Exception($msg);
          
        }
        else{
			
            $this->write('f','current price is sellfor:'.$res['highestBid'].' , buyfor:'.$res['lowestAsk']);
            $this->save_market($res);
		}
		return $res;
	}
	
	public function get_balances() {
	    $res  = $this->apiObj->get_balances();
	   if (isset($res['error']) || empty($res)|| count($res)==0){
          $msg = 'function get_balances: connection to api error';
          $this->write('f_e',$msg);
          $this->write('f_e', json_encode($res)); 
          throw new Exception($msg);
          
        }
       
		return $res;
	}
	
	public function sell($pair, $rate, $amount) {

		$res='';
		if(!dev_mode) $res  = $this->apiObj->sell($pair, $rate, $amount);
		else $res = array('devmode');
	   if (isset($res['error']) || empty($res)|| count($res)==0){
          $msg = 'function sell: connection to api error';
          $this->write('f_e', $msg);
          $this->write('f_e', json_encode($res)); 
          $this->write('f', '#Tried to put PUT ORDER SELL '.$amount.' '. $this->money.'  for '.$rate); 
          throw new Exception($msg);
          
        }
        else{
            $this->write('f_t', '# PUT ORDER SELL '.$amount.' '. $this->money.'  for '.$rate); 
            $this->write('f', '# PUT ORDER SELL '.$amount.' '. $this->money.'  for '.$rate); 
		}
		return $res;
	}
	
	public function buy($pair, $rate, $amount) {
	   $res='';
	   if(!dev_mode) $res  = $this->apiObj->buy($pair, $rate, $amount);
		else 
		$res = array('devmode');
	   if (isset($res['error']) || empty($res)|| count($res)==0){
          $msg = 'function buy: connection to api error';
          $this->write('f_e', $msg);   
          $this->write('f_e', json_encode($res)); 
          $this->write ('f','Tried to put order #BUY '.$amount.' '.$this->money.' for '.$rate); 
          throw new Exception($msg);
          
        }
        else{
            $this->write ('f','# PUT ORDER BUY '.$amount.' '.$this->money.' for '.$rate); 
            $this->write ('f_t','# PUT ORDER BUY '.$amount.' '.$this->money.' for '.$rate); 
            
		}
		return $res;
	}
	
	
	public function no_action(){

          $this->write ('f',' #NO ACTION '); 
	}
	
	public function recalibrate_threshold($my_last_trade, $trade_type, $market){
		
		$content = file_get_contents(market_log);
		$array_market = json_decode($content);
		
		$avg = $this->get_averages($market);
		
		$avg_buy = $avg['avg_buy'];
		$avg_sell = $avg['avg_sell'];
		$this->write('f',' Current average prices for buy/sell are '.$avg_buy.'/'.$avg_sell);
		
		$threshold = $my_last_trade;
		if( ($trade_type=='buy' && ($avg_buy * 100 / $my_last_trade ) < 99) ){
			$threshold = $avg_buy;
			$this->write('f',' => my new treshold is '.$avg_buy);
		}elseif( ($trade_type=='sell' && ($avg_sell * 100 / $my_last_trade ) >101)){
			$threshold = $avg_sell;
			$this->write('f',' => my new treshold is '.$avg_sell);
		}
		
		return $threshold;
	}
	
	public function get_average_price($last_trade_type, $market){
		$content = file_get_contents(market_log);
		$array_market = json_decode($content);
		$avg = 0;
		$i = count($array_market);
		
		foreach($array_market as $market){
			if($last_trade_type=='buy'){
                
				if(isset($market->highestBid)){
                    $avg+= $market->highestBid;
                    }
			}else{
                
				if(isset($market->lowestAsk)){
                    $avg+= $market->lowestAsk;
                    }
			}
		}
		
		if ($i<avg_number){
            $avg += number_format(round($avg/$i, 8 , PHP_ROUND_HALF_UP), 8)*(avg_number-$i);
		}
		if ($i>avg_number){
            $avg -= number_format(round($avg/$i, 8 , PHP_ROUND_HALF_UP), 8)*($i-avg_number);
		}
		
		if($last_trade_type=='buy'){
			$avg = number_format(round($avg/avg_number, 8 , PHP_ROUND_HALF_UP), 8);
		}else{
			$avg = number_format((floor($avg*100000000/avg_number)/100000000), 8);
		}
		
		$this->write('f',' => average price for '.($last_trade_type=='buy'?'sell':'buy').' is '.$avg);
		
		return $avg;
	}
	public function get_median_price($last_trade_type, $market){
		$content = file_get_contents(market_log);
		$array_market = json_decode($content);
		$avg = 0;
		$i = count($array_market);
		$prices = array();
		foreach($array_market as $market){
			if($last_trade_type=='buy'){
                
				if(isset($market->highestBid)){
                    $prices[] = $market->highestBid;
                    }
			}else{
                
				if(isset($market->lowestAsk)){
                    $prices[] = $market->lowestAsk;
                    }
			}
		}
		asort($prices);
		$prices = array_values($prices);
		$n =  count($prices);
		if ($n%2 == 0){
		    $avg = $prices[$n/2] + $prices[$n/2 -1];
            if($last_trade_type=='buy'){
                $median = number_format(round($avg/2, 8 , PHP_ROUND_HALF_UP), 8);
            }else{
                $median = number_format((floor($avg*100000000/2)/100000000), 8);
            }
            
		}
		else {
		    $median = $prices[floor($n/2)];
		}
		
				
		$this->write('f',' => median price for '.($last_trade_type=='buy'?'sell':'buy').' is '.$median);
		
		return $median;
	}
	
	public function save_market($market){
		$content = file_get_contents(market_log);
		if (is_null($content) || $content == 'null' || $content == ''){
			$array_market = array();
		}
		else{
			$array_market = json_decode($content);	
		
			if (count($array_market)>=avg_number){
        
				 $array_market = array_splice($array_market,-(avg_number-1));
		
            }
            
		}
		
        array_push($array_market,json_decode(json_encode($market), FALSE));

		file_put_contents(market_log, json_encode($array_market));
	}

	public function get_last_market(){
		$content = file_get_contents(market_log);
		$array_market = json_decode($content);	
		return $array_market[count($array_market)-1];
	}


    private function get_delta($last_action,$current_average, $current_rate){
        
        
        $delta = (variation_rate* $current_average /100) * self::$satochi;
        $current_average = $current_average * self::$satochi;
        $current_rate = $current_rate * self::$satochi;
        if ($last_action == 'buy'){
            if (($current_rate-$current_average)==-1){
                $delta = ceil($delta / 2);
            }
        }
        elseif ($last_action == 'sell'){
          if($current_rate-$current_average>1){
              $delta = ceil($delta / 3);
          }
          else{
            if ($current_average-$current_rate<0)
                $delta = ceil($delta / 2);
          }
        }
        return number_format(round($delta/self::$satochi, 8 , PHP_ROUND_HALF_UP), 8); 
    }
    
    private function simple_delta($last_action,$current_average, $current_rate){
        
        
        $delta = variation_rate * $current_average / 100;
        
        return number_format(ceil($delta*self::$satochi)/self::$satochi, 8); 
    }

    private function max_delta($last_action,$current_average, $current_rate){
        
        
        $delta = max_variation_rate * $current_average / 100;
        
        return number_format(ceil($delta*self::$satochi)/self::$satochi, 8); 
    }
    
    private function get_averages($market){
        $avg_sell = 0;
		$avg_buy = 0;		
		$i=0;
		foreach($array_market as $market){
            $i++;
			$avg_sell+= $market->highestBid;
			$avg_buy+= $market->lowestAsk;
		}
		if ($i<avg_number){
            $avg_sell += number_format(round($avg_sell/$i, 8 , PHP_ROUND_HALF_DOWN), 8)*(avg_number-$i);
            $avg_buy += number_format(round($avg_buy/$i, 8 , PHP_ROUND_HALF_DOWN), 8)*(avg_number-$i);
		}
		if ($i>avg_number){
            $avg_sell -= number_format(round($avg_sell/$i, 8 , PHP_ROUND_HALF_DOWN), 8)*($i-avg_number);
            $avg_buy -= number_format(round($avg_buy/$i, 8 , PHP_ROUND_HALF_DOWN), 8)*($i-avg_number);
		}
		$avg_buy = number_format(round($avg_buy/avg_number, 8 , PHP_ROUND_HALF_DOWN), 8);
		$avg_sell = number_format(round($avg_sell/avg_number, 8 , PHP_ROUND_HALF_UP), 8);
		return array('avg_buy'=>$avg_buy,
                        'avg_sell'=>$avg_sell);
	}
	//live cumulative gains 
    private function calc_and_log_gains($myLastTrade){
    
     
        if (is_null($this->data->gains_cumul_lastupdate)){
            $this->data->gains_cumul =0;
            $start = 0;
            $end = strtotime('today midnight');
            $this->gains_graph->clear();
        }
        elseif ($this->data->gains_cumul_lastupdate < strtotime('today midnight')){
            $start = $this->data->gains_cumul_lastupdate;
            $end = strtotime('today midnight');
        }
        else{
          return true;
        }
        $history = $this->get_my_trade_history(money_in.'_'.money,0,$start,$end);
        $gain = 0;
        $buy= 0;
        $sell=0;
        
        if(is_array($history) && count($history)>0){
                $myLastTrade = array();        
                foreach($history as $trade) {
                    if ($trade['type']=='buy'){
                        $gain -= $trade['total']*self::$satochi;
                        $buy +=$trade['total']*self::$satochi;
                        }
                    if ($trade['type']=='sell'){
                        $gain += $trade['total']*self::$satochi;
                        $sell += $trade['total']*self::$satochi;
                        }
                    
                    if (!isset($myLastTrade['type'])) {
                        $myLastTrade=$trade;
                        }
                    elseif(!isset($myLastTrade['stop']) && $myLastTrade['type']==$trade['type']) {
                        $myLastTrade['total'] += $trade['total'];
                    }
                    else{
                        $myLastTrade['stop'] = 'stop';
                        }

                    
                    
                }
        }    
        
        $this->data->gains_cumul += $gain;
        $this->data->gains_cumul_lastupdate = strtotime('today midnight');
        $delta = 0;
        
        if ($myLastTrade['type'] == 'buy'){
                $delta = $myLastTrade['total'] * self::$satochi;
        }
        $this->gains_graph->saveValue(array(($this->data->gains_cumul+$delta)));
        
	}
    // make a function that would returns gains per day for the last x days
    // using returnTradeHistory with params start and end, we could the use this to on the fly generate 
    // a gain graph per day
    
    public static function get_gains_per_day($s=0,$days=7){
        $polo = new poloniex(key,secret);
        $res = array();
        $days--;
        $total=0;
      for($i=0;$i<=$days;$i++){
            $start = strtotime('-'.($i-$s).' days midnight');
            $end = strtotime('-'.($i-$s-1).' days midnight')-1;
            $history = $polo->get_my_trade_history(money_in.'_'.money,0,$start,$end);
            $gain = 0;
            if(is_array($history) && count($history)>0){
                foreach($history as $trade) {
                    if ($trade['type']=='buy'){
                        $gain -= $trade['total']*self::$satochi;
                        $total -= $trade['total']*self::$satochi;
                        }
                    if ($trade['type']=='sell'){
                        $gain += $trade['total']*self::$satochi;
                        $total += $trade['total']*self::$satochi;
                        }
                    
                }
            }
            
            $res[date('d/m/Y',strtotime('-'.($i-$s).' days'))] = $gain;
      }
      $res['total']= $total;
      return $res;
    }
    
    public function get_value($coin, $currency='euro'){
        return json_decode(file_get_contents('https://api.coinmarketcap.com/v1/ticker/'.$coin.'/?convert='.$currency));
    }
    
}
