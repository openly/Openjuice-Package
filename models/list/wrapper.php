<?php
/*
 * Created on Jan 23, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */
abstract class Wrapper{
	protected $list;
	protected $rec;
	
	public function __construct($list,$rec){
		$this->list = $list;
		$this->rec = $rec;
	}
	
	public abstract function wrap($val);
}


class EditWrapper extends Wrapper{
	public function wrap($val){
		return t('<a href="%s%s/">%s</a>',$this->list->getEditURL(),$this->rec[$this->list->getIDField()],$val);
	}
}

class DeleteWrapper extends Wrapper{
	public function wrap($val){
		return t('<a href="%s/%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">%s</a>',$this->list->getDeleteUrl(),$this->rec[$this->list->getIDField()],$val);
	}
}
