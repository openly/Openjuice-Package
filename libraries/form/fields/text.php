<?php
defined('C5_EXECUTE') or die("Access Denied.");

class TextField extends OJField{
	public function initialize(){
		parent::initialize();
		$form = Loader::helper('form');
		$this->field = $form->text($this->getDisplayFieldName(),$this->default,$this->fieldAttrs);
	}
}