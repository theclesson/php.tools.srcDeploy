<?php 
/**
 * CURL SFTP Class
 * 
 * @author	razy
 * 
 * @created	2015.10.28	razy
 */

class CurlSftp {
	
	private $curl	= NULL;
	private $host	= NULL;
	private $conf	= array();
	private $info	= array();
	private $error	= array();
	private $fp		= NULL;
	
	private $debug	= 1;
	
	public function __construct( $config = NULL ) {
		$this->curl	= curl_init();
		$this->setConfig( $config );
	}
	
	public function setConfig( $config, $val=NULL ) {
		
		$this->debug( $config, 'setConfig() > $config' );
		
		if ( is_array($config) ) {
			$this->conf	= $config;
			curl_setopt_array( $this->curl, $this->conf );
		}
		else if ( !empty($config) && !empty($val) ) {
			curl_setopt( $this->curl, $config, $val );
		}
		else if ( !empty($config) && is_string($config) ) {
			$this->host	= $config;
		}
		
		curl_setopt( $this->curl, CURLOPT_UPLOAD, 1 );
	}
	
	public function send( $file ) {
		
		if ( !$this->fp = fopen( $file['path'], 'r' ) ) {
			echo "[ERROR] File is Not Exists or Read Error. \n";
			return FALSE;
		}
		$this->debug( $file, 'send() > $file' );
		
		curl_setopt( $this->curl, CURLOPT_INFILESIZE, 	filesize($file['path']) );
		curl_setopt( $this->curl, CURLOPT_INFILE,		$this->fp );
		
		echo "\n\nFile Uploding .... '{$file['upload']}{$file['name']}' \n\n";
		$result	 	= curl_exec( $this->curl );
		$error_no 	= curl_errno( $this->curl );
		$info 		= curl_getinfo( $this->curl );
		
		curl_close ( $this->curl );
		fclose( $this->fp );
		
		$this->debug( $info );
		$this->debug( $error_no, 'send > error_no' );
		
		echo "Local File    : {$file['path']} \n";
		echo "Remote File   : {$this->conf[CURLOPT_URL]} \n";
		echo "Remote IP     : {$info['primary_ip']}:{$info['primary_port']} \n";
		echo "Upload Size   : {$info['upload_content_length']} \n";
		echo "Upload Time   : {$info['starttransfer_time']} sec \n";
		echo "Upload Result : ". $this->curl_msg( $error_no ) ."\n";
	}
	
	function __destruct() {
		@curl_close( $this->curl );
		@fclose( $this->fp );
   	}
   	
   	function curl_msg ( $error_no = 0 ) {
   	
   		switch( $error_no ) {
   			case 7	: return '[7]  CURLE_COULDNT_CONNECT';			break;
   			case 9	: return '[9]  CURLE_REMOTE_ACCESS_DENIED';		break;
   			case 78	: return '[78] CURLE_REMOTE_FILE_NOT_FOUND';	break;
   			case 0	: return '[0]  CURLE_OK';						break;
   			default	: return "[{$error_no}] UNKNOWN ERROR";			break;
   		}
   	}
   	
   	public function debug( $var, $msg = NULL ) {
   		if ( $this->debug ) {	echo "{$msg} : \n";	print_r( $var );	echo "\n\n";	}
   	}
	
}
?>