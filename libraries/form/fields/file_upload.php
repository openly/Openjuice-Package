<?php
defined('C5_EXECUTE') or die("Access Denied.");

class FileUploadField extends OJField{
	public function initialize(){
		parent::initialize();
		if(!$this->upload_dir) $this->upload_dir = DIR_REL . "/files/";
		if($this->default){
			$this->field = '<img src="' . $this->upload_dir  . $this->default . '" alt="Image">';
		}
		$this->field .= '<br /><input type="file" name="' . $this->getDisplayFieldName() . '">';
		$this->field .= Loader::helper('form')->hidden($this->getDisplayFieldName() . '-prev',$this->default);
	}

	public static function getFieldValue(&$args,$field,$prefix,&$extra=null){
		$key = $prefix . $field['name'];
		if(!$extra['upload_dir']) $extra['upload_dir'] =  DIR_REL . "/files/";
		$extra['upload_dir'] = $_SERVER['DOCUMENT_ROOT'] . $extra['upload_dir'];
		if($_FILES[$key]['name'] != ''){
			$fileName = date('YmdHis') . $_FILES[$key]['name'];
			move_uploaded_file($_FILES[$key]['tmp_name'], $extra['upload_dir'] . $fileName);
			$retval = array();
			$retval[$field['name']] = $fileName;
			return $retval;
		}
		return parent::getFieldValue($args,$field . '-prev',$prefix,$extra);
	}
}