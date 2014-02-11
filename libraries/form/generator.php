<?php

defined('C5_EXECUTE') or die("Access Denied.");

/**
* OJGenerator
*
* @uses     
*
* @category Category
* @package  Package
* @author    <>
*/
class OJGenerator
{
    public static $params = array(
        'name','dbcol','type','label','validations',
        'step','model','values','default','custom_error_message',
        'template','fieldAttrs'
    );
    

    /**
     * getField
     * 
     * @param mixed &$args    Description.
     * @param mixed $prefix   Description.
     * @param mixed $template Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getField(&$args,$prefix,$template)
    {
        return self::_getFieldFromHash(
            self::getHash($args),
            $prefix,
            $template
        );
    }

    /**
     * getValidators
     * 
     * @param mixed &$args Description.
     * @param mixed $value Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getValidators(&$args,$value)
    {
        return self::_getValidatorFromHash(self::getHash($args), $value);
    }

    /**
     * _getFieldFromHash
     * 
     * @param mixed &$hash    Description.
     * @param mixed $prefix   Description.
     * @param mixed $template Description.
     *
     * @access private
     * @static
     *
     * @return mixed Value.
     */
    private static function _getFieldFromHash(&$hash,$prefix,$template)
    {
        $fieldClass = ucfirst($hash['type']) . 'Field';
        $field = new $fieldClass();
        $field->setPrefix($prefix);
        $field->setTemplate($template);
        $field->setVars($hash);
        return $field;
    }

    /**
     * _getValidatorFromHash
     * 
     * @param mixed &$hash  Description.
     * @param mixed $prefix Description.
     *
     * @access private
     * @static
     *
     * @return mixed Value.
     */
    private static function _getValidatorFromHash(&$hash,$prefix)
    {
        $validators = array();
        foreach (split(' ', $hash['validations']) as $validation) {
            if (strlen($validation) < 1) {
                continue;
            }
            $validatorClass = ucfirst($validation) . 'Validator';
            $validator = new $validatorClass();
            $validator->setPrefix($prefix);
            $validator->setFieldName($hash['name']);
            $validator->setLabel($hash['label']);
            $validator->setErrorMessage($hash['custom_error_message']);
            $validators[] = $validator;
        }
        return $validators;
    }
    

    /**
     * getHash
     * 
     * @param mixed &$args Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getHash(&$args)
    {
        $hash = $args;
        if (is_array($args)) {
            if (!in_array('name', array_keys($args))) {
                $hash = self::_getHashFromArray($args);
            }
        } elseif (is_string($args)) {
            $hash = self::_getHashFromString($args);
        }
        return $hash;
    }

    /**
     * _getHashFromArray
     * 
     * @param mixed &$array Description.
     *
     * @access private
     * @static
     *
     * @return mixed Value.
     */
    private static function _getHashFromArray(&$array)
    {
        $hash = array();
        foreach (self::$params as $param) {
            $hash[$param] = array_shift($array);
        }
        return $hash;
    }

    /**
     * _getHashFromString
     * 
     * @param mixed $str Description.
     *
     * @access private
     * @static
     *
     * @return mixed Value.
     */
    private static function _getHashFromString($str)
    {
        return self::_getHashFromArray(split(',', $str));
    }
}
