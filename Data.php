<?php 
Class Data{
    protected $file;
    protected $data;
     public function __construct($name=null) {
     
            if (is_null($name))
                $this->file = 'data.tfs';
            else {
                $this->file = $name.'.tfs';
            }

            if (!is_dir(__DIR__.'/data')){
                mkdir(__DIR__.'/data');
                chmod(__DIR__.'/data',0777);

            }
            if (file_exists(__DIR__.'/data/'.$this->file)){
                $data = file_get_contents(__DIR__.'/data/'.$this->file);
            }
            else $data ='null';
    
            if ($data!='null')
                $this->data = unserialize($data);
            else{
                $this->data = new stdClass();
                }
    
		}
		
	public function __destruct(){
        file_put_contents(__DIR__.'/data/'.$this->file, serialize($this->data));
	}
	
	public function __set($name, $value){
	 $this->data->{$name} = $value;
	
	}
	public function __get($name){
	if (isset($this->data->{$name}))
        return $this->data->{$name};
    else {
        return null;
    }
    
	}
}
