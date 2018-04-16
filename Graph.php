<?php 
require_once 'settings.php';

class Graph  {
    public $graph;
    protected $name;

 public function __construct($name,$last=null) {
           $this->name = $name;
			$this->graph = json_decode(file_get_contents($name));
            if (is_null($this->graph) || $this->graph == 'null'){
                $this->graph = array();
            }
            else {
              if (!is_null($last))
              $this->graph = array_slice($this->graph, -$last);
            }
            
}
public function saveValue($myData){
    //we only keep 24 hours worth of data
    if (count($this->graph)>=graph_data_points_max)
        $this->graph = array_slice($this->graph,-graph_data_points_max);
    $data = array(date("YmdHis",time()));
    $data = array_merge($data,$myData);
    $this->graph[]=$data;
    file_put_contents($this->name,json_encode($this->graph));
        
}
public function clear(){
    file_put_contents($this->name,'');
    $this->graph = array();   
}

}
