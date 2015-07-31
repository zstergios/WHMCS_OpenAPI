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

class WOAForms{
	private static $instance;
	protected $addon;
	protected $addonPath;
	protected $addonLink;
	
	function __construct($addon=''){
		if(!empty($addon)) $this->setAddon($addon);
	}
	
	public static function getInstance($addon='') {
		if(!self::$instance) self::$instance = new self($addon);
		return self::$instance;
	}
	
	public function setAddon($addon){
		$this->addon=$addon;
		$this->addonPath=ROOTDIR.'/modules/addons/'.$this->addon;
		$this->addonLink='addonmodules.php?module='.$this->addon;
	}
	
	public function getAddon(){
		return $this->addon;
	}
	
	public function getAddonPath(){
		return $this->addonPath;
	}
	
	public function getAddonLink(){
		return $this->addonLink;
	}
	
	function load($view){
		$page=$this->addonPath.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.strtolower($view).".php";
		$function="Page".ucfirst(strtolower($view));
		if(file_exists($page)){
			require_once($page);
			$classname= ucfirst(strtolower($view))."Forms";
			$pageclass = new $classname();
			if(!method_exists($pageclass,$function)) return '<h3>Sorry! The page <b>'.$view.'</b> not found ('.$pageclass.'->'.$function.')</h3>';
			return $pageclass->$function();
		}
		if(!method_exists($this,$function)) return '<h3>Sorry! The page file <b>'.$view.'</b> not found</h3>';
		return $this->$function();
	}
	
	function tabber($tabs){
		if(!is_array($tabs)) return '';
		$js='<script>$(document).ready(function(){
		$(".tabbox").css("display","none");
		var selectedTab;
		$(".tab").click(function(){
			var elid = $(this).attr("id");
			$(".tab").removeClass("tabselected");
			$("#"+elid).addClass("tabselected");
			if (elid != selectedTab) {
				$(".tabbox").slideUp();
				$("#"+elid+"box").slideDown();
				selectedTab = elid;
			}
			$("#tab").val(elid.substr(3));
		});
		selectedTab = "tab0";
		$("#tab0").addClass("tabselected");
		$("#tab0box").css("display","");
		  });</script>';
		//if(version_compare(WHMCSV,'6.0.0','ge')) $js='<script>$(document).ready(function(){$( "a[href^=\'#tab\']" ).click( function() {  var tabID = $(this).attr(\'href\').substr(4); $("#tab").val(tabID);});</script>';
		$html='<div id="tabs" style="width:100%;"><ul class="nav nav-tabs admin-tabs" role="tablist">'; 
		$menuhtml=$contenthtml="";
		for($i=0;$i<count($tabs);$i++){
			//$id=version_compare(WHMCSV,'6.0.0','ge')?'tabLink':'tab';
			$id='tab';
			$menuhtml.='<li role="tab" data-toggle="tab" id="'.$id.$i.'" class="tab woatab"><a href="#tab'.$i.'">'.$tabs[$i]['title'].'</a></li>';
			//$idBox=version_compare(WHMCSV,'6.0.0','ge')?'tab'.$i:'tab'.$i.'box';
			$idBox='tab'.$i.'box';
			$contenthtml.='<div id="'.$idBox.'" class="tabbox"><div id="tab_content">'.$tabs[$i]['content'].'</div></div>';
		}
		$html=$js.$html.$menuhtml.'</ul><div class="tab-content admin-tabs">'.$contenthtml.'</div></div>';
		return $html;
	}
	
	function createTable($rows,$w='100%',$h='100%',$class='',$bgRowColor='#E5E5E5'){
		if(!is_array($rows)) return '';
		$html='<table class="datatable wtable '.$class.'" border="0" height="'.$h.'" width="'.$w.'" cellspacing="0" cellpadding="2">';
		$c=0;
		foreach($rows as $key => $row){
			$id=str_replace(array(' ','_'),'',$key);
			$html.='<tr id="tr_'.$id.'">';
			$bgcolor=($c%2==0 && !empty($bgRowColor))?' bgcolor="'.$bgRowColor.'"':'';
			foreach($row as $td){
				$height=((int)$td['height']>0)?' height="'.(int)$td['height'].'" ':'';
				$width=((int)$td['width']>0)?' width="'.(int)$td['width'].'" ':'';
				$style=(isset($td['style']))?' style="'.$td['style'].'"':'';
				$extra=(isset($td['extra']))?' '.$td['extra']:'';
				$class=(isset($td['class']))?' class="'.$td['class'].'"':'';
				$html.='<td id="td_'.$id.'"'.$height.$width.$bgcolor.$style.$class.$extra.'>'.$td['html'].'</td>';
			}
			$html.='</tr>';
			$c++;
		}
		$html.='</table>';
		return $html;
	}
	
	function selectbox($name,$options=array(),$selected=array(),$extra="",$keyname='value'){
		if(!is_array($options)) return '';
		if(!is_array($selected)) $selected=explode(",",$selected);
		$id=str_replace(array('[',']'),'',$name);
		
		$html='<select name="'.$name.'" id="'.$id.'" '.$extra.'>';
		foreach($options as $k=>$v){
			$boxname=$boxvalue=$v;
			if($keyname=='key'){
				$boxname=$v;
				$boxvalue=$k;
			}
			$checked=in_array($boxvalue,$selected)?' selected':'';
			$html.='<option value="'.$boxvalue.'"'.$checked.'>'.$boxname.'</option>';
		}
		$html.='</select>';
		return $html;
	}
	
	function checkboxes($name,$options=array(),$selected=array(),$extra="",$keyname='value',$sep=' '){
		if(!is_array($options)) return false;
		if(!is_array($selected)) $selected=explode(",",$selected);
		$id=str_replace(array('[',']'),'',$name);
		
		foreach($options as $k=>$v){
			$boxname=$boxvalue=$v;
			if($keyname=='key'){
				$boxname=$v;
				$boxvalue=$k;
			}
			$theID=$id.'_'.$k;
			$checked=in_array($boxvalue,$selected)?' checked':'';
			$boxes[]='<span class="achkbox" id="'.$theID.'"><input id="'.$theID.'" type="checkbox" name="'.$name.'" value="'.$boxvalue.'"'.$checked .$extra.' /> '.$boxname.'</span>';
		}
		return '<div class="chkbox">'.implode($sep,$boxes).'</div>';
	}
	
	function radioboxes($name,$options=array(),$selected=array(),$extra="",$keyname='value',$sep=' '){
		$checboxes=$this->checkboxes($name,$options,$selected,$extra,$keyname,$sep);
		return str_replace(array("checkbox","chkbox","achkbox"),array("radio","rdbox",'ardkbox'),$checboxes);
	}
}