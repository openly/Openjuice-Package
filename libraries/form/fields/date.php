<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* DateField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class DateField extends OJField
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
        $dtt = Loader::helper('form/date_time');
        $this->field = $dtt->date(
            $this->getDisplayFieldName(),
            $this->default,
            true,
            $this->fieldAttrs
        );
    }
}