<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* SelectField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author    <>
*/
class SelectField extends OJField
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
        if (is_string($this->values)) {
            if (class_exists(ucfirst($this->values))) {
                $className = $this->values;
                $provider = new $className();
                $this->values = $provider->getValues();
            } else {
                $values = array();
                foreach (split(' ', $this->values) as $value) {
                    list($key,$val) = split('\|', $value);
                    $values[$key] = $val;
                }
                $this->values = $values;
            }
        } elseif (!is_array($this->values)) {
            $this->values = array('0' => '- Select -');
        }
        $form = Loader::helper('form');
        $this->field = $form->select(
            $this->getDisplayFieldName(),
            $this->values,
            $this->default,
            $this->fieldAttrs
        );
    }
}