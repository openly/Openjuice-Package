<?php

defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('form/field', 'openjuice');
Loader::library('form/validator', 'openjuice');
Loader::library('util', 'openjuice');
Loader::library('form/generator', 'openjuice');
Loader::library('form/expression', 'openjuice');

/**
* OJForm
*
* @uses     
*
* @category Category
* @package  Package
* @author    <>
*/
class OJForm
{

    protected $fields = array();
    protected $formMultiFieldValidations = null;
    protected $formRules = null;
    protected $fieldGroups = array();
    protected $values = null;

    protected $fieldPrefix = '';
    protected $fieldTemplate = <<<EOF
        <tr {{parentAttrs}} >
            <th {{labelParentAttrs}}><label for="{{fieldPrefix}}{{fieldName}}">{{label}}</label></th>
            <td {{fieldParentAttrs}}>{{{field}}}</td>
        </tr>
EOF
    ;

    protected $errors = array();
    private $_expr = null;
    protected $template = "{{{formFields}}";
    protected $submitBtnName = "Save";

    /**
     * __construct
     * 
     * @param mixed &$fields                    Description.
     * @param mixed &$formMultiFieldValidations Description.
     * @param mixed &$formRules                 Description.
     * @param mixed &$fieldGroups               Description.
     * @param mixed &$args                      Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct(&$fields,
        &$formMultiFieldValidations = null,
        &$formRules = null,
        &$fieldGroups = null,
        &$args = null
    ) {
        $this->fields = $fields;
        $this->values = $args;
        $this->formMultiFieldValidations = $formMultiFieldValidations;
        $this->formRules = $formRules;
        $this->fieldGroups = $fieldGroups;
        foreach ($this->fields as $key=>$field) {
            $this->fields[$key]['name'] = $key;
            if (isset($field["show_in_form"]) && !$field["show_in_form"]) {
                unset($this->fields[$key]);
            }
        }

        $this->_expr = new Expression(array_keys($this->fields));
    }

    /**
     * setFieldPrefix
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldPrefix($str)
    {
        $this->fieldPrefix = $str;
    }

    /**
     * getAvailableFieldTypes - Functions to support the population
     * of UI for generator
     * 
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getAvailableFieldTypes()
    {
        $fileHelper = Loader::helper('file');
        $fields = array();
        foreach (OJUtil::getIncludeDirs('fields') as $dir) {
            foreach ($fileHelper->getDirectoryContents($dir) as $file) {
                $value = OJUtil::getCleanName($file);
                $key = OJUtil::getCleanVarName($file);
                $fields[$key] = $value;
            }
        }
        return $fields;
    }

    /**
     * getAvailableValidatorTypes
     * 
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getAvailableValidatorTypes()
    {
        $fileHelper = Loader::helper('file');
        $validators = array();
        foreach (OJUtil::getIncludeDirs('validators') as $dir) {
            foreach ($fileHelper->getDirectoryContents($dir) as $file) {
                $value = OJUtil::getCleanName($file);
                $key = OJUtil::getCleanVarName($file);
                $validators[$key] = $value;
            }
        }
        return $validators;
    }

    /**
     * _includeFields
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _includeFields()
    {
        $fileHelper = Loader::helper('file');
        foreach (OJUtil::getIncludeDirs('fields') as $dir) {
            foreach ($fileHelper->getDirectoryContents($dir) as $file) {
                if (preg_match('/\.php$/', $file)) {
                    include_once $dir . '/' . $file;
                }
            }
        }
    }

    /**
     * _includeValidators
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _includeValidators()
    {
        $fileHelper = Loader::helper('file');
        foreach (OJUtil::getIncludeDirs('validators') as $dir) {
            foreach ($fileHelper->getDirectoryContents($dir) as $file) {
                if (preg_match('/\.php$/', $file)) {
                    include_once $dir . '/' . $file;
                }
            }
        }
    }

    /**
     * getMarkup
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getMarkup($args=null)
    {
        if ($args == null) {
            $args = $this->getFieldValues();
        }
        $m = new Mustache;
        $this->_includeFields();
        $renderedFieldGroups = array();
        $this->setFieldValues($args);
        $retstr = '';
        $formFields .='';
        foreach ($this->fields as $field) {
            if (isset($field['field_group'])) {
                if (!in_array($field['field_group'], $renderedFieldGroups)) {
                    $formFields .= $this->getFieldGroupMarkup($field['field_group']);
                    $renderedFieldGroups[] = $field['field_group'];
                }
                continue;
            }
            $formFields .= $this->getFieldMarkup($field, $args);
        }

        $data = array(
            "submitBtnName" => $this->submitBtnName,
            "formFields"=>$formFields
        );

        $retstr .= $m->render($this->template, $data);
        return $retstr;
    }

    /**
     * getFieldMarkup
     * 
     * @param mixed $field Description.
     * @param mixed $args  Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getFieldMarkup($field,$args)
    {
        if ($args == null) {
            $args = $this->getFieldValues();
        }
        $vals = is_array($this->values) ?
        array_merge($this->values, $args) : $args;
        $this->_applyFormRules($field, $vals);
        $name = $field['name'];
        if (!empty($field['requiredArgs'])) {
            $requiredFields = explode(',', $field['requiredArgs']);
            $argFields = array();
            foreach ($requiredFields as $key=>$requiredField) {
                if (isset($args[$requiredField])) {
                    $argFields[$requiredField] = $args[$requiredField];
                }
            }
        }
        $field['requiredArgs'] = $argFields;
        return OJGenerator::getField(
            $field,
            $this->fieldPrefix,
            $this->fieldTemplate,
            $this->formRules[$name]
        )->getMarkup();
    }

    /**
     * render
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function render($args=null)
    {
        echo $this->getMarkup($args);
    }

    /**
     * getFieldGroupMarkup
     * 
     * @param mixed $fieldGroup Description.
     * @param mixed $args       Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getFieldGroupMarkup($fieldGroup,$args=null)
    {
        if ($args == null) {
            $args = $this->getFieldValues();
        }
        $grpTemplate = $this->groupTemplate;
        $fieldTemplate = $this->fieldTemplate;
        $grpHead = '';
        if (isset($this->fieldGroups[$fieldGroup])) {
            $grpDesc = $this->fieldGroups[$fieldGroup];

            $grpTemplate = $grpDesc['template'] ? 
            $grpDesc['template'] : $grpTemplate;
            
            $fieldTemplate = $grpDesc['field_template'] ?
            $grpDesc['field_template'] : $fieldTemplate;
            
            $grpHead = $grpDesc['group_name'];
        }

        $renderedFields = array();
        foreach ($this->fields as $field) {
            if (is_array($this->values)) {
                $this->_applyFormRules(
                    $field,
                    array_merge($this->values, $args)
                );
            } else {
                $this->_applyFormRules($field, $args);
            }

            if (!$field['field_group']
                || $field['field_group'] != $fieldGroup
            ) {
                continue;
            }
            $fieldMarkup = OJGenerator::getField(
                $field,
                $this->fieldPrefix,
                $fieldTemplate,
                $this->formRules[$field['name']]
            )->getMarkup();
            $renderedFields[] = array('field' => $fieldMarkup);
        }
        $r = new Renderable($grpTemplate);
        $fGroupParams = array(
            'group_name'=>$grpHead,
            'fields'=>$renderedFields,
            'group_id'=>$fieldGroup
        );
        return $r->getMarkup($fGroupParams);
    }

    /**
     * renderField
     * 
     * @param mixed $fieldkey Description.
     * @param mixed $args     Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function renderField($fieldkey,$args=null)
    {
        $this->_includeFields();
        $field = $this->fields[$fieldkey];
        echo $this->getFieldMarkup($field, $args);
    }

    /**
     * renderFieldGroup
     * 
     * @param mixed $fieldGroup Description.
     * @param mixed $args       Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function renderFieldGroup($fieldGroup,$args=null)
    {
        $this->setFieldValues($args);
        echo $this->getFieldGroupMarkup($fieldGroup);
    }

    /**
     * _applyFormRules
     * 
     * @param mixed &$field Description.
     * @param mixed &$args  Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _applyFormRules(&$field,&$args=null)
    {
        //To do: Now calling this method at two places validation
        // and field generation. Need to make it once
        $name = $field['name'];
        if (isset($this->formRules[$name])) {
            if ($args == null) {
                $args = $_POST;
            }
            foreach ($this->formRules[$name] as $fieldRule) {
                $condition = $this->_expr->parse($fieldRule['condition']);
                if ($this->_expr->validateExpression($condition, $args)) {
                    $this->_performRuleAction($field, $fieldRule);
                }
            }
        }
    }

    /**
     * _performRuleAction
     * 
     * @param mixed &$field     Description.
     * @param mixed &$fieldRule Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _performRuleAction(&$field,&$fieldRule)
    {
        switch($fieldRule['action']){
            case 'hide':
                $field['type'] = 'hidden';
                $field['validations'] = '';
                break;
            case 'search':
                $field['fieldParams'] = $fieldRule['param'];
                break;
            case 'change':
                $field = array_merge($field, $fieldRule['attrs']);
                break;
        }
    }

    /**
     * validate
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function validate(&$args=null)
    {
        if ($args == null) {
            $args = $_POST;
        }
        $this->_includeValidators();
        $errors = array();
        foreach ($this->fields as $field) {
            $vals = is_array($this->values)?
            array_merge($this->values, $args):$args;
            
            $this->_applyFormRules($field, $vals);
            $validators = OJGenerator::getValidators(
                $field,
                $this->fieldPrefix
            );
            foreach ($validators as $validator) {
                if (! $validator->validate($args)) {
                    $errors[] = $validator->getError();
                }
            }
        }
        if (!empty($this->formMultiFieldValidations)) {
            $condition = $this->_expr->parse(
                $this->formMultiFieldValidations['condition']
            );

            $expResult = $this->_expr->validateExpression(
                $condition,
                $args
            );
            if (!$expResult) {
                $errors[]['message'] = $this->formMultiFieldValidations['message'];
            }
        }
        $this->errors = $errors;
        return count($errors)<1;
    }

    /**
     * getErrors
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getErrors()
    {
        return $this->errors; 
    }

    /**
     * getFieldValues
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getFieldValues(&$args=null)
    {
        if (!$args) {
            $args = $_POST;
        }
        $this->_includeFields();
        $retval = array();
        foreach ($this->fields as $field) {
            $hash = OJGenerator::getHash($field);
            $fieldClass = ucfirst($hash['type']) . 'Field';
            $retval = array_merge(
                $retval,
                $fieldClass::getFieldValue(
                    $args,
                    $hash,
                    $this->fieldPrefix,
                    $hash
                )
            );
        }
        return $retval;
    }

    /**
     * setFieldValues
     * 
     * @param mixed &$values Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldValues(&$values=null)
    {
        $this->_includeFields();
        if (!$values) {
            $values = $_POST;
        }
        $this->_includeFields();
        foreach ($this->fields as &$field) {
            if (isset($values[$field['name']])) {
                $field['default'] = $values[$field['name']];
                // if($_SERVER['REQUEST_METHOD'] == 'POST'
                //  && strtolower($field['type']) == 'checkbox'
                //){
                //  $fname = OJGenerator::getField(
                //     $field,
                //     $this->fieldPrefix,
                //     $this->fieldTemplate,
                //     $this->formRules[$name]
                // )->getDisplayFieldName();
                //  $_POST[$fname] = $values[$field['name']];
                // }
            }
        }
    }

    /**
     * setFieldTemplate
     * 
     * @param mixed $tmpl Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldTemplate($tmpl)
    {
        $this->fieldTemplate = $tmpl;
    }

    /**
     * setFormTemplate
     * 
     * @param mixed $tmpl Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFormTemplate($tmpl)
    {
        $this->template = $tmpl;
    }

    /**
     * hasErrors
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * getSubmitButtonName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getSubmitButtonName()
    {
        return $this->submitBtnName;
    }

    /**
     * getValues
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * getFields
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * setFields
     * 
     * @param mixed $value Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFields($value)
    {
        $this->fields = $value;
    }
}
