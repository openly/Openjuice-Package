<?php
defined('C5_EXECUTE') or die("Access Denied.");

class RichTextField extends OJField{
	public function initialize(){
		parent::initialize();
		$this->fieldAttrs['class'] .= ' ccm-advanced-editor';
		$form = Loader::helper('form');
		$this->field = $form->textarea($this->getDisplayFieldName(),$this->default,$this->fieldAttrs);
		if(!$_GLOBALS['mce_conf']){
			Loader::element('editor_init');
			Loader::element('editor_config', array('editor_mode' => 'ADVANCED'));
			$_GLOBALS['mce_conf'] = 1;
		}
	}
}