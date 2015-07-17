<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");
if(!class_exists('WDB')) require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."classes".DIRECTORY_SEPARATOR."db.php");
if(!class_exists('WAPI'))require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."classes".DIRECTORY_SEPARATOR."api.php");
if(defined("CLIENTAREA") && !class_exists('WClientarea')) require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."classes".DIRECTORY_SEPARATOR."clientarea.php");

define("CLIENTAREA",true);
define("FORCESSL",true);
$openAPI=ROODIR."/modules/addons/openAPI/init.php";
if(!file_exists($openAPI)) exit('This addon requires openAPI addon module');
require($openAPI);

$pagetitle='MY_TITLE';
$ca=new WClientarea($pagetitle);
$ca->setBreadcrump(array('mypage.php'=>'languageKey'));
$ca->initialize();
$ca->output("managesms",$smartyvalues);
