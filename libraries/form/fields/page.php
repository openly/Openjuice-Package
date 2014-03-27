<?php
defined('C5_EXECUTE') or die("Access Denied.");

class PageField extends OJField{
	public function initialize(){
		parent::initialize();
		$pageSelector = Loader::helper('form/page_selector');
		$this->field = '<div id="'.$this->getDisplayFieldName().'">' . $pageSelector->selectPage($this->getDisplayFieldName(), $this->default) . '</div>'; 
	}
}