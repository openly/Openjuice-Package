<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* CaptchaField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Raghu
*/
class CaptchaField extends OJField
{

    /**
     * initialize
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function initialize()
    {
        $this->template = "{{{field}}}";
        $captcha = Loader::helper('validation/captcha');

        ob_start();
        $captcha->display();
        $captcha->showInput();
        $this->field = ob_get_contents();
        ob_end_clean();
    }
}