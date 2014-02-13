<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* HiddenField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class HiddenField extends OJField
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
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $this->default = $_POST[$this->getDisplayFieldName()];
        }
        if ($this->default &&  !is_string($this->default)) {
            $this->default = serialize($this->default);
        }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $_POST[$this->getDisplayFieldName()] = $this->default;
        }
        $this->field = $form->hidden(
            $this->getDisplayFieldName(),
            $this->default
        );
    }
}