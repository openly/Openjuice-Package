<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* DistinctModelColoumnDropdownField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author   Abhi
*/
class DistinctModelColoumnDropdownField extends OJField
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
        if ($this->model != null) {
            $model = new $this->model();
            $rows = $model->getDistinctRowsFor($this->column, $this->filter);
            $values = array();
            foreach ($rows as $row) {
                $values[$row] = $row;
            }
            $this->values = array_merge(array('0' => '- Select -'), $values);
            
            $form = Loader::helper('form');
            $this->field = $form->select(
                $this->getDisplayFieldName(),
                $this->values,
                $this->default,
                $this->fieldAttrs
            );
        } else {
            $this->field = "Model Name not defined properly for the model-drop-down field";
        }
    }
}