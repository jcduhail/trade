<?php 
trait Logger {
protected $log;

public function init(){
            $this->init_rotate(action_log,log_rotate,max_log_size);
			$this->init_rotate(transaction_log,log_rotate,max_log_size);
			$this->init_rotate(error_log,log_rotate,max_log_size);
			$this->log['f'] = fopen(action_log,'a+');
            $this->log['f_t'] = fopen(transaction_log,'a+');
            $this->log['f_e'] = fopen(error_log,'a+');
            
}

public function close(){
	
		  fclose($this->log['f']);
		  fclose($this->log['f_e']);
		  fclose($this->log['f_t']);
	}

public function init_rotate($logfilename,$logfilestokeep,$maxsize){
        if (file_exists($logfilename)) {
            if (filesize($logfilename) >= $maxsize) {
                if (file_exists($logfilename . "." . $logfilestokeep)) {
                    unlink($logfilename . "." . $logfilestokeep);
                }
                for ($i = $logfilestokeep; $i > 0; $i--) {
                    if (file_exists($logfilename . "." . $i)) {
                        $next = $i+1;
                        rename($logfilename . "." . $i, $logfilename . "." . $next);
                    }
                }
                rename($logfilename, $logfilename . ".1");
            }
        }
}

public function write($filename, $msg){
    fwrite($this->log[$filename], date('d/m/Y H:i:s').' : '.$msg.chr(13).chr(10));   
}

}
