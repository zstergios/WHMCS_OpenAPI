<?php
/**
 * @package		WHMCS openAPI 
 * @version     3.0.4
 * @author      Stergios Zgouletas | WEB EXPERT SERVICES LTD <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/

if(!defined('WHMCS'))
{
	$whmcsRoot=realpath(dirname(__FILE__)."/../../../").DIRECTORY_SEPARATOR;
	//WHMCS 5.2+
	if (file_exists($whmcsRoot."init.php"))
	{
		require_once($whmcsRoot."init.php");
	}
	else
	{
		require_once($whmcsRoot."dbconnect.php");
		require_once($whmcsRoot."includes/functions.php");
		if(defined("CLIENTAREA")) require_once($whmcsRoot."includes/clientareafunctions.php");
	}
}

if(!defined('WOAPI_DBCAPSULE') && class_exists('Capsule')) define('WOAPI_DBCAPSULE',true);
$classPath=dirname(__FILE__).DIRECTORY_SEPARATOR."classes".DIRECTORY_SEPARATOR;
if(!defined('WOAPI_CLASSPATH')) define('WOAPI_CLASSPATH',$classPath);

if(!class_exists('WOADB')) require($classPath."db.php");
if(!class_exists('WOAAPI')) require($classPath."api.php");  
if(!class_exists('WOAForms')) require($classPath."forms.php");
if(defined("CLIENTAREA") && !class_exists('WOAClientarea')) require($classPath."clientarea.php");
