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
	$configarray = array
	(
		"name" => "WHMCS OpenAPI (WOA)",
		"description" => 'WHMCS OpenAPI (WOA) is an addon that helps developer for fast addon development <a target="_blank" href="https://github.com/zstergios/WHMCS_OpenAPI">gitHub</a>',
		"version" => '1.2',
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