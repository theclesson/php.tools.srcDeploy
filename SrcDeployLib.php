<?php
ini_set( 'include_path', ini_get('include_path'). PATH_SEPARATOR . './lib/phpseclib1.0.2' );
require_once( 'Net/SFTP.php');

$deploy	= new Deploy( './conf/SrcDeploy.ini' );
$deploy->run( $argv );

class Deploy {
	
	const	THIS_PROJECT= 'SrcDeploy';
	
	const	IS_DIR		= 'IS_DIR';
	const	IS_FILE		= 'IS_FILE';
	const	INI_FILE	= './conf/SrcDeploy.ini';
	
	const 	SFTP_PORT	= 22;
	
	private $iniFile	= NULL;
	private $iniConf	= array();
	private $conf		= array();
	private $file		= array();
	private $sftp		= NULL;
	
	private $debug		= 0;
	
	public function __construct( $iniFile = NULL ) {
		$this->setConfig( $iniFile );
	}
	
	public function setConfig( $iniFile ) {
		
		$this->iniFile	= empty($iniFile) ? self::INI_FILE : $iniFile ;
		
		if ( !file_exists( $this->iniFile ) ) {
			echo "[ERROR] INI File is Not Exists : {$this->iniFile} \n";
			return FALSE;
		}
		
		$this->iniConf	=  parse_ini_file( $this->iniFile, TRUE );
		$this->debug( $this->iniConf, 'setConfig() > $this->iniConf' );
		
		return TRUE;
	}
	
	public function run( $argv = array() ) {
		
		if ( !$this->checkConfig(@$argv[1]) ) {
			return FALSE;
		}
		$this->debug( $this->conf, 'run() > $this->conf' );
		
		if ( !$this->checkFile() ) {
			return FALSE;
		}
		$this->debug( $this->file, 'run() > $this->file' );
		
		
		echo "Deploy Source File ...	\n";
		echo "\n";
		
		echo "Connecting Host : {$this->conf['host']}:{$this->conf['port']} \n";
		$this->sftp = new Net_SFTP( $this->conf['host'], $this->conf['port'] );
		
		if ( ! $this->sftp->login( $this->conf['user'], $this->conf['passwd'] ) ) {
			exit( "Login() Failed : {$this->conf['user']}:{$this->conf['passwd']}" );
		}
		
		$this->upload( $this->conf, $this->file );
	}
	
	private function upload ( $conf, $file ) {
		
		$uploadDir	= $this->conf['path'] . $this->file['dir'];
		$deployFile	= $uploadDir . $this->file['name'];
		echo "Change dir      : {$uploadDir}	\n";
		if ( ! $this->sftp->chdir($uploadDir) ) {
			exit( "Chdir() Failed : {$uploadDir}" );
		}
		
		echo "\n";
		echo "Source File     : {$this->file['path']}	\n";
		echo "Deploy To       : {$deployFile}  			\n";
		
		if ( ! $this->sftp->put( $deployFile, $this->file['path'], NET_SFTP_LOCAL_FILE ) ) {
			exit( "put() Failed : {$this->file['path']} to {$deployFile}" );
		}
		
		echo "\n";
		echo "Deploy Success!!	\n";
		
	}
	
	private function checkConfig( $target ) {
		
		if ( empty($target) ) {
			echo "Deploy target is empty. php -f SrcDeploy [TARGET] \n";
			return FALSE;
		}
		$this->debug( $target, 'checkConfig() > $target' );
		
		$this->conf	= @$this->iniConf[$target];
		
		if ( empty($this->conf) ) {
			echo "[ERROR] Target is Not Exists in INI File.\n";
			return FALSE;
		}
		
		if ( empty($this->conf['host']) ) {
			echo "[ERROR] HOST is Not Exists in INI File. for {$target}\n";
			return FALSE;
		}
		
		if ( empty($this->conf['path']) ) {
			echo "[ERROR] PATH is Not Exists in INI File. for {$target}\n";
			return FALSE;
		}
		
		if ( empty($this->conf['user']) ) {
			echo "[ERROR] USER is Not Exists in INI File. for {$target}\n";
			return FALSE;
		}
		
		if ( empty($this->conf['passwd']) ) {
			echo "[ERROR] PASSWD is Not Exists in INI File. for {$target}\n";
			return FALSE;
		}
		
		$this->conf['port']	= empty($this->conf['port'])	? self::SFTP_PORT 	: $this->conf['port'] ;
		
		return $this->conf;
	}
	
	private function checkFile() {
		
		$this->file['ploc'] 	= getenv( 'project_loc' );
		$this->file['proj'] 	= getenv( 'project_name' );
		$this->file['path'] 	= getenv( 'resource_loc' );
		$this->file['name'] 	= getenv( 'resource_name' );
		$this->file['type'] 	= @is_file( $this->file['path'] ) ? self::IS_FILE : self::IS_DIR ;
		$this->debug( $this->file, 'checkFile() > $this->file' );
		
		if ( $this->file['proj'] == self::THIS_PROJECT ) {
			echo "[ERROR] Source file in this tool. select other file. \n";
			return FALSE;
		}
		
		if ( empty($this->file['path']) ) {
			echo "[ERROR] Source Path is empty. check RUN Environment : resource_loc \n";
			return FALSE;
		}
		
		if ( !file_exists($this->file['path']) ) {
			echo "[ERROR] Source file is Not Exists. check RUN Environment : resource_loc \n";
			return FALSE;
		}
		
		$this->file['dir']	= str_replace( $this->file['ploc'], '', str_replace( $this->file['name'], '', $this->file['path'] ) );
		$this->file['dir']	= str_replace( '\\', '/', $this->file['dir'] );
		
		return $this->file;
	}
	
	public function debug( $var, $msg = NULL ) {
		if ( $this->debug ) {	echo "{$msg} : \n";	print_r( $var );	echo "\n\n";	}
	}
	
}
?>