<?php
defined('C5_EXECUTE') or die("Access Denied.");

class DecimalValidator extends OJValidator{
	public function validate($args){
		$valueToTest = $args[$this->getDisplayFieldName()];
		if(!OJUtil::checkNotBlank($valueToTest) || preg_match('/^\+?-?\d+\.+\d*$/',$valueToTest)) {
			return true;
		}
		$this->error = array(
			'message' => t('Field "%s" is not a vaild Decimal number.',$this->label),
			'name' => $this->fieldName
		);
		return false;
	}

}
?>
