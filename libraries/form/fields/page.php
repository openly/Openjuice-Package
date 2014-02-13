<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* PageField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class PageField extends OJField
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
        $pageSelector = Loader::helper('form/page_selector');
        $this->field = '<div id="' . $this->getDisplayFieldName().'">' .
        $this->field .= $pageSelector->selectPage(
            $this->getDisplayFieldName(),
            $this->default
        );
        $this->field .= '</div>'; 
    }
}