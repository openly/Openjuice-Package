<?php

defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('form/field','openjuice');
Loader::library('form/validator','openjuice');
Loader::library('util','openjuice');
Loader::library('form/generator','openjuice');
Loader::library('form/expression','openjuice');

class OJForm{

	protected $fields = array();
	protected $formMultiFieldValidations = NULL;
	protected $formRules = NULL;
	protected $fieldGroups = array();
	protected $values = NULL;

	protected $fieldPrefix = '';
	protected $fieldTemplate = <<<EOF
		<tr {{parentAttrs}} >
			<th {{labelParentAttrs}}><label for="{{fieldPrefix}}{{fieldName}}">{{label}}</label></th>
			<td {{fieldParentAttrs}}>{{{field}}}</td>
		</tr>
EOF
;

	protected $errors = array();
	private $expr = NULL;
/*	protected $template = "	<div class='form'><table>{{{formFields}}</table></div>
							<div class='wizard-footer'>
									<input type='submit' value='{{submitBtnName}}' name='{{submitBtnName}}'>							
							</div>";
*/
	protected $template = "{{{formFields}}";
	protected $submitBtnName = "Save";

	public function __construct(&$fields, &$formMultiFieldValidations = NULL, &$formRules = NULL,&$fieldGroups = null,&$args = null){
		$this->fields = $fields;
		$this->values = $args;
		$this->formMultiFieldValidations = $formMultiFieldValidations;
		$this->formRules = $formRules;
		$this->fieldGroups = $fieldGroups;
		foreach($this->fields as $key=>$field)
		{
			$this->fields[$key]['name'] = $key;
			if(isset($field["show_in_form"]) && !$field["show_in_form"])
				unset($this->fields[$key]);

		}

		$this->expr = new Expression(array_keys($this->fields));
	}

	public function setFieldPrefix($str){$this->fieldPrefix = $str;}
	/*
		Functions to support the population of UI for generator
	*/
	public static function getAvailableFieldTypes(){
		$fileHelper = Loader::helper('file');
		$fields = array();
		foreach (OJUtil::getIncludeDirs('fields') as $dir) {
			foreach($fileHelper->getDirectoryContents($dir) as $file){
				$value = OJUtil::getCleanName($file);
				$key = OJUtil::getCleanVarName($file);
				$fields[$key] = $value;
			}
		}
		return $fields;
	}

	public static function getAvailableValidatorTypes(){
		$fileHelper = Loader::helper('file');
		$validators = array();
		foreach (OJUtil::getIncludeDirs('validators') as $dir) {
			foreach($fileHelper->getDirectoryContents($dir) as $file){
				$value = OJUtil::getCleanName($file);
				$key = OJUtil::getCleanVarName($file);
				$validators[$key] = $value;
			}
		}
		return $validators;
	}

	private function includeFields(){
		$fileHelper = Loader::helper('file');
		foreach (OJUtil::getIncludeDirs('fields') as $dir) {
			foreach($fileHelper->getDirectoryContents($dir) as $file){
				if(preg_match('/\.php$/',$file))
					require_once($dir . '/' . $file);
			}
		}
	}

	private function includeValidators(){
		$fileHelper = Loader::helper('file');
		foreach (OJUtil::getIncludeDirs('validators') as $dir) {
			foreach($fileHelper->getDirectoryContents($dir) as $file){
				if(preg_match('/\.php$/',$file))
					require_once($dir . '/' . $file);
			}
		}
	}

	public function getMarkup($args=NULL){
		if($args == null) $args = $this->getFieldValues();
		$m = new Mustache;
		$this->includeFields();
		$renderedFieldGroups = array();
		$this->setFieldValues($args);
		$retstr = '';
		$formFields .='';
		foreach ($this->fields as $field) {
			if(isset($field['field_group'])){
				if(!in_array($field['field_group'], $renderedFieldGroups)){
					$formFields .= $this->getFieldGroupMarkup($field['field_group']);
					$renderedFieldGroups[] = $field['field_group'];
				}
				continue;
			}
			$formFields .= $this->getFieldMarkup($field,$args);
		}

		$data = array("submitBtnName" => $this->submitBtnName,
						"formFields"=>$formFields
					);

		$retstr .= $m->render($this->template,$data);
		return $retstr;
	}

	public function getFieldMarkup($field,$args){
		if($args == null) $args = $this->getFieldValues();
		$vals = is_array($this->values)? array_merge($this->values,$args):$args;
		$this->applyFormRules($field,$vals);
		$name = $field['name'];
		if(!empty($field['requiredArgs']))
		{
			$requiredFields = explode(',', $field['requiredArgs']);
			$argFields = array();
			foreach($requiredFields as $key=>$requiredField)
			{
				if(isset($args[$requiredField]))
					$argFields[$requiredField] = $args[$requiredField];
			}
		}
		$field['requiredArgs'] = $argFields;
		return OJGenerator::getField(&$field,$this->fieldPrefix,$this->fieldTemplate,$this->formRules[$name])->getMarkup();
	}

	public function render($args=NULL){
		echo $this->getMarkup($args);
	}

	public function getFieldGroupMarkup($fieldGroup,$args=null){
		if($args == null) $args = $this->getFieldValues();
		$grpTemplate = $this->groupTemplate;
		$fieldTemplate = $this->fieldTemplate;
		$grpHead = '';
		if(isset($this->fieldGroups[$fieldGroup])){
			$grpDesc = $this->fieldGroups[$fieldGroup];
			$grpTemplate = $grpDesc['template']?$grpDesc['template']:$grpTemplate;
			$fieldTemplate = $grpDesc['field_template']?$grpDesc['field_template']:$fieldTemplate;
			$grpHead = $grpDesc['group_name'];
			//echo $fieldTemplate;
		}

		$renderedFields = array();
		foreach($this->fields as $field){
			if(is_array($this->values))
				$this->applyFormRules($field,array_merge($this->values,$args));
			else
				$this->applyFormRules($field,$args);
			if(!$field['field_group'] || $field['field_group'] != $fieldGroup)
				continue;
			$fieldMarkup = OJGenerator::getField(&$field,$this->fieldPrefix,$fieldTemplate,$this->formRules[$field['name']])->getMarkup();
			$renderedFields[] = array('field'=>$fieldMarkup);
		}
		$r = new Renderable($grpTemplate);
		$fGroupParams = array('group_name'=>$grpHead,'fields'=>$renderedFields,'group_id'=>$fieldGroup);
		return $r->getMarkup($fGroupParams);
	}

	public function renderField($fieldkey,$args=NULL){
		$this->includeFields();
		$field = $this->fields[$fieldkey];
		echo $this->getFieldMarkup($field,$args);
	}

	public function renderFieldGroup($fieldGroup,$args=null){
		$this->setFieldValues($args);
		echo $this->getFieldGroupMarkup($fieldGroup);
	}

	//To do: Now calling this method at two places validation and field generation. Need to make it once
	private function applyFormRules(&$field,&$args=null){
		$name = $field['name'];
		if(isset($this->formRules[$name])){
			if($args == null) $args = $_POST;
			foreach($this->formRules[$name] as $fieldRule){
				$condition = $this->expr->parse($fieldRule['condition']);
				if($this->expr->validateExpression($condition,&$args)){
					$this->performRuleAction(&$field,&$fieldRule);
				}
			}
		}
	}

	private function performRuleAction(&$field,&$fieldRule){
		switch($fieldRule['action']){
				case 'hide':
					$field['type'] = 'hidden';
					$field['validations'] = '';
					break;
				case 'search':
					$field['fieldParams'] = $fieldRule['param'];
					break;
				case 'change':
					$field = array_merge($field,$fieldRule['attrs']);
					break;
		}
	}

	public function validate(&$args=null){
		if($args == null)
			$args = $_POST;
		$this->includeValidators();
		$errors = array();
		foreach ($this->fields as $field) {
			$vals = is_array($this->values)? array_merge($this->values,$args):$args;
			$this->applyFormRules(&$field,$vals);
			$validators = OJGenerator::getValidators(&$field,$this->fieldPrefix);
			foreach ($validators as $validator) {
				if(! $validator->validate(&$args)) $errors[] = $validator->getError();
			}
		}
		if(!empty($this->formMultiFieldValidations)){
			$condition = $this->expr->parse($this->formMultiFieldValidations['condition']);
			if(!$this->expr->validateExpression($condition,&$args)){
				$errors[]['message'] = $this->formMultiFieldValidations['message'];
			}
		}
		$this->errors = $errors;
		return count($errors)<1;
	}

	public function getErrors(){
		return $this->errors; 
	}

	public function getFieldValues(&$args=null){
		if(!$args) $args = $_POST;
		$this->includeFields();
		$retval = array();
		foreach ($this->fields as $field) {
			$hash = OJGenerator::getHash($field);
			$fieldClass = ucfirst($hash['type']) . 'Field';
			$retval =array_merge($retval, $fieldClass::getFieldValue(&$args,&$hash,$this->fieldPrefix,&$hash));
		}
		return $retval;
	}

	public function setFieldValues(&$values=null){
		$this->includeFields();
		if(!$values) $values = $_POST;
		$this->includeFields();
		foreach ($this->fields as &$field) {
			if(isset($values[$field['name']])){
				$field['default'] = $values[$field['name']];
				// if($_SERVER['REQUEST_METHOD'] == 'POST' && strtolower($field['type']) == 'checkbox'){
				// 	$fname = OJGenerator::getField($field,$this->fieldPrefix,$this->fieldTemplate,$this->formRules[$name])->getDisplayFieldName();
				// 	$_POST[$fname] = $values[$field['name']];
				// }
			}
		}
	}

	public function setFieldTemplate($tmpl){
		$this->fieldTemplate = $tmpl;
	}

	public function setFormTemplate($tmpl){
		$this->template = $tmpl;
	}

	public function hasErrors(){
		return count($this->errors) > 0;
	}

	public function getSubmitButtonName(){return $this->submitBtnName;}

	public function getValues(){
		return $this->values;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setFields($value)
	{
		$this->fields = $value;
	}
}
