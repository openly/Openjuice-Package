<?php
defined('C5_EXECUTE') or die("Access Denied.");

class UserField extends OJField{
	public function initialize(){
		parent::initialize();
		$uh = Loader::helper('form/user_selector');
		$this->field = $uh->selectUser($this->getDisplayFieldName(),$this->default);
	}
}