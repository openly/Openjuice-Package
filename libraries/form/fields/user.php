<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* UserField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author    <>
*/
class UserField extends OJField
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
        $uh = Loader::helper('form/user_selector');
        $this->field = $uh->selectUser(
            $this->getDisplayFieldName(),
            $this->default
        );
    }
}