<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");
function OpenAPI_config() {
	$configarray = array
	(
		"name" => "WHMCS OpenAPI",
		"description" => "OpenAPI is an addon thet helps developer for fast development",
		"version" => '1.0',
		"author" => "Web-expert.gr",
		"language" => "english",
		"fields" => array()
	);
    return $configarray;
}

function OpenAPI_activate() {
	return array('status'=>'info','description'=>'Success');
}

function OpenAPI_deactivate() {
	return array('status'=>'info','description'=>'Success');
}