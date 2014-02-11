<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* RequiredValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author    <>
*/
class RequiredValidator extends OJValidator
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
        if (OJUtil::checkNotBlank($args[$this->getDisplayFieldName()])) {
            return true;
        }
        $this->error = array(
            'message' => t('Field "%s" is required.', $this->label),
            'name' => $this->fieldName
        );
        return false;
    }
}
