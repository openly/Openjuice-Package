<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* CurrencyValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Raghu
*/
class CsrfTokenValidator extends OJValidator
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

        $token = Loader::helper('validation/token');
        if ($token->validate('', $valueToTest)) {
            return true;
        }
        
        $this->error = array(
            'message' => t('CSRF Token Error. Please re-submit again'),
            'name' => $this->fieldName
        );
        return false;
    }
}