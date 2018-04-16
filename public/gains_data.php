<?php


include '/var/www/html/ecumeur/cfs.php';




$days = (isset($_GET['days']))?$_GET['days']:7;
$start = (isset($_GET['start']))?$_GET['start']:0;
$gains = cfs::get_gains_per_day($start,$days);


$output = fopen("php://output",'w') or die("Can't open php://output");
ob_start();
//header("Content-Type:application/csv"); 
//header("Content-Disposition:attachment;filename=data.tsv"); 




    fputcsv($output, array('jour','gains'),chr(9),'"');


foreach($gains as $day=>$gain) {
    fputcsv($output, array($day,$gain),chr(9),'"');
}

fclose($output) or die("Can't close php://output");
$string = ob_get_clean();
        

        
// Output CSV-specific headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment filename=\"$filename.csv\";" );
header("Content-Transfer-Encoding: binary");
exit($string);
