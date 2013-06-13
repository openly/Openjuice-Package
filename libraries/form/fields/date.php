<?php
defined('C5_EXECUTE') or die("Access Denied.");

class DateField extends OJField{
	public function initialize(){
		parent::initialize();
		$dtt = Loader::helper('form/date_time');
		$this->field = $dtt->date($this->getDisplayFieldName(),$this->default,true,$this->fieldAttrs);
	}
}