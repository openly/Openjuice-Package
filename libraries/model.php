<?php

Loader::library('templating/renderable', 'openjuice');
Loader::library('form/wizard', 'openjuice');
Loader::library('form/form', 'openjuice');

/**
* OJModel
*
* @uses     Renderable
*
* @category Category
* @package  Package
* @author    <>
*/
class OJModel extends Renderable
{
    // Configuration variables
    protected $meta = array( 'adapter' => 'mysql' );
    protected $identifier = 'id';
    protected $fields = array();

    protected $relations = array();

    protected $searchPatterns = array();

    protected $listTemplates = array();
    protected $templates = array();
    
    /* Functional variables */
    protected $adapter = null;

    protected $steps = array();
    protected $useWizzard = false;
    protected $form = null;
    protected $defaultFields = null;
    protected $formMultiFieldValidations =null;
    protected $formRules =null;

    protected $status = null;

    /**
     * __construct
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($id=null)
    {
        $this->init();
        if($id != null)
            $this->load($id);
    }

    /**
     * getList
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getList()
    {
        return $this->getDataAdapterObj()->getList();
    }

    /**
     * getDataAdapterObj
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getDataAdapterObj()
    {
        if (!$this->adapter) {
            Loader::library(
                'data_adapter/' . $this->meta['adapter'],
                'openjuice'
            );
            $className = ucfirst($this->meta['adapter']) . 'DataAdapter';
            $this->adapter = new $className(
                $this,
                $this->meta,
                $this->fields,
                $this->relations
            );
        }
        return $this->adapter;
    }

    /**
     * search
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function search()
    {
        $args = func_get_args();
        if (is_string($args[0])) {
            return $this->searchFromPattern($args[0], $args[1]);
        } else if (is_array($args[0])) {
            return $this->filter($args[0], $args[1]);
        }
    }

    /**
     * searchFromPattern
     * 
     * @param mixed $pattern Description.
     * @param mixed $args    Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function searchFromPattern($pattern,$args)
    {
        if (in_array($pattern, array_keys($this->searchPatterns))) {
            $pattern = $this->searchPatterns[$pattern];
            $list = $this->getList();
            $list->addPattern($pattern, $args);
            return $list;
        }
    }

    /**
     * __call
     * 
     * @param mixed $fn   Description.
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __call($fn,$args)
    {
        if (preg_match('/^search/', $fn)) {
            $arg = lcfirst(preg_replace('/^search/', '', $fn));
            return $this->search($arg, $args[0]);
        }
    }

    /**
     * getListTemplate
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getListTemplate($str = null)
    {
        if ($str == null) {
            return $this->listTemplates[0];
        }
    }

    /**
     * getForm
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getForm()
    {
        if ($this->form == null) {
            if ($this->useWizzard) {
                $this->form = $this->_getWizard();
            } else {
                $this->form = new OJForm(
                    $this->fields,
                    $this->formMultiFieldValidations,
                    $this->formRules,
                    $this->fieldGroups
                );
            }
        }
        return $this->form;
    }

    /**
     * getWizard
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _getWizard()
    {
        if (count($this->steps) > 0) {
            $wiz = OJWizard::getWizard(
                $this->fields,
                $this->steps,
                $this->formMultiFieldValidations,
                $this->formRules,
                $this->fieldGroups
            );
        }
        return $wiz;
    }

    /**
     * getValues
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getValues()
    {
        $this->formValues = $this->useWizzard ?
        $this->form->getValues() : $this->form->getFieldValues();
        return $this->formValues;
    }

    /**
     * save
     * 
     * @param mixed &$args Description.
     * @param mixed $id    Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function save(&$args,$id = null)
    {
        $id = ($id != null && $id > 0) ?
        $id : $this->formValues[$this->identifier];
        if ($id > 0) {
            $this->getDataAdapterObj()->update($id, $args);
            $this->status = "Updated";
        } else {
            $this->getDataAdapterObj()->create($args);
            $this->status = "Added";
        }
    }

    /**
     * process
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function process()
    {
        if ($this->useWizzard) {
            $wiz = $this->getForm();
            $wiz->process();
            if ($wiz->isComplete()) {
                $args = $this->getValues();
                $this->on_before_save($args);
                $this->save($args);
                $this->on_after_save($args);
            }
        } else {
            $form = $this->getForm();
            $args = $this->getValues();
            if ($form->validate($args)) {
                $this->on_before_save($args);

                if ($this->_isNewRecord()) {
                    $this->getDataAdapterObj()->create($args);
                    $this->status = "Added";                    
                } else {
                    $this->getDataAdapterObj()->update(
                        $args[$this->identifier],
                        $args
                    );
                    $this->status = "Updated";
                }

                $this->on_after_save($args);
            }
        }
    }

    /**
     * isNewRecord
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _isNewRecord()
    {
        $value = $this->getValues();
        return !(isset($value[$this->identifier])
            && !empty($value[$this->identifier]));
    }

    /**
     * getIdentifierField
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getIdentifierField()
    {
        return $this->identifier;
    }

    /**
     * getStatus
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * getDefaultFields
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getDefaultFields()
    {
        return $this->defaultFields;
    }

    /**
     * getFields
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * getDistinctRows
     * 
     * @param mixed $filter Description.
     * @param mixed $args   Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getDistinctRows($filter = null,$args = null)
    {
        if ($this->defaultFields == null) {
            foreach ($this->fields as $key=>$field) {
                if (isset($field['db_col'])) {
                    $this->defaultFields[] = $key;
                    break;
                }
            }
        }
        $rows = $this->getDataAdapterObj()->getDistinctRows($filter, $args);
        $rowStrs = array();
        foreach ($rows as $row) {
            $id = $row[$this->identifier];
            $rowStrs[$id] = $this->getRowString($row);
        }
        return $rowStrs;
    }

    /**
     * getDistinctRowsFor
     * 
     * @param mixed $col    Description.
     * @param mixed $filter Description.
     * @param mixed $args   Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getDistinctRowsFor($col,$filter=null,$args=null)
    {
        return $this->getDataAdapterObj()->getDistinctRowsFor(
            $col, $filter, $args
        );
    }

    /**
     * getRowString
     * 
     * @param mixed &$row Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getRowString(&$row)
    {
        $str = "";
        foreach ($row as $key=>$col) { 
            if ($key == $this->identifier) {
                continue;
            }
            $str .= $col . '-';
        }
        return substr($str, 0, -1);
    }

    /**
     * isWizard
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function isWizard()
    {
        return $this->useWizzard;
    }

    /**
     * load
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function load($id)
    {
        if (!$this->useWizzard) {
            $this->addIDField($id);
        }

        $form = $this->getForm();

        //To dp:if it is new form, then only load
        if (!$this->useWizzard || $form->isNewWizard()) {
            $this->on_before_load();
            $values = $this->getDataAdapterObj()->load($id);
            $this->on_after_load($values);
            $form->setFieldValues($values);
        }
    }

    /**
     * addIDField
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addIDField($id)
    {
        $field['name'] = $this->identifier;
        $field['db_col'] = $this->identifier;
        $field['default'] = $id;
        $field['type'] = 'hidden';
        $this->fields[$this->identifier] = $field;
    }

    /**
     * getMeta
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * usesWizzard
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function usesWizzard()
    {
        return $this->useWizzard;
    }

    /**
     * delete
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function delete($id)
    {
        if ($id != null) {
            $this->getDataAdapterObj()->delete($id);
        }
    }


    /**
     * on_before_load
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function on_before_load()
    {

    }

    /**
     * on_after_load
     * 
     * @param mixed &$args Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function on_after_load(&$args = null)
    {

    }

    /**
     * on_before_save
     * 
     * @param mixed &$args Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function on_before_save(&$args = null)
    {

    }

    /**
     * on_after_save
     * 
     * @param mixed &$args Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function on_after_save(&$args = null)
    {
        
    }   

    /**
     * init
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function init()
    {

    }       
}
