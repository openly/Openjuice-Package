<?php
defined('C5_EXECUTE') or die("Access Denied.");

class HiddenField extends OJField{
	protected $hidden = true;
	
	public function initialize(){
		$this->template = "{{{field}}}";
		$form = Loader::helper('form');
		if($_SERVER['REQUEST_METHOD'] == "POST")
			$this->default = $_POST[$this->getDisplayFieldName()];
		if(!is_string($this->default)) $this->default = serialize($this->default);
		if($_SERVER['REQUEST_METHOD'] == "POST")
			$_POST[$this->getDisplayFieldName()] = $this->default;
		$this->field = $form->hidden($this->getDisplayFieldName(),$this->default);
	}
}