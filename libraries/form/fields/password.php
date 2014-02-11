<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* PasswordField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author    <>
*/
class PasswordField extends OJField
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
        parent::initialize();
        $form = Loader::helper('form');
        $this->field = $form->password(
            $this->getDisplayFieldName(),
            $this->default,
            $this->fieldAttrs
        );
    }
}