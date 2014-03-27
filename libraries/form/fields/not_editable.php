<?php
defined('C5_EXECUTE') or die("Access Denied.");

class NotEditableField extends OJField{
	public function initialize(){
		parent::initialize();
		$form = Loader::helper('form');
		$this->field = $form->hidden($this->getDisplayFieldName(),$this->default) . t('<b>%s</b>',$this->default);
	}
}