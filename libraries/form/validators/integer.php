<?php
defined('C5_EXECUTE') or die("Access Denied.");

class IntegerValidator extends OJValidator{
	public function validate($args){
		$valueToTest = $args[$this->getDisplayFieldName()];
		if(!OJUtil::checkNotBlank($valueToTest) || $this->isInteger($valueToTest)) {
			return true;
		}
		$this->error = array(
			'message' => t('Field "%s" is not an integer.',$this->label),
			'name' => $this->fieldName
		);
		return false;
	}

	private function isInteger($data){
		if (is_int($data)) {
			return true;
		} else if (is_string($data) === true && is_numeric($data) === true) {
			return (strpos($data, '.') === false);
		}
		return false;
	}
}
