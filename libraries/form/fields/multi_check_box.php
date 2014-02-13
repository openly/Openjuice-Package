<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* MultiCheckBoxField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class MultiCheckBoxField extends OJField
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
        $this->field = '';
        $curValues = split(',', $this->default);
        foreach ($this->values as $key => $val) {
            $this->field .= '<div>';
            $this->field .= $form->checkbox(
                $this->getDisplayFieldName().'[]',
                $key,
                in_array($key, $curValues),
                array("id" => $this->getDisplayFieldName())
            );
            $this->field .= '<span for="Added">&nbsp;&nbsp;' . $val . '</span>';
            $this->field .= '</div>';
        }
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
        if(!is_array($args[$key])) {
            return array($key => $args[$key]);
        }
        return array($key => implode(',', $args[$key]));
    }
}
