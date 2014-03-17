<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* CaptchaValidator
*
* @uses     OJValidator
*
* @category Category
* @package  Package
* @author   Raghu
*/
class CaptchaValidator extends OJValidator
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
        $this->fieldName = 'ccmCaptchaCode';
        $captcha = Loader::helper('validation/captcha');
        if ($captcha->check($this->fieldName)) {
            return true;
        }
        
        $this->error = array(
            'message' => t('Captcha Validation Failed.'),
            'name' => $this->fieldName
        );
        return false;
    }
}