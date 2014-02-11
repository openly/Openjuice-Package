<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* ModelDropDownField
*
* @uses     OJField
*
* @category Category
* @package  Package
* @author    <>
*/
class ModelDropDownField extends OJField
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
            $relationModel = new $this->model();
            $values = $relationModel->getDistinctRows(
                $this->filter,
                $this->requiredArgs
            );
            if (is_null($values)) {
                $values = array();
            }
            OJUtil::array_unshift_assoc($values, '0', '- Select -');
            $form = Loader::helper('form');
            $this->field = $form->select(
                $this->getDisplayFieldName(),
                $values,
                $this->default,
                $this->fieldAttrs
            );
        } else {
            $this->field = "Model Name not defined properly for the model-drop-down field";
        }
    }
}