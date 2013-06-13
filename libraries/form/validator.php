<?php
defined('C5_EXECUTE') or die("Access Denied.");

abstract class OJValidator{
	protected $fieldName = '';
	protected $fieldPrefix = '';
	protected $label = '';
	protected $custom_error_message = '';
	protected $error = '';

	public function setPrefix($str){$this->fieldPrefix = $str;}
	public function setFieldName($str){$this->fieldName = $str;}
	public function setLabel($str){$this->label = $str;}
	public function setErrorMessage($str){
		if($str != '')
		{
			$this->custom_error_message = array(
				'message' => t($str),
				'name' => $this->fieldName
			);
		}
	}

	public function getError(){
		return empty($this->custom_error_message) ? $this->error : $this->custom_error_message;
	}

	protected function getDisplayFieldName(){
		return $this->fieldPrefix . $this->fieldName;
	}

	abstract public function validate($args);
}