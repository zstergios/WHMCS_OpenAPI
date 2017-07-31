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

class WOAClientarea{
	protected $ClientArea;
	protected $breadCrump=array();
	protected $pagetitle='Custom Page';
	function __construct($pagetitle=null){
		$this->setPageTitle($pagetitle);
	}
	
	public function setPageTitle($pagetitle=null){
		if(!is_null($pagetitle)){
			$this->pagetitle=$pagetitle;
		}
	}
	
	public function initialize(){
		$api=WOAAPI::getInstance();
		if (version_compare(WHMCSV, '5.2.0') >= 0) {
			$this->ClientArea = new WHMCS_ClientArea();
			$this->ClientArea->setPageTitle($api->getLang($this->pagetitle));
			$this->ClientArea->addToBreadCrumb('index.php',$api->getLang('globalsystemname'));
			if(count($this->breadCrump)){
				foreach($this->breadCrump as $page=>$languageKey){
					$this->ClientArea->addToBreadCrumb($page,$api->getLang($languageKey));
				}
			}
			$this->ClientArea->initPage();
		}else{
			$breadcrumbnav ='<a href="index.php">'.$api->getLang('globalsystemname').'</a>'; 
			if(count($this->breadCrump)){
				foreach($this->breadCrump as $page=>$languageKey){
					$breadcrumbnav.=' > <a href="'.$page.'">'.$api->getLang($languageKey).'</a>';
				}
			}
			initialiseClientArea($this->pagetitle,'',$breadcrumbnav);
		}
	}
	
	public function addBreadcrump($page,$languageKey)
	{
		$this->breadCrump[$page]=$languageKey;
	}
	
	public function setBreadcrump($breadCrump=array())
	{
		$this->breadCrump=$breadCrump;
	}
	
	public function isLoggedIn()
	{
		if (version_compare(WHMCSV, '6.0.0') >= 0)
		{
			return $this->ClientArea->isLoggedIn();
		}
		return (isset($_SESSION['uid']) && (int)$_SESSION['uid']>0)?true:false;
	}
	
	public function getUserID()
	{
		if (version_compare(WHMCSV, '6.0.0') >= 0)
		{
			return $this->ClientArea->getUserID();
		}
		return (int)$_SESSION['uid'];
	}
	
	public function requireLogin()
	{
		if (version_compare(WHMCSV, '6.0.0') >= 0)
		{
			$this->ClientArea->requireLogin();
		}
		else
		{
			if(!$this->isLoggedIn())
			{
				$_SESSION['loginurlredirect'] = $_SERVER['REQUEST_URI'];
				WOAAPI::redirect('clientarea.php');			
			}
		}
	}
	
	public function output($template,$smartyvalues=array())
	{
		if (is_object($this->ClientArea) && version_compare(WHMCSV, '5.2.0') >= 0)
		{
			foreach($smartyvalues as $key =>$val)
			{
				$this->ClientArea->assign($key,$val);
			}
			$this->ClientArea->setTemplate($template);
			$this->ClientArea->output();	
		}
		else
		{
			outputClientArea($template);
		}
	}
}