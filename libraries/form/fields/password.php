<?php
defined('C5_EXECUTE') or die("Access Denied.");

class PasswordField extends OJField{
	public function initialize(){
		parent::initialize();
		$form = Loader::helper('form');
		$this->field = $form->password($this->getDisplayFieldName(),$this->default,$this->fieldAttrs);
	}
}