<?php
defined('C5_EXECUTE') or die("Access Denied.");

class EmailValidator extends OJValidator{
	public function validate($args){
		$valueToTest = $args[$this->getDisplayFieldName()];
		if(!OJUtil::checkNotBlank($valueToTest) || preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9\._-])+.([a-zA-Z0-9\._-]+)+$/',$valueToTest)) {
			return true;
		}
		$this->error = array(
			'message' => t('Field "%s" is not a vaild email address.',$this->label),
			'name' => $this->fieldName
		);
		return false;
	}
}
?>
