<?php
include 'cfs_v2.php';

function __autoload($classname) {
    $filename = 'core/'.$classname.".php";
    
    if(file_exists( $filename ))
    {
        include_once($filename);
    }
}

while(true){
    try{
        $exchange = new cfs_v2('bitstamp');
        $exchange->launch();
    }
    catch(Exception $e){
        echo $e->getMessage();
        if($exchange->get_nb_tries()<3) $exchange->launch();
    }
    sleep(120);
}
