<?php
defined('C5_EXECUTE') or die("Access Denied.");

class CurrencyValidator extends OJValidator{
	public function validate($args){
		$valueToTest = $args[$this->getDisplayFieldName()];
		if(!OJUtil::checkNotBlank($valueToTest) || preg_match('/^\$?.?.?.?[1-9]\d*(?:\.?\d{0,2})?$/',$valueToTest)) {
			return true;
		}
		$this->error = array(
			'message' => t('Field "%s" is not a vaild currency.',$this->label),
			'name' => $this->fieldName
		);
		return false;
	}

}
?>
