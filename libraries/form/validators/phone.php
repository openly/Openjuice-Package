<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* PhoneValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Abhi
*/
class PhoneValidator extends OJValidator
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
            || preg_match('/^[0-9\-\+\. \(\)]+$/', $valueToTest)
        ) {
            return true;
        }
        $this->error = array(
            'message' => t('Field "%s" is not a vaild phone number.', $this->label),
            'name' => $this->fieldName
        );
        return false;
    }

}