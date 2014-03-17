<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* EmailValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Abhi
*/
class EmailValidator extends OJValidator
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
            || filter_var($valueToTest, FILTER_VALIDATE_EMAIL)
        ) {
            return true;
        }
        $this->error = array(
            'message' => t(
                'Field "%s" is not a vaild email address.',
                $this->label
            ),
            'name' => $this->fieldName
        );
        return false;
    }
}