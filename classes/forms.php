<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WForms{
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
	
	public static function setAddon($addon){
		$this->addon=$addon;
		$this->addonPath=ROOTDITR.'/modules/addons/'.$this->addon;
		$this-addonLink='addonmodules.php?module='.$this->addon;
	}
	
	function load($task){
		$page=$this->addonPath.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.strtolower($task).".php";
		$function="Page".ucfirst(strtolower($task));
		if(file_exists($page)){
			require_once($page);
			$classname= ucfirst(strtolower($task))."Forms";
			$pageclass = new $classname();
			if(!method_exists($pageclass,$function)) return '<h3>Sorry! The page <b>'.$task.'</b> is not found</h3>';
			return $pageclass->$function();
		}
		if(!method_exists($this,$function)) return '<h3>Sorry! The page <b>'.$task.'</b> is not found</h3>';
		return $this->$function();
	}
	
	function footer($footer){
		return $footer;
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
		$html='<div>';
		$html.='<div id="tabs" style="width:100%;"><ul>'; 
		$menuhtml=$contenthtml="";
		for($i=0;$i<count($tabs);$i++){
			$menuhtml.='<li id="tab'.$i.'" class="tab" style="border:1px solid #ccc;"><a href="javascript:;">'.$tabs[$i]['title'].'</a></li>';
			$contenthtml.='<div id="tab'.$i.'box" class="tabbox"><div id="tab_content">'.$tabs[$i]['content'].'</div></div>';
		}
		$html=$js.$html.$menuhtml.'</ul>'.$contenthtml.'</div></div>';
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
	
	function checkboxes($name,$options,$selected,$extra="",$keyname='value',$sep=' '){
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
	function radioboxes($name,$options,$selected,$keyname='value',$extra="",$sep=' '){
		$checboxes=$this->checkboxes($name,$options,$selected,$keyname,$extra,$sep);
		return str_replace(array("checkbox","chkbox",'[]"'),array("radio","rdbox",'"'),$checboxes);
	}
}