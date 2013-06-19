<?php

defined('C5_EXECUTE') or die("Access Denied.");

class OJGenerator{
	public static $params = array('name','dbcol','type','label','validations','step','model','values','default','custom_error_message','template','fieldAttrs');
	
	public static function getField(&$args,$prefix,$template){
		return self::getFieldFromHash(self::getHash($args),$prefix,$template);
	}

	public static function getValidators(&$args,$value){
		return self::getValidatorFromHash(self::getHash($args),$value);
	}

	private static function getFieldFromHash(&$hash,$prefix,$template){
		//debug_print_backtrace();
		$fieldClass = ucfirst($hash['type']) . 'Field';
		$field = new $fieldClass();
		$field->setPrefix($prefix);
		$field->setTemplate($template);
		$field->setVars($hash);
		return $field;
	}

	private static function getValidatorFromHash(&$hash,$prefix){
		$validators = array();
		foreach (split(' ',$hash[validations]) as $validation) {
			if(strlen($validation) < 1) continue;
			$validatorClass = ucfirst($validation) . 'Validator';
			$validator = new $validatorClass();
			$validator->setPrefix($prefix);
			$validator->setFieldName($hash['name']);
			$validator->setLabel($hash['label']);
			$validator->setErrorMessage($hash['custom_error_message']);
			$validators[] = $validator;
		}
		return $validators;
	}
	
	public static function getHash(&$args){
		$hash = $args;
		if(is_array($args)){
			if(!in_array('name', array_keys($args))){
				$hash = self::getHashFromArray($args);
			}
		}elseif (is_string($args)) {
			$hash = self::getHashFromString($args);
		}
		return $hash;
	}

	private static function getHashFromArray(&$array){
		$hash = array();
		foreach (self::$params as $param) {
			$hash[$param] = array_shift($array);
		}
		return $hash;
	}

	private static function getHashFromString($str){
		return self::getHashFromArray(split(',', $str));
	}
}
