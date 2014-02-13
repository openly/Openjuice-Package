<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* HiddenDisplayField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class HiddenDisplayField extends OJField
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
        $form = Loader::helper('form');
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $this->default = $_POST[$this->getDisplayFieldName()];
        }
        if (!is_string($this->default)) {
            $this->default = serialize($this->default);
        }
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $_POST[$this->getDisplayFieldName()] = $this->default;
        }
        
        $this->field = t('<b>%s</b>', $this->default);
    }

}