<?php
include 'cfs.php';

try{
	$poloniex = new cfs();
	$poloniex->launch();
}
catch(Exception $e){
	  echo $e->getMessage();
	  if($poloniex->get_nb_tries()<3) $poloniex->launch();
}
