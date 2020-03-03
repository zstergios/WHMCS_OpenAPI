<?php
/**
 * @package		WHMCS openAPI 
 * @version     3.0.3
 * @author      Stergios Zgouletas | WEB EXPERT SERVICES LTD <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WOAAPI
{
	private static $instance;
	private static $version='3.0.3';
	protected $debug=false;
	protected $db=null;
	protected $moduleConfig=array();
	protected $whmcsconfig=null;
	protected $updateServers=array();
	protected $timeout=30;
	protected $languages=array();
	protected $countries=array('countries'=>array(),'callingCodes'=>array());
	
	function __construct()
	{
		$this->db=WOADB::getInstance();
		$whmcs=$this->getWhmcsConfig();
		list($Version,$Release)=@explode('-',$whmcs["Version"]);
		if(!defined('WHMCSV')) define('WHMCSV',$Version);
		$this->setUpdateServer('https://raw.githubusercontent.com/zstergios/WHMCS_OpenAPI/master/update.ini?t='.time(),'openAPI');
	}
	
	public static function getInstance()
	{
		if(!self::$instance) self::$instance = new self();
		return self::$instance;
	}
	
	public static function getVersion()
	{
		return self::$version;
	}
	
	public function getLang($key)
	{
		global $_LANG;
		$languageTxt=isset($_LANG[$key])?$_LANG[$key]:$key;
		if($this->debug && !isset($_LANG[$key])) $languageTxt='*'.$key.'*';
		return $languageTxt;
	}
	
	public function getAddonLang($language='',$module,$fallback=true)
	{
		if(empty($language) && isset($_SESSION['Language'])) $language=$_SESSION['Language'];
		if(empty($language)) $language='english';
		$language=strtolower($language);
		
		//If isalready loaded return strings	
		if(isset($this->languages[$module][$language]) && count($this->languages[$module][$language])) return $this->languages[$module][$language];
		
		//Load basic language files
		$languagePath=ROOTDIR.'/modules/addons/'.$module.'/lang/';
		$loadLanguage=file_exists($languagePath.$language.'.php')?$languagePath.$language.'.php':$languagePath.'english.php';
		require($loadLanguage);
		$this->languages[$module][$language]=$_ADDONLANG;
		
		//Overrides
		$languagePathOverride=ROOTDIR.'/modules/addons/'.$module.'/lang/overrides/';
		if(file_exists($languagePathOverride.$language.'.php'))
		{
			$_ADDONLANG=array();
			require($languagePathOverride.$language.'.php');
			if(count($_ADDONLANG)) $this->languages[$module][$language]=array_merge($this->languages[$module][$language],$_ADDONLANG);
		}
		
		//fallback
		if($fallback && $language!='english')
		{
			$defaultLanguage=isset($this->languages[$module]['english'])? $this->languages[$module]['english'] : $this->getAddonLang('english',$module,false);
			$this->languages[$module][$language]=array_merge($defaultLanguage,$this->languages[$module][$language]);
		}
		
		return $this->languages[$module][$language];
	}
	
	public function getUpdateServer($module)
	{
		return isset($this->updateServers[$module])?$this->updateServers[$module]:NULL;
	}
	
	public function setUpdateServer($url,$module)
	{
		$this->updateServers[$module]=$url;
	}
	
	public function checkUpdate($currentVersion,$module,$url='')
	{
		
		if(!empty($url))
		{
			$this->setUpdateServer($url,$module);
		}
			
		if(isset($this->updateServers[$module]))
		{
			$data=$this->getRemoteData($this->updateServers[$module],array('version'=>$currentVersion));
		}
		else
		{
			$data=array('response'=>null,'error'=>'Update server URL for "'.$module.'" has not been set');
		}
		
		return $data;
	}
	
	public function callAPI($values,$username=NULL)
	{
		$rs = localAPI($values["action"],$values,$username);
		return $rs;
	}
	
	function printJSON($data=array())
	{
		http_response_code(200); //for HTTP 2
		header('Content-Type: application/json; charset=utf-8',true);
		exit(json_encode($data));
	}
	
	public static function redirect($url,$seconds=0)
	{
		if(headers_sent() || $seconds>0)
		{
			echo "<script>setTimeout(\"location.href = '".$url."';\",".($seconds*1000).");</script>";
		}
		else
		{
			@header('Location:'.$url);
			exit();
		}
	}
	
	/*Current Page URL*/
	public function curPageURL($onlyDomain=false) {
		$pageURL = 'http';
		if($_SERVER["HTTPS"] == "on") $pageURL .= "s";
		$pageURL .= "://".$_SERVER["SERVER_NAME"];
		if($onlyDomain) return $pageURL;
		$pageURL.=$_SERVER["REQUEST_URI"];
		return str_replace('&amp;','&',$pageURL);
	}
	
	
	//Utf-8 String Handling
	################################################
	function strpos($str,$needle,$offset=0)
	{
		return function_exists('mb_strpos')?mb_strpos($str,$needle,$offset):strpos($str,$needle,$offset);
	}
	
	function substr($str,$i=null,$j=null)
	{
		return function_exists('mb_substr')?mb_substr($str,$i,$j):substr($str,$i,$j);
	}
	
	function strlen($str)
	{
		return function_exists('mb_strlen')?mb_strlen($str):strlen($str);
	}
	
	function ucfirst($str)
	{
		return ucfirst(strtolower($str));
	}
	
	//File/Folder Functions
	public function getFiles($folderPath,$ext=array())
	{
		$folderPath=rtrim($folderPath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		if(!is_dir($folderPath)) return array();
		$files=array();
		$results = scandir($folderPath);
		foreach ($results as $file)
		{
			if ($file === '.' || $file === '..' || !is_file($folderPath.$file)) continue;
			$extension=@end(@explode(".",$file,2));
			$filename=str_replace('.'.$extension,'',$file);
			if(count($ext)>0 && !in_array(".".$extension,$ext)) continue;
			$files[]=$file;
		}
		return $files;
	}
	
	public function getFolders($folderPath)
	{
		if(!is_dir($folderPath)) return array();
		
		$folders=array();
		foreach (new DirectoryIterator($folderPath) as $file)
		{
			if($file->isDot()) continue;
			if($file->isDir()) $folders[]=$file->getFilename();
		}
		return $folders;
	}
	
	//Delete Folder with all files
	//Returns true|false
	public function deleteFolder($folderPath)
	{
		$folderPath=rtrim($folderPath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		if(!is_dir($folderPath)) return false;
		
	 	$files = glob($folderPath.'*'); // get all file names
		if(count($files))
		{
			foreach($files as $file)
			{
			  if(is_file($file)) unlink($file); // delete file
			}
		}
		return rmdir($folderPath);
	}
	
	/*Array Functions*/
	public function array_remove($input=array(),$remove=array())
	{
		$arr = array_diff((array)$input,(array)$remove);
		return array_values($arr);
	}
	
	public function getCountiesDetails()
	{
		if(count($this->countries['countries']) && count($this->countries['callingCodes'])) return $this->countries;
		$countryFile=file_exists(ROOTDIR.'/resources/country/countries.json')?'countries.json':'dist.countries.json';
		if(file_exists(ROOTDIR.'/resources/country/'.$countryFile))
		{
			$data=json_decode(@file_get_contents(ROOTDIR.'/resources/country/'.$countryFile),true);
			foreach($data as $code=>$cdata)
			{
				$this->countries['countries'][$code]=$cdata['name'];
				$this->countries['callingCodes'][$code]=$cdata['callingCode'];
			}
		}
		else
		{
			require(ROOTDIR.'/includes/countries.php');
			$this->countries['countries']=$countries;
			require(ROOTDIR.'/includes/countriescallingcodes.php');
			$this->countries['callingCodes']=$countrycallingcodes;
		}
		return $this->countries;
	}
	
	
	public function getCountries()
	{
		$this->getCountiesDetails();
		return $this->countries['countries'];
	}

	public function getCallingCodes()
	{
		$this->getCountiesDetails();
		return $this->countries['callingCodes'];
	}
	
	#Get WHMCS Config
	public function getWhmcsConfig($key='')
	{
		global $CONFIG;
		//Read from Global Var if is set
		if(is_array($CONFIG) && count($CONFIG))
		{
			$this->whmcsconfig=$CONFIG;
		}
		if(!is_array($this->whmcsconfig))
		{
			$this->whmcsconfig=array();
			$rs=$this->db->query("SELECT * FROM `tblconfiguration`;");
			while ($data = $this->db->fetch_array($rs))
			{
				$this->whmcsconfig[$data["setting"]]=$data["value"];
			}
		}
		
		if(!empty($this->whmcsconfig['SystemSSLURL'])) $this->whmcsconfig['SystemSSLURL']=rtrim($this->whmcsconfig['SystemSSLURL'],'/').'/';
		$this->whmcsconfig['SystemURL']=rtrim($this->whmcsconfig['SystemURL'],'/').'/';
		
		if(!empty($key) && $key=='SystemSSLURL' && empty($this->whmcsconfig[$key])) $key='SystemURL';
		if(!empty($key) && is_array($this->whmcsconfig)) return isset($this->whmcsconfig[$key])?$this->whmcsconfig[$key]:NULL;
		return $this->whmcsconfig;
	}
	
	#Get Addon Config
	public function getModuleParams($key=null,$module)
	{
		if(!isset($this->moduleConfig[$module]) || !count($this->moduleConfig[$module]))
		{
			
			$this->db->query('SELECT setting,value FROM `tbladdonmodules` WHERE module='.$this->db->quoteValue($module).';');
			$this->moduleConfig[$module]=array();
			while($row=$this->db->fetch_array())
			{
				$this->moduleConfig[$module][$row['setting']]=trim($row['value']);
			}
		}
		if(!empty($key) && is_array($this->moduleConfig[$module]) && isset($this->moduleConfig[$module][$key])) return $this->moduleConfig[$module][$key];
		return $this->moduleConfig[$module];
	}
	
	public function setModuleParams($name,$value='',$module)
	{
		$this->db->query('UPDATE `tbladdonmodules` SET value='.$this->db->quoteValue($value).' WHERE setting='.$this->db->quoteValue($name).' AND module='.$this->db->quoteValue($module).';');
		$this->moduleConfig[$module][$name]=$value;
	}
	
	public function logActivity($logtxt='',$module='openAPI')
	{
		if(empty($logtxt)) return false;
		LogActivity($module.' - '.$logtxt);
		return true;
	}
	
	function highlightKeyword($haystack,$needle,$color = "#daa732")
	{
		return preg_replace("/($needle)/i",sprintf('<span style="color:%s;">$1</span>',$color),$haystack);
	}
	
	//SendTo Parameter can be string or array $sendTo=array('to'=>array(),'cc'=>array(),'bcc'=>array());
	public function sendEmail($sendTo,$subject,$body,$frommail='',$fromname='WHMCS System',$AllowHTML=true,$charset="utf-8",$files=array())
	{
		if((is_array($sendTo) && !count($sendTo)) || (!is_array($sendTo) && (empty($sendTo) || $this->strpos($sendTo,'@')===false))) return array('send'=>false,'error'=>'No email');
		
		if(!class_exists('PHPMailer'))
		{
			if(file_exists(ROOTDIR.'/includes/classes/PHPMailer/PHPMailerAutoload.php'))
			{
				require_once(ROOTDIR.'/includes/classes/PHPMailer/PHPMailerAutoload.php');
			}
			elseif(file_exists(ROOTDIR.'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php'))
			{
				require_once(ROOTDIR.'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
			}
			elseif(file_exists(ROOTDIR.'/vendor/phpmailer/phpmailer/src/PHPMailer.php'))
			{
				require_once(ROOTDIR.'/vendor/phpmailer/phpmailer/src/PHPMailer.php');
				require_once(ROOTDIR.'/vendor/phpmailer/phpmailer/src/SMTP.php');
				require_once(ROOTDIR.'/vendor/phpmailer/phpmailer/src/Exception.php');
			}
			else
			{
				 return array('send'=>false,'error'=>'PHPMailer not found');	
			}
		}
				
		$whmcs=$this->getWhmcsConfig();
		if($frommail=='')
		{
			$frommail=trim($whmcs["SystemEmailsFromEmail"]);
			$fromname=trim($whmcs["SystemEmailsFromName"]);
		}
		
		$plainbody= strip_tags(preg_replace("/<br(.*)>|<newline>/iU", "\n", str_replace("</p>","</p><newline>", $body)) );
		
		#Multi-Language Support
		if(function_exists('iconv') && function_exists('mb_detect_encoding') && mb_detect_encoding($plainbody)!=strtoupper($charset))
		{
			$plainbody=@iconv(mb_detect_encoding($plainbody),$charset,$plainbody); //plain
			$body=@iconv(mb_detect_encoding($body),$charset,$body); #html
		}
		
		$body='<style>'.$whmcs["EmailCSS"].'</style>'.$body;
		
		$isMailDisabled = !function_exists('mail') || in_array('mail', explode(',', ini_get('disable_functions')));				
				
		$mail = new PHPMailer(true);
		try
		{
			if($whmcs['MailType']=='smtp' && !empty($whmcs['SMTPUsername']) && !empty($whmcs['SMTPPassword']))
			{
				$rsp=$this->callAPI(array('action'=>'DecryptPassword','password2'=>$whmcs['SMTPPassword']));
				if($rsp['result']=='success')
				{
					$mail->SMTPDebug = 0;
					$mail->isSMTP();
					$mail->SMTPAuth = true;
					$mail->Host=$whmcs['SMTPHost'];
					$mail->Port=$whmcs['SMTPPort'];
					$mail->Username=$whmcs['SMTPUsername'];
					$mail->Password=$rsp['password'];
				
					if(!empty($whmcs['SMTPSSL']))
					{			
						$mail->SMTPSecure =$whmcs['SMTPSSL'];
					}
					
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true
						)
					);
				}
				else if($isMailDisabled)
				{
					return array('send'=>false,'error'=>'Failed to descrypt SMTP password');
				}
			}
			else if($whmcs['MailType']=='mail')
			{
				if($isMailDisabled) return array('send'=>false,'error'=>'Mail() is disabled!');
				$mail->isMail();
			}
						
			$encodings=array(0=>'8bit',1=>'7bit',2=>'binary',3=>'base64');
			if(in_array($whmcs['MailEncoding'],$encodings))
			{
				$mail->Encoding=$encodings[$whmcs['MailEncoding']];
			}
		
			$mail->CharSet=$charset;
			$mail->setFrom($frommail,$fromname);
			$mail->Subject = $subject;
			if($AllowHTML) $mail->msgHTML($body);
			$mail->AltBody =$plainbody;
			
			if(is_array($sendTo))
			{
				foreach(array('to','cc','bcc') as $type)
				{
					if(isset($sendTo[$type]) && count((array)$sendTo[$type]))
					{
						foreach((array)$sendTo[$type] as $email=>$emailName)
						{
							$email=trim($email);
							if($this->strpos($email,'@') === false && $this->strpos($emailName,'@') !== false)
							{
								$email=$emailName;
								$emailName='';
							}
							if(filter_var($email,FILTER_VALIDATE_EMAIL) === false) continue;
							if($type=='to')	$mail->addAddress($email,$emailName);
							elseif($type=='cc')	$mail->addCC($email,$emailName);
							elseif($type=='bcc') $mail->addBCC($email,$emailName);
						}
					}
				}
			}
			else
			{
				$emails=@explode(',',$sendTo);
				foreach($emails as $email) $mail->addAddress(trim($email),'');
			}
			
			if(count($files)>0)
			{
				foreach($files as $file)
				{
					if(file_exists($file) && filesize($file)>0)
					{
						$mail->addAttachment($file);
					}
				}
			}
			$result=$mail->send();
		}
		catch (phpmailerException $e)
		{
			return array('send'=>false,'error'=>$e->errorMessage());
		}
		catch (Exception $e)
		{
			return array('send'=>false,'error'=>$e->getMessage());
		}
		
		return array('send'=>$result,'error'=>$mail->ErrorInfo);
	}
	
	//Set Connection Timeout | int
	function setTimeout($timeout)
	{
		$this->timeout=(int)$timeout;
	}
	
	function getRemoteData($url,$fields=null,$headers=array(),$method='GET')
	{
		//Prepare Variables
		$url=trim($url);
		$values=array('response'=>null,'error'=>null);
		$method=strtoupper($method);
		$fields=($fields!==null && is_array($fields))?http_build_query($fields):$fields;
		$user_agent=(!empty($_SERVER['HTTP_USER_AGENT']))?$_SERVER['HTTP_USER_AGENT']:'OpenWHMCSAPI-'.self::$version;
		
		$port=(int)parse_url($url, PHP_URL_PORT);
		
		if(function_exists('curl_init')) 
		{
			$ch = curl_init();
			
			if($port!=80 && $port!=443)
			{
				curl_setopt ( $ch, CURLOPT_PORT, $port );
				$url=str_replace(":".$port,'',$url);
			}
			curl_setopt($ch, CURLOPT_URL, $url );
			if($method=='POST')
			{
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
			}
			
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT,$user_agent);
			if ((ini_get('open_basedir')!==false && (int)ini_get('open_basedir')==1) && (ini_get('safe_mode')!==false && (int)ini_get('safe_mode')==1))
			{
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
			if(substr($url,0,5)=='https')
			{
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
			if($method=='POST')
			{
				if(!count($headers)) $headers[]='Content-type: application/x-www-form-urlencoded'; //default
				$params = array('http' => array('method' => 'POST','content' => $fields,'follow_location'=>1,'timeout'=>$this->timeout));
			}
			else
			{
				$params = array('http' => array('method' => 'GET','follow_location' =>1,'timeout'=>$this->timeout));
			}
			
			$headers[]='User-agent: '.$user_agent;
			if (count($headers)>0)	$params['http']['header'] = implode("\r\n",$headers);
			$ctx=@stream_context_create($params);
			$contents=@file_get_contents($url,false,$ctx);
			
			if($contents!==false){
				$values["response"]=trim($contents);
			}
			else
			{
				$errr=error_get_last();
				$values["error"]="file_get_contents() error ".$errr['message'];
			}
		}
		else
		{
			$values["error"]='Error';
		}
		return $values;
	}
	
	function print_data($values,$ret=false)
	{
		$data='<pre>'.htmlspecialchars(print_r($values,true)).'</pre>';
		if($ret) return $data;
		echo $data;
	}
	
	public function dump($var)
	{
		ob_start();
		var_dump($var);
		return ob_get_clean();
	}
	
	function setDebug($status)
	{
		$this->debug=(boolean)$status;
	}
	
	public function microtime_float()
	{
		list ($msec, $sec) = @explode(' ', microtime());
		$microtime = (float)$msec + (float)$sec;
		return $microtime;
	}
}