<?php
/**
 * @package		WHMCS openAPI 
 * @version     1.2
 * @author      Stergios Zgouletas <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/

if(!defined("WHMCS")) die("This file cannot be accessed directly");

function openAPI_config() {
	require_once('init.php');
	$configarray = array
	(
		"name" => "WHMCS OpenAPI (WOA)",
		"description" => 'WHMCS OpenAPI (WOA) is an addon that helps developer for fast addon development <a target="_blank" href="https://github.com/zstergios/WHMCS_OpenAPI">gitHub</a>',
		"version" => WOAAPI::$version,
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
	require_once('init.php');
	$api=WOAAPI::getInstance();
	$data=$api->checkUpdate($vars['version'],'openAPI');
	
	if(empty($data['error'])){
		$data=explode(';',$data['response']);
		$info=array();
		foreach($data as $p){
			list($key,$value)=explode('=',$p);
			$info[$key]=$value;
		}
		echo '<p>Your Version:'.$vars['version'].'</p>';
		echo '<p>Latest Version:'.$vars['version'].' Released: '.$vars['released'].'</p>';
		echo '<p>Change-log: <a target="_blank" href="'.$vars['changelog-url'].'"> View changes</a></p>';
		if(version_compare($vars['version'],'eq')){
			echo '<p class="alert alert-success">Well done, you have installed the latest version!</p>';
		}
		else
		{
			echo '<p class="alert alert-warning">You should consider upgrading to latest version v'.$vars['version'].'!</p>';
		}
	}
	else
	{
		echo '<p class="alert alert-danger">Error Occured:'.$data['error'].'</p>';
	}
}