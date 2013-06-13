<?php
defined('C5_EXECUTE') or die("Access Denied.");

class RequiredValidator extends OJValidator{
	public function validate($args){
	//	var_dump($args);
		if(OJUtil::checkNotBlank($args[$this->getDisplayFieldName()])) {
			return true;
		}
		$this->error = array(
			'message' => t('Field "%s" is required.',$this->label),
			'name' => $this->fieldName
		);
		return false;
	}
}
