<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* IntegerValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Abhi
*/
class IntegerValidator extends OJValidator
{
    /**
     * validate
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function validate($args)
    {
        $valueToTest = $args[$this->getDisplayFieldName()];
        if (!OJUtil::checkNotBlank($valueToTest)
            || $this->_isInteger($valueToTest)
        ) {
            return true;
        }
        $this->error = array(
            'message' => t('Field "%s" is not an integer.', $this->label),
            'name' => $this->fieldName
        );
        return false;
    }

    /**
     * isInteger
     * 
     * @param mixed $data Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _isInteger($data)
    {
        if (is_int($data)) {
            return true;
        } else if (is_string($data) === true && is_numeric($data) === true) {
            return (strpos($data, '.') === false);
        }
        return false;
    }
}