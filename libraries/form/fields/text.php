<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* TextField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author    <>
*/
class TextField extends OJField
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
        $this->field = $form->text(
            $this->getDisplayFieldName(),
            $this->default,
            $this->fieldAttrs
        );
    }
}