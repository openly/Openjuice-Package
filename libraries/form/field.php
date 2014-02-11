<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('templating/renderable', 'openjuice');

/**
* OJField
*
* @uses     Renderable
*
* @category Category
* @package  Package
* @author    <>
*/
class OJField extends Renderable
{
    protected $fieldTypeName = '';

    protected $fieldsToRender =  array(
        'parentAttrs','labelParentAttrs','fieldParentAttrs',
        'fieldName','fieldPrefix','label','field'
    );

    protected $template = '';

    protected $parentAttrs = '';
    protected $labelParentAttrs = '';
    protected $fieldParentAttrs = '';
    protected $label = '';
    protected $field = '';

    protected $fieldPrefix = '';
    protected $fieldName = '';

    protected $default = '';

    protected $validations = array();

    protected $custom_error_message = '';

    protected $fieldAttrs = array();

    protected $extra = array();

    /**
     * __set
     * 
     * @param mixed $name Description.
     * @param mixed $val  Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __set($name,$val)
    {
        if($val == null)return;
        $this->extra[$name] = $val;
    }

    /**
     * __get
     * 
     * @param mixed $name Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __get($name)
    {
        return $this->extra[$name];
    }

    /**
     * __construct
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct()
    {
        $this->fieldTypeName = str_replace('Field', '', get_class($this));
    }

    /**
     * setPrefix
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setPrefix($str)
    {
        $this->fieldPrefix = $str;
    }

    /**
     * setTemplate
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setTemplate($str)
    {
        $this->template = $str;
    }

    /**
     * setVars
     * 
     * @param mixed &$hash Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setVars(&$hash)
    {
        if (OJUtil::checkNotBlank($hash['name'])) {
            $this->fieldName = $hash['name'];
        } else {
            throw new Exception("Field name cannot be blank", 1);
        }
        $this->label = $hash['name'];
        foreach ($hash as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * initialize
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function initialize()
    {
        if (!is_array($this->validations)) {
            //$this->validations = array();
            $this->validations = explode(' ', $this->validations);
        }
        if (is_string($this->fieldAttrs)) {
            $attrs = array();
            foreach (split(' ', $this->fieldAttrs) as $attr) {
                list($key,$val) = split('=', $attr);
                $attrs[$key] = $val;
            }
            $this->fieldAttrs = $attrs;
        }
        if (!is_array($this->fieldAttrs)) {
            $this->fieldAttrs = array();
        }
        $fieldAttrs['class'] .= ' ' . implode(' ', $this->validations);
        if (in_array('required', $this->validations)) {
            $this->label .= ' *';
        }
    }

    /**
     * getDisplayFieldName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getDisplayFieldName()
    {
        return $this->fieldPrefix . $this->fieldName;
    }

    /**
     * setDisplayFieldName
     * 
     * @param mixed $name Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setDisplayFieldName($name)
    {
        $this->fieldName = $name;
    }

    /**
     * getMarkup
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getMarkup()
    {
        $this->initialize();
        return parent::getMarkup();
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
            return array($field['name'] => $args[$key]);
        } else {
            return array($field['name'] => $field['default']);
        }
    }
}
