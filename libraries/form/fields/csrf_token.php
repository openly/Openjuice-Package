<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* HiddenField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Raghu
*/
class CsrfTokenField extends OJField
{
    protected $hidden = true;

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
        $form = Loader::helper('form');
        $token = Loader::helper('validation/token');

        $this->default = $token->generate();
        $this->field = $form->hidden(
            $this->getDisplayFieldName(),
            $this->default
        );
    }
}