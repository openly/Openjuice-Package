<?php 
require_once __DIR__ . '/../form/generator.php';
require_once __DIR__ . '/../form/expression.php';
require_once __DIR__ . '/../util.php';

/**
* Validate
*
* @uses     
*
* @category Category
* @package  Package
* @author   Raghu
*/
class Validate
{
    private $_fields = null;
    private $_formMultiFieldValidations = null;
    private $_fieldPrefix = '';

    private $_expr = null;
    private $_errors = array();

    /**
     * __construct
     * 
     * @param mixed &$fields                    Description.
     * @param mixed &$formMultiFieldValidations Description.
     * @param mixed $fieldPrefix                Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function __construct(&$fields, &$formMultiFieldValidations, $fieldPrefix)
    {
        $this->_fields = $fields;
        $this->_formMultiFieldValidations = $formMultiFieldValidations;
        $this->_fieldPrefix = $fieldPrefix;
        $this->_expr = new Expression(array_keys($fields));
    }

    /**
     * validate
     * 
     * @param mixed &$values Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function validate(&$values)
    {
        $this->_errors = array();
        $this->_includeValidators();
        foreach ($this->_fields as $field) {
            $this->_applyFormRules($field, $values);
            $validators = OJGenerator::getValidators(
                $field,
                $this->_fieldPrefix
            );
            
            foreach ($validators as $validator) {
                if (!$validator->validate($values)) {
                    $this->_errors[] = $validator->getError();
                }
            }
        }
        if (!empty($this->_formMultiFieldValidations)) {
            $condition = $this->_expr->parse(
                $this->formMultiFieldValidations['condition']
            );

            $expResult = $this->_expr->validateExpression(
                $condition,
                $values
            );
            if (!$expResult) {
                $this->_errors[]['message']
                    = $this->formMultiFieldValidations['message'];
            }
        }

        return count($this->_errors) < 1;
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
        return $this->_errors;
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
        //To DO: Remove this Concrete5 Dependency
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
     * _applyFormRules
     * 
     * @param mixed &$field Description.
     * @param mixed &$args  Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _applyFormRules(&$field, &$args)
    {
        //To do: Now calling this method at two places validation
        // and field generation. Need to make it once
        $name = $field['name'];
        if (isset($this->formRules[$name])) {
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
    private function _performRuleAction(&$field, &$fieldRule)
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
}