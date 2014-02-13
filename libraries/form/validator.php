<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* OJValidator
*
* @uses     
*
* @category Category
* @package  Package
* @author   Abhi
*/
abstract class OJValidator
{
    protected $fieldName = '';
    protected $fieldPrefix = '';
    protected $label = '';
    protected $custom_error_message = '';
    protected $error = '';

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
     * setFieldName
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldName($str)
    {
        $this->fieldName = $str;
    }

    /**
     * setLabel
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setLabel($str)
    {
        $this->label = $str;
    }

    /**
     * setErrorMessage
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setErrorMessage($str)
    {
        if ($str != '') {
            $this->custom_error_message = array(
                'message' => t($str),
                'name' => $this->fieldName
            );
        }
    }

    /**
     * getError
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getError()
    {
        return empty($this->custom_error_message) ?
        $this->error : $this->custom_error_message;
    }

    /**
     * getDisplayFieldName
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getDisplayFieldName()
    {
        return $this->fieldPrefix . $this->fieldName;
    }

    /**
     * validate
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    abstract public function validate($args);
}