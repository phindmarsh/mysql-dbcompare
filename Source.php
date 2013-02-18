<?php

abstract class Source {
	
	protected $type;
	protected $args;
	
	public function __construct($type, $args){
		$this->args = $args;
	}
	
	public static function init($type, array $args){
		
		$offset = strlen($type) + 1;
		$conf = array();
		foreach($args as $key => $value){
			if(strpos($key, $type) === 0){
				$conf[substr($key, $offset)] = $value;
			}
		}
		
		if(isset($conf['ssh-server'])){
			return new SSHSource($type, $conf);
		}
		else if (isset($conf['db-name'])){
			return new DBSource($type, $conf);
		}
		else if(isset($conf['file'])){
			return new FileSource($type, $conf);
		}
		else {
			throw new Exception('No valid sources found in arguments');
		}
	}
	
	abstract public function getStructure();
	
	public static function getArgList() { 
		return array_merge(
			FileSource::getArgList(), 
			DBSource::getArgList(), 
			SSHSource::getArgList()
		);
	}

}

class FileSource extends Source {
	
	static $arglist = array(
		'file'
	);
	
	public function getStructure(){
		if(isset($this->args['file']) && file_exists($this->args['file'])){
			return file_get_contents($this->args['file']);
		}
		else
			throw new Exception("Could not read source file for $type");
	}
	
	public static function getArgList(){
		return self::$arglist;
	}
	
	public function __toString(){
		return $this->args['file'];
	}
	
}

class DBSource extends Source {
	
	static $arglist = array(
		'db-user',
		'db-password',
		'db-name',
		'db-host',
		'db-port',
		'db-socket'
	);
	
	public function __construct($type, $args){
		
		parent::__construct($type, $args);
		
		if(!isset($args['db-name']))
			throw new Exception('DB Source requires a db-name to be set');
		
	}
	
	public function getStructure(){
		return shell_exec( $this->buildCommand() );
	}
	
	protected function buildCommand(){
		
		$command = array();
		$command[] = 'mysqldump -d --compact ';
		if(isset($this->args['db-user']))
			$command[] = '-u'.$this->args['db-user'];
		if(isset($this->args['db-password']))
			$command[] = '-p'.$this->args['db-password'];
		if(isset($this->args['db-host']))
			$command[] = '-h'.$this->args['db-host'];
		if(isset($this->args['db-port']))
			$command[] = '-P'.$this->args['db-port'];
		if(isset($this->args['db-socket']))
			$command[] = '-S'.$this->args['db-socket'];
		
		$command[] = $this->args['db-name'];
		$command[] = "2>&1";
		
		return implode(' ', $command);
	}
	
	public static function getArgList(){
		return self::$arglist;
	}
	
	public function __toString(){
		$host = isset($this->args['db-host']) ? $this->args['db-host'] : 'localhost';
		return 'mysqldump:'.$this->args['db-name'].'@'.$host;
	}
	
}

class SSHSource extends DBSource {
	
	static $arglist = array(
		'ssh-server',
		'ssh-user',
		'ssh-pubkey',
		'ssh-privkey',
	);
	
	private $connection;
	
	public function __construct($type, $args){
		if(!function_exists('ssh2_connect'))
			throw new Exception('SSH2 is required to use an ssh connection. Try `pecl install channel://pecl.php.net/ssh2-0.11.3`');
					
		parent::__construct($type, $args);
		
		foreach(self::$arglist as $arg){
			if(!isset($this->args[$arg]))
				throw new Exception("SSH Source requires '{$arg}' to be set");
		}
		
		list($host, $port) = explode(':', $this->args['ssh-server']) + array('localhost', '22');
		
		if(!$this->connection = ssh2_connect($host, (int)$port)){
			throw new Exception('Cannot connect to server'); 
		}
		if (!ssh2_auth_pubkey_file($this->connection, $this->args['ssh-user'], $this->args['ssh-pubkey'], $this->args['ssh-privkey'])) { 
            throw new Exception('Autentication rejected by server'); 
        }
        
        if(VERBOSE){
	        notice('Connected to SSH server as `' . trim($this->exec('whoami')) . '`. Server time is ' . trim($this->exec('date')));
        }
	}
	
	public function getStructure(){
		
		$command = $this->buildCommand();
		
		return $this->exec($command);
		
	}
	
	private function exec($cmd) { 
	
        if (!$this->connection || !($stream = ssh2_exec($this->connection, $cmd))) { 
            throw new Exception('SSH command failed'); 
        }
        
        stream_set_blocking($stream, true); 
        
        $data = ""; 
        while ($buf = fread($stream, 4096)) { 
            $data .= $buf; 
        } 
        
        fclose($stream); 
        
        return $data; 
        
    }
	
	public static function getArgList(){
		return array_merge(self::$arglist, parent::getArgList());
	}
	
	public function __toString(){
		return parent::__toString() . '~ssh:'.$this->args['ssh-server'];
	}
	
	private function disconnect() { 
        $this->exec('history -c');
        $this->exec('echo "EXITING" && exit'); 
        
        $this->connection = null; 
    }
    
    public function __destruct() {
        if($this->connection !== null)
        	$this->disconnect(); 
    }
	
}
