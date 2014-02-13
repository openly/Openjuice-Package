<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* DecimalValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Abhi
*/
class DecimalValidator extends OJValidator
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
            || preg_match('/^\+?-?\d+\.+\d*$/', $valueToTest)
        ) {
            return true;
        }
        $this->error = array(
            'message' => t(
                'Field "%s" is not a vaild Decimal number.',
                $this->label
            ),
            'name' => $this->fieldName
        );
        return false;
    }

}