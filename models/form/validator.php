<?php
/*
 * Created on Jan 22, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */

interface Validator{
	public function test($value);
}

class RequiredValidator implements Validator{
	public function test($value=null){
		return ($value!=null && strlen($value) > 0);
	}
}

class IntegerValidator implements Validator{
	public function test($value=null){
		return ($value == null || is_numeric($value));
	}
}
