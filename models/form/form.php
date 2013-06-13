<?php
Loader::model('form/field','openjuice');
/*
 * Created on Jan 22, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */
class OJForm{
	private $objName;
	private $title;
	private $subTitle;
	
	private $fields = array();

	public function __construct($objName,$title=null,$subTitle = null){
		$this->objName = $objName;
		$this->title = $title?$title:$objName." Details";
		$this->subTitle = $subTitle?$subTitle:"Edit $objName details";
	}

	public function getTitle(){return $this->title;}
	public function getSubTitle(){return $this->subTitle;}
	
	public function preProcess(){
		Loader::element('editor_init');
		Loader::element('editor_config', array('editor_mode' => 'CUSTOM'));
		foreach($this->getHiddenFields() as $hField){
			echo $hField->getField();
		}
	}
	
	public function postProcess(){
		
	}
	
	public function addField($details,$extra=null){
		$class = ucfirst($details['type']) . 'Field';
		$label = $details['label'];
		$fieldName = $details['name'];
		$this->fields[] = new $class($fieldName,$label,$details['default'],$extra);
	}
	
	public function getHiddenFields(){
		$retval = array();
		foreach($this->fields as $field){
			if($field->isHidden())
				$retval[] = $field;
		}
		return $retval;
	}
	
	public function getFields(){
		$retval = array();
		foreach($this->fields as $field){
			if(!$field->isHidden())
				$retval[] = $field;
		}
		return $retval;
	}
	
	public function buttons(){
		$ih = Loader::helper('concrete/interface');
		$btnStr = $ih->submit('Save',null,'right','primary');
		$btnStr .= $ih->button_js('Cancel','history.back()','left');
		return $btnStr;
	}
}
