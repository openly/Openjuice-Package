<?php
defined('C5_EXECUTE') or die("Access Denied.");

class TextAreaField extends OJField
{
    public function initialize()
    {
        parent::initialize();
        $form = Loader::helper('form');
        $this->fieldAttrs = array('rows'=>"10","cols"=>"50","style"=>"width:auto;");
        $this->field = $form->textarea($this->getDisplayFieldName(), $this->default, $this->fieldAttrs);
    }
}
