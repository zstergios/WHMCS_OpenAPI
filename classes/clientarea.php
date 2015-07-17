<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WClientarea{
	protected $ca;
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
	
	public function initialize($pagetitle=null){
		$api=WAPI::getInstance();
		$this->setPageTitle($pagetitle);
		
		$whmcsRoot=realpath(dirname(__FILE__)."/../../../../").DIRECTORY_SEPARATOR;
		if (version_compare(WHMCSV, '5.2.0') >= 0) {
			require($whmcsRoot."init.php");
		}else{
			require($whmcsRoot."dbconnect.php");
			require($whmcsRoot."includes/functions.php");
			require($whmcsRoot."includes/clientareafunctions.php");
		}
		
		if (version_compare(WHMCSV, '5.2.0') >= 0) {
			$this->ca = new WHMCS_ClientArea();
			$this->ca->setPageTitle($api->getLang($this->pagetitle));
			$this->ca->addToBreadCrumb('index.php',$api->getLang('globalsystemname'));
			if(count($this->breadCrump)){
				foreach($this->breadCrump as $page=>$languageKey){
					$this->ca->addToBreadCrumb($page,$api->getLang($languageKey));
				}
			}
			$this->ca->initPage();
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
	
	public function addBreadcrump($page,$languageKey){
		$this->breadCrump[$page]=$languageKey;
	}
	
	public function setBreadcrump($breadCrump=array()){
		$this->breadCrump=$breadCrump;
	}
	
	public function output($template,$smartyvalues=array()){
		if (version_compare(WHMCSV, '5.2.0') >= 0) {
			foreach($smartyvalues as $key =>$val){
				$this->ca->assign($key,$val);
			}
			$this-ca->setTemplate($template);
			$this-ca->output();	
		}else{
			outputClientArea($template);
		}
	}
}