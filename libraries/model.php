<?php
require_once __DIR__ . '/helpers/validate.php';

/**
* OJModel
*
* @category Category
* @package  Package
* @author   Abhi
*/
class OJModel
{
    // Configuration variables
    protected $meta = array('adapter' => 'mysql');
    protected $identifier = 'id';
    protected $fields = array();
    protected $fieldPrefix = '';

    protected $relations = array();

    protected $searchPatterns = array();

    protected $listTemplates = array();
    protected $templates = array();
    
    /* Functional variables */
    protected $adapter = null;

    protected $steps = array();
    protected $useWizzard = false;

    protected $defaultFields = null;

    protected $formMultiFieldValidations = null;
    protected $formRules =null;

    //To Do: Use codes and strings
    private $_status = null;

    private $_values = array();
    private $_errors = array();

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
        $this->_addIDField($id);

        $this->init();
        
        if ($id != null) {
            $this->load($id);
        }
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
            include_once __DIR__ . '/data_adapter/' . $this->meta['adapter'] . '.php';
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
     * getValues
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getValues()
    {
        return $this->_values;
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
    public function save(&$args, $id = null)
    {
        //To Do: Catch Exception on DB save
        if ($id) {
            $this->getDataAdapterObj()->update($id, $args);
            $this->_status = "Updated";
        } else {
            $this->getDataAdapterObj()->create($args);
            $this->_status = "Added";
        }
    }

    /**
     * process
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function process(&$args=null)
    {
        if ($this->useWizzard) {
            //process the wizard
            // $wiz->process();
            // if ($wiz->isComplete()) {
            //     $args = $this->getValues();
            //     $this->on_before_save($args);
            //     $this->save($args);
            //     $this->on_after_save($args);
            // }
            return true;
        } else {
            if ($this->validate($args)) {
                $this->on_before_save($args);

                $this->save($args, $args[$this->identifier]);

                $this->on_after_save($args);

                return true;
            }
        }
        return false;
    }

    /**
     * getErrors
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * validate
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function validate(&$args)
    {
        $this->_errors = array();
        
        $validate = new Validate(
            $this->fields,
            $this->formMultiFieldValidations,
            $this->fieldPrefix
        );

        if ($validate->validate($args)) {
            return true;
        } else {
            $this->_status = "Validation Failed";
            $this->_errors = $validate->getErrors();
            return false;
        }
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
        return $this->_status;
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
        $this->on_before_load();
        $this->_values = $this->getDataAdapterObj()->load($id);
        $this->on_after_load($this->_values);
    }

    /**
     * addIDField - adds the hidden ID field to the model's fields variable
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    private function _addIDField($id)
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
        if ($id) {
            $this->getDataAdapterObj()->delete($id);
            return true;
        }

        return false;
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
        //To Do: avoid the private and protected variables
        return $this->$name;
    }

    /**
     * init - function for overriding.
     * This will get called on model constructor
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function init()
    {

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

}
