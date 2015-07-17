<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WAPI{
	private static $version='1.0';
	protected $debug=false;
	protected $moduleConfig=array();
	protected $whmcsconfig=null;
	private $whmcsObject=null;
	private static $instance;
	private $this->db=null;
	
	function __construct()
	{
		$this->debug=false;
		$this->db=WDB::getInstance();
		$whmcs=$this->getWhmcsConfig();
		if(!defined('WHMCSV')) define('WHMCSV',$whmcs["Version"]);
	}
	
	public static function getInstance()
	{
		if(!self::$instance) self::$instance = new self();
		return self::$instance;
	}
	
	function setDebug($status)
	{
		$this->debug=$status;
	}
	
	public function getLang($key)
	{
		$languageTxt='';
		if(defined('CLIENTAREA') && version_compare(WHMCSV, '6.0.0') >= 0){
			if(empty($this->whmcsObject)){
				require(ROOTDIR."/init.php");
				$this->whmcsObject=$whmcs;
			}
			$languageTxt=$this->whmcsObject->get_lang($key);
		}else{
			global $_LANG;
			$languageTxt=isset($_LANG[$key])?$_LANG[$key]:'';
		}
		
		if(empty($languageTxt)) $languageTxt=($this->debug)?'*'.$key.'*':$key;
		return $languageTxt;
	}
	
	public static function redirect($url,$seconds=0)
	{
		if(headers_sent() || $seconds>0){
			echo "<script>setTimeout(\"location.href = '".$url."';\",".($seconds*1000).");</script>";
		}else{
			@header('Location:'.$url);
		}
		exit;
	}
	
	//Utf-8 String Handling
	################################################
	function strpos($str,$needle,$offset=0)
	{
		return (function_exists('mb_strpos'))?mb_strpos($str,$needle,$offset):substr($str,$needle,$offset);
	}
	
	function substr($str,$i=null,$j=null)
	{
		return (function_exists('mb_substr'))?mb_substr($str,$i,$j):substr($str,$i,$j);
	}
	
	function strlen($str)
	{
		return (function_exists('mb_strlen'))?mb_strlen($str):strlen($str);
	}
	
	//WHMCS USER CUSTOMFIELDS
	public function updateCustomField($fieldid,$userid,$value="")
	{
		if((int)$fieldid<1 || (int)$userid<1) return false;
		$r=$this->db->query("SELECT value FROM `tblcustomfieldsvalues` WHERE fieldid=".$fieldid." AND relid=".$userid." LIMIT 1;");
		if($this->db->num_rows($r)>0) 
			return $this->db->query("UPDATE tblcustomfieldsvalues SET value='".$value."' WHERE fieldid='".$fieldid."' AND relid='".$userid."';");
		else
			return $this->db->query("INSERT IGNORE INTO `tblcustomfieldsvalues` (`fieldid`, `relid`, `value`) VALUES ('".$fieldid."','".$userid."','".$value."');");
	}
	
	public function getCustomField($fieldid,$userid)
	{
		if((int)$fieldid<1 || (int)$userid<1) return '';
		$data=$this->db->getRow("SELECT value FROM `tblcustomfieldsvalues` WHERE fieldid=".$fieldid." AND relid=".$userid." LIMIT 1;");
		return $data["value"];
	}
	
	//File/Fodlder Functions
	public function getFiles($dir,$ext=array())
	{
		$dir=rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		if(!is_dir($dir)) return array();
		$files=array();
		$results = scandir($dir);
		foreach ($results as $file) {
			if ($file === '.' || $file === '..' || !is_file($dir.$file)) continue;
			$extension=@end(@explode(".",$file,2));
			$filename=str_replace('.'.$extension,'',$file);
			if(count($ext)>0 && !in_array(".".$extension,$ext)) continue;
			$files[]=$file;
		}
		return $files;
	}
	
	public function getFolders($dir)
	{
		$folders=array();
		foreach (new DirectoryIterator($dir) as $file)
		{
			if($file->isDot()) continue;
			if($file->isDir()) $folders[]=$file->getFilename();
		}
		return $folders;
	}
	
	//Delete Folder with Files
	public function deleteFolder($folder)
	{
		if(!is_dir($folder)) return false;
	 	$files = glob($folder.'/*'); // get all file names
		if(count($files)){
			foreach($files as $file){
			  if(is_file($file)) unlink($file); // delete file
			}
		}
		return rmdir($folder);
	}
	
	public function getLanguagesFiles($exts=array(".php"))
	{
		$files=$this->getFiles(ROOTDIR.DIRECTORY_SEPARATOR.'lang',$exts);
		return $files;
	}
	
	/*Array Functions*/
	public function array_remove($input=array(),$remove=array())
	{
		if(!is_array($remove)) $remove=(array)$remove;
		$arr = array_diff($input,$remove);
		$arr = array_values($arr);
		return $arr;
	}
	
	public function getCountries()
	{
		include(ROOTDIR.'/includes/countries.php');
		return $countries;
	}

	public function getCallingCodes()
	{
		include(ROOTDIR.'/includes/countriescallingcodes.php');
		return $countrycallingcodes;
	}
	
	#Get WHMCS Config
	public function getWhmcsConfig($key='')
	{
		if(!is_array($this->whmcsconfig)){
			$this->whmcsconfig=array();
			$rs=$this->db->query("SELECT * FROM tblconfiguration;");
			while ($data = $this->db->fetch_array($rs)) {
				$this->whmcsconfig[$data["setting"]]=$data["value"];
			}
		}
		if(!empty($key) && $key=='SystemSSLURL' && empty($this->whmcsconfig[$key])) $key='SystemURL';
		if(!empty($key) && is_array($this->whmcsconfig) && isset($this->whmcsconfig[$key])) return $this->whmcsconfig[$key];
		if(!empty($this->whmcsconfig) && is_array($this->whmcsconfig)) return $this->whmcsconfig;
	}
	
	#Get Addon Config
	public function getModuleParams($key=null,$module)
	{
		if(!count($this->moduleConfig) || !isset($this->moduleConfig[$module])){
			
			$this->db->query('SELECT setting,value FROM tbladdonmodules WHERE module="'.$module.'";');
			$this->moduleConfig[$module]=array();
			while($row=$this->db->fetch_array()){
				$this->moduleConfig[$module][$row['setting']]=trim($row['value']);
			}
		}
		if(!empty($key) && is_array($this->moduleConfig[$module]) && isset($this->moduleConfig[$module][$key])) return $this->moduleConfig[$module][$key];
		return $this->moduleConfig[$module];
	}
	
	public function setModuleParams($name,$value='',$module='')
	{
		$this->db->query('UPDATE `tbladdonmodules` SET value="'.$this->db->safe($value).'" WHERE setting="'.$name.'" AND module="'.$module.'";');
		$this->moduleConfig[$name]=$value;
	}
	
	public function logActivity($desc='',$module)
	{
		if(empty($desc)) return false;
		LogActivity($module.' - '.$desc);
		return true;
	}
	
	function highlightKeyword($haystack,$needle,$color = "#daa732") {
		return preg_replace("/($needle)/i",sprintf('<span style="color:%s;">$1</span>',$color),$haystack);
	}
	
	public function sendEmail($to,$subject,$body,$frommail='',$fromname='WHMCS System',$AllowHTML=true,$charset="utf-8")
	{
		if(empty($to) || strpos($to,"@")===false ) return false;
		$whmcs=$this->getWhmcsConfig();
		if($charset!="utf-8") $charset=$whmcs["Charset"];
		if($frommail==''){
			$frommail=trim($whmcs["SystemEmailsFromEmail"]);
			$fromname=trim($whmcs["SystemEmailsFromName"]);
		}
		
		$random_hash = md5(date('r', time()));
		$plainbody= strip_tags(preg_replace("/<br(.*)>/iU", "\n", $body));
		$body='<style type="text/css">'.$whmcs['EmailCSS'].'</style><p><a href="'.$whmcs['SystemURL'].'"><img src="'.$whmcs['LogoURL'].'" border="0" /></a></p>'.$body;
		
		$headers  = "From: ".$fromname." <".$frommail.">\r\n";
		$headers .= "Reply-To: ". $frommail . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"OpenAPIInterface-".$random_hash."\"";
		
		#UTF-8
		if(function_exists('mb_detect_encoding')){
			if(mb_detect_encoding($plainbody)!=strtoupper($charset)) $plainbody=@iconv(mb_detect_encoding($plainbody),$charset,$plainbody); 
			if(mb_detect_encoding($body)!=strtoupper($charset))	$body=@iconv(mb_detect_encoding($body),$charset,$body); #html
		}
		
		$prepare="--OpenAPIInterface-".$random_hash."\n";
		$prepare.="Content-Type: text/plain; charset=\"".$charset."\"";
		$prepare.="\nContent-Transfer-Encoding: base64\n";
		$prepare.="\n".chunk_split(base64_encode($plainbody))."\n";
		if($AllowHTML){
			$prepare.="--OpenAPIInterface-".$random_hash."\n";
			$prepare.="Content-Type: text/html; charset=\"".$charset."\"";
			$prepare.="\nContent-Transfer-Encoding: base64\n";
			$prepare.="\n".chunk_split(base64_encode($body))."\n";
		}
		$prepare.="--OpenAPIInterface-".$random_hash."--";
		ob_start(); //Turn on output buffering
		echo $prepare;
		$message = ob_get_contents();
		ob_end_clean();
		return @mail( $to, stripslashes($subject), $message, $headers );
	}
	
	function getRemoteData($url,$fields=null,$headers=array(),$method='GET'){
		$url=trim($url);
		$values=array('response'=>null,'error'=>null);
		
		$fields=($fields!==null && is_array($fields))?http_build_query($fields):$fields;
		$user_agent=(!empty($_SERVER['HTTP_USER_AGENT']))?$_SERVER['HTTP_USER_AGENT']:'OpenWHMCSAPI-'.rand(10,99);
		$method=strtoupper($method);
		
		if(function_exists('curl_init')) 
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url );
			if($method=='POST'){				
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
			}
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT,$user_agent);
			if ((ini_get('open_basedir')!==false && (int)ini_get('open_basedir')==1) && (ini_get('safe_mode')!==false && (int)ini_get('safe_mode')==1)){
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30 );
			if(substr($url,0,5)=='https'){
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			}
			if(count($headers)>0) curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
			$contents = curl_exec($ch);
			$info = curl_getinfo($ch);
			if(curl_errno($ch)>0) $values["error"].=curl_error($ch)." (Error:".curl_errno($ch)." HttpCode:".$info['http_code]'].")";
			curl_close($ch);
			$values["headers"]=$info ;
			$values["response"]=trim($contents);
		}
		elseif(function_exists('file_get_contents') || empty($values["response"]))
		{
			if($method=='POST'){
				if(!count($headers)) $headers[]='Content-type: application/x-www-form-urlencoded'; //default
				$params = array('http' => array('method' => 'POST','content' => $fields,'follow_location'=>1,'timeout'=>30));
			}else{
				$params = array('http' => array('method' => 'GET','follow_location' =>1,'timeout'=>30));
			}
			$headers[]=$user_agent;
			if (count($headers)>0)	$params['http']['header'] = implode("\r\n",$headers);
			$ctx=@stream_context_create($params);
			$contents=@file_get_contents($url,false,$ctx);
			
			if($contents!==false){
				$values["response"]=trim($contents);
			}else{
				$values["error"]="file_get_contents() error ". error_get_last();
			}
		}else{
			$values["error"]=array('error'=>'Error');
		}
		return $values;
	}

	function print_data($values,$ret=false){
		$data='<pre>'.htmlspecialchars(print_r($values,true)).'</pre>';
		if($ret) return $data;
		echo $data;
	}
}