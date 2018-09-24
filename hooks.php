<?php
/**
 * @package		WHMCS openAPI 
 * @version     1.9
 * @author      Stergios Zgouletas <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/
if(!defined("WHMCS")) die("This file cannot be accessed directly");

if(isset($_REQUEST["module"]) && $_REQUEST["module"]=='openAPI' && (int)$_SESSION["adminid"]>0 && strpos($_SERVER["REQUEST_URI"],'addonmodules.php?')!==false && @$_REQUEST["task"]=="ajax"){
	require_once('init.php');
	$api=WOAAPI::getInstance();
	$response=array('error'=>null,'data'=>null);
	$format=(isset($_REQUEST["format"]) &&  !empty($_POST['format']))?strtolower($_POST['format']):'json';
	
	if(count($_POST) && !empty($_POST['addon']))
	{
		$addonName=filter_var($_POST['addon'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		$page=(isset($_POST['page']) && !empty($_POST['page']))?filter_var($_POST['page'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH):'ajax';
		$filePath=ROOTDIR.'/modules/addons/'.$addonName.'/'.$page.'.php';
		
		if(file_exists($filePath))
		{
			require($filePath);
		}
		else
		{
			$response['error']='File '.$filePath.' not exists!';
		}
	}
	else
	{
		$response['error']='"addon" parameter is empty!';	
	}
	
	if($format=='json')
	{
		$api->printJSON($response);	
	}
	else
	{
		header('Content-Type: text/plain');
		foreach($response as $key=>$data)
		{
			$data=is_array($data)?json_encode($response):$data;
			echo $key.'='.$data."\n";
		}
		exit();
	}
}