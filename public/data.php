<?php
include '/var/www/html/ecumeur/settings.php';

include '/var/www/html/ecumeur/Graph.php';

$last = (isset($_GET['last']))?$_GET['last']:null;
$type = (isset($_GET['type']))?$_GET['type']:'data';


$output = fopen("php://output",'w') or die("Can't open php://output");
ob_start();
//header("Content-Type:application/csv"); 
//header("Content-Disposition:attachment;filename=data.tsv"); 

if ($type =='data'){
    $g = new Graph(data_log,$last);
    fputcsv($output, array('time','highestBid','LowestAsk','threshold'),chr(9),'"');
}
elseif ($type =='satochi'){
    $g = new Graph(satochi_log,$last);
    fputcsv($output, array('time','Satochi','BTC','Coin value in Satochi'),chr(9),'"');
}
elseif ($type =='gain'){
    $g = new Graph(gains_log,$last);
    fputcsv($output, array('time','Gain Satochi'),chr(9),'"');
}
if ($type =='satochi2'){
    $g = new Graph(satochi_log,$last);
    fputcsv($output, array('time','Satochi'),chr(9),'"');
    foreach($g->graph as $e) {
        
        fputcsv($output, array_slice( $e, 0, 2),chr(9),'"');
    }
    
}
else{
    foreach($g->graph as $e) {
        fputcsv($output, $e,chr(9),'"');
    }
}
fclose($output) or die("Can't close php://output");
$string = ob_get_clean();
        

        
// Output CSV-specific headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/octet-stream");
//header("Content-Disposition: attachment filename=\"$filename.csv\";" );
header("Content-Transfer-Encoding: binary");
exit($string);
