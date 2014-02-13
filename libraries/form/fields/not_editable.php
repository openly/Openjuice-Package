<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* NotEditableField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class NotEditableField extends OJField
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
        $form = Loader::helper('form');
        $this->field = $form->hidden(
            $this->getDisplayFieldName(),
            $this->default
        );
        $this->field .= t('<b>%s</b>', $this->default);
    }
}