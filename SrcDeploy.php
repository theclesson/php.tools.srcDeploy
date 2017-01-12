<?php
require_once( 'classes/curlSftp.class.php' );

$deploy	= new Deploy( './conf/SrcDeploy.ini' );
$deploy->run( $argv );

class Deploy {
	
	const	THIS_PROJECT= 'SrcDeploy';
	
	const	IS_DIR		= 'IS_DIR';
	const	IS_FILE		= 'IS_FILE';
	const	INI_FILE	= './conf/SrcDeploy.ini';
	
	const 	CURL_PORT	= 22;
	const 	CURL_RROTO	= CURLPROTO_SFTP;
	
	private $iniFile	= NULL;
	private $iniConf	= array();
	private $conf		= array();
	private $file		= array();
	private $ftp		= NULL;
	
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
		
		echo "Deploy Source File ...											\n";
		echo "\n";
		echo "Source File : {$this->file['path']}								\n";
		echo "Deploy Host : {$this->conf['host']}             					\n";
		echo "Deploy Path : {$this->conf['path']}             					\n";
		echo "Deploy User : {$this->conf['user']}:{$this->conf['passwd']}		\n";
		
		$this->upload( $this->conf, $this->file );
	}
	
	private function upload ( $conf, $file ) {
		
		if ( @$file['type'] == self::IS_DIR ) {
			$file['upload']	= str_replace( $file['ploc'], '', str_replace( $file['name'], '', $file['path'] ) );
			echo "[ERROR] Is a Dir. Select File Only. \n";
 			return FALSE;
		}
		else {
			$file['upload']	= str_replace( $file['ploc'], '', str_replace( $file['name'], '', $file['path'] ) );
		}
		$file['upload']		= str_replace( "\\", '/', $file['upload'] );
		$this->debug( $file, 'upload() > $file' );
		
		return $this->send( $conf, $file );
	}
	
	private function send ( $conf, $file ) {
		
		$ftpConf[CURLOPT_URL]		= "sftp://{$conf['host']}{$conf['path']}{$file['upload']}{$file['name']}";
		$ftpConf[CURLOPT_PORT]		= $conf['port'];
		$ftpConf[CURLOPT_PROTOCOLS]	= $conf['proto'];
		$ftpConf[CURLOPT_USERPWD]	= "{$conf['user']}:{$conf['passwd']}";
		
		$ftp	= New CurlSftp( $ftpConf );
		$ftp->send( $file );
		
		return TRUE;
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
		
		$this->conf['port']	= empty($this->conf['port'])	? self::CURL_PORT 	: $this->conf['port'] ;
		$this->conf['proto']= empty($this->conf['proto']) 	? self::CURL_RROTO 	: $this->conf['proto'] ;
		
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
		
		return $this->file;
	}
	
	public function debug( $var, $msg = NULL ) {
		if ( $this->debug ) {	echo "{$msg} : \n";	print_r( $var );	echo "\n\n";	}
	}
	
}
?>