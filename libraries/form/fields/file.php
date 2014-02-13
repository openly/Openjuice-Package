<?php

defined('C5_EXECUTE') or die("Access Denied.");

/**
* FileField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class FileField extends OJField
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
        $ast = Loader::helper('concrete/asset_library');
        $this->field = $ast->file(
            $this->getDisplayFieldName(),
            $this->getDisplayFieldName(),
            $this->label,
            $this->default ? File::getByID($this->default) : null
        );
    }
}