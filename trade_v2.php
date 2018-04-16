<?php
include 'cfs.php';
include 'Graph.php';

try{
	$poloniex = new cfs();
	$g = new Graph(data_log);
	$myLastTrade = $poloniex->get_my_last_trade('BTC_'.money);
	
	$market = $poloniex->get_ticker('BTC_'.money);
	if(!$market) die;
	$myBalances = $poloniex->get_balances();
	//$myMoney = $myBalances[money];
	$myMoney = min(max_sale,$myBalances[money]);
	
	// Determine if need to recalibrate the threshold
	$threshold = $poloniex->recalibrate_threshold($myLastTrade[0]['rate'], $myLastTrade[0]['type'], $market);
	
	$g->saveValue(array($market['highestBid'],$market['lowestAsk'],$threshold));
	
	//print $market['highestBid'] * 100 / $threshold;
		
	if( ($myLastTrade[0]['type']=='buy' && ( ($market['highestBid'] * 100 / $threshold) >= 100.1  )) || $market['highestBid'] ==  force_sell_at){
		// Sell all
		$poloniex->sell('BTC_'.money, $market['highestBid'], $myMoney,$market);
	}elseif( $myLastTrade[0]['type']=='sell' && (( ($market['lowestAsk'] * 100 / $threshold) <= 99.9  ) || $market['lowestAsk']==force_buy_at )){
		// Buy all
			
		$myBTC = $myBalances['BTC'];
		$amount = round($myBTC / $market['lowestAsk'], 0 , PHP_ROUND_HALF_DOWN);
		$poloniex->buy('BTC_'.money, $market['lowestAsk'], $amount, $market);
	}else 
	 	$poloniex->no_action();
}
catch(Exception $e){
	  echo $e->getMessage();
}
