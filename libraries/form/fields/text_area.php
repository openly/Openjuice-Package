<?php
defined('C5_EXECUTE') or die("Access Denied.");

class TextAreaField extends OJField{
	public function initialize(){
		parent::initialize();
		$form = Loader::helper('form');
		$this->field = $form->textarea($this->getDisplayFieldName(),$this->default,$this->fieldAttrs);
	}
}
