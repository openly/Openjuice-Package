<?php
defined('C5_EXECUTE') or die("Access Denied.");

class ImageField extends OJField{
	public function initialize(){
		$ast = Loader::helper('concrete/asset_library');
		$this->field = $ast->image($this->getDisplayFieldName(), $this->getDisplayFieldName(), $this->label,$this->default?File::getByID($this->default):null);
	}
}