<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* TextAreaField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class TextAreaField extends OJField
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
        $this->field = $form->textarea(
            $this->getDisplayFieldName(),
            $this->default,
            $this->fieldAttrs
        );
    }
}
