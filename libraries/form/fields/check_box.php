<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* CheckBoxField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class CheckBoxField extends OJField
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
        $this->field = $form->checkbox(
            $this->getDisplayFieldName(),
            1,
            $this->default == 1,
            $this->fieldAttrs
        );
    }

    /**
     * getFieldValue
     * 
     * @param mixed &$args  Description.
     * @param mixed $field  Description.
     * @param mixed $prefix Description.
     * @param mixed &$extra Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getFieldValue(&$args,$field,$prefix,&$extra = null)
    {
        $key = $prefix . $field['name'];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (is_null($args[$key])) {
                $args[$key] = 0;
            }
            return array($field['name'] => $args[$key]);
        } else {
            return array($field['name'] => $field['default']);
        }
    }
}