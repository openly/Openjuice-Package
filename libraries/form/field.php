<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('templating/renderable','openjuice');

class OJField extends Renderable{
	protected $fieldTypeName = '';

	protected $fieldsToRender =  array(
		'parentAttrs','labelParentAttrs','fieldParentAttrs','fieldName','fieldPrefix','label','field'
	);

	protected $template = '';

	protected $parentAttrs = '';
	protected $labelParentAttrs = '';
	protected $fieldParentAttrs = '';
	protected $label = '';
	protected $field = '';

	protected $fieldPrefix = '';
	protected $fieldName = '';

	protected $default = '';

	protected $validations = array();

	protected $custom_error_message = '';

	protected $fieldAttrs = array();

	protected $extra = array();

	public function __set($name,$val){
		if($val == null)return;
		$this->extra[$name] = $val;
	}

	public function __get($name){
		return $this->extra[$name];
	}

	public function __construct(){
		$this->fieldTypeName = str_replace('Field', '', get_class($this));
	}

	public function setPrefix($str){$this->fieldPrefix = $str;}
	public function setTemplate($str){$this->template = $str;}

	public function setVars(&$hash){
		if(OJUtil::checkNotBlank($hash['name'])){
			$this->fieldName = $hash['name'];
		}else{
			throw new Exception("Field name cannot be blank", 1);
		}
		$this->label = $hash['name'];
		foreach ($hash as $key => $val) {
			$this->{$key} = $val;
		}
	}

	public function initialize(){
		if(!is_array($this->validations)){
			$this->validations = explode(' ',$this->validations);//$this->validations = array();
		}
		if(is_string($this->fieldAttrs)){
			$attrs = array();
			foreach (split(' ',$this->fieldAttrs) as $attr) {
				list($key,$val) = split('=',$attr);
				$attrs[$key] = $val;
			}
			$this->fieldAttrs = $attrs;
		}
		if(!is_array($this->fieldAttrs)){
			$this->fieldAttrs = array();
		}
		$fieldAttrs['class'] .= ' ' . implode(' ',$this->validations);
		if(in_array('required', $this->validations)) $this->label .= ' *';
	}

	public function getDisplayFieldName(){return $this->fieldPrefix . $this->fieldName;}
	public function setDisplayFieldName($name){$this->fieldName = $name;}

	public function getMarkup(){
		$this->initialize();
		return parent::getMarkup();
	}

	public static function getFieldValue(&$args,$field,$prefix,&$extra = null){
		$key = $prefix . $field['name'];
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return array($field['name'] => $args[$key]);
		}
		else{
			return array($field['name'] => $field['default']);
		}
	}
}
