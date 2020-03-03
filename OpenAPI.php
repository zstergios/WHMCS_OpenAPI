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
function openAPI_config() {
	require('init.php');
	$configarray = array
	(
		"name" => "WHMCS OpenAPI (WOA)",
		"description" => 'WHMCS OpenAPI (WOA) is an addon that helps developer for fast addon development <a target="_blank" href="https://github.com/zstergios/WHMCS_OpenAPI">gitHub</a>',
		"version" => WOAAPI::getVersion(),
		"author" => "Web-expert.gr",
		"language" => "english",
		"fields" => array(),
	);
    return $configarray;
}

function openAPI_activate() {
	return array('status'=>'info','description'=>'Successfully activated!');
}

function openAPI_deactivate() {
	return array('status'=>'info','description'=>'Successfully deactivated!');
}

function openAPI_output($vars) {
	require('init.php');
	$api=WOAAPI::getInstance();
	$data=$api->checkUpdate($vars['version'],'openAPI');
	if(empty($data['error']))
	{
		$data=explode(';',$data['response']);
		$info=array();
		foreach($data as $p)
		{
			if(empty($p)) continue;
			list($key,$value)=explode('=',$p);
			$info[trim($key)]=trim($value);
		}
		
		echo '<p>Your Version is:'.$vars['version'].'</p>';
		echo '<p>Latest Version is:'.$info['version'].' Released: '.$info['released'].'</p>';
		echo '<p>ChangeLog: <a target="_blank" href="'.$info['changelog-url'].'"> View changes</a></p>';
		
		if(version_compare($vars['version'],$info['version'],'eq'))
		{
			echo '<p class="alert alert-success">Well done, you have installed the latest version!</p>';
		}
		else
		{
			echo '<p class="alert alert-danger" style="font-weight:bold;">A newer version of '.$info['name'].' is available v'.$info['version'].'!</p>';
		}
	}
	else
	{
		echo '<p class="alert alert-warning">Error Occured:'.$data['error'].'</p>';
	}
}