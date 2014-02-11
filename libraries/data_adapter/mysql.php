<?php

Loader::library('data_adapter/data_adapter', 'openjuice');
Loader::library('data_adapter/data_list', 'openjuice');
Loader::library('data_adapter/mysql_list', 'openjuice');
Loader::library('data_adapter/mysql_qb', 'openjuice');

/**
* MysqlDataAdapter
*
* @uses     DataAdapter
*
* @category Category
* @package  Package
* @author   Abhi
*/
class MysqlDataAdapter extends DataAdapter
{

    protected $model;

    protected $table;

    protected $clause         = '';
    protected $sort           = '';
    protected $clauseArgs     = array();
    protected $sortArgs       = array();
    protected $requiredFields = array();
    private $_dbColNames = null;
    private $_fieldValues = null;
    protected $db = null;
    

    /**
     * generateList
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function generateList()
    {
        return new MysqlList(
            $this->model,
            $this->meta,
            $this->fields,
            $this->relations
        );
    }

    /**
     * create
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function create($args)
    {
        $this->_getDB();
        $valueQues = str_repeat('?,', count($this->_getDBFieldNames($args)) - 1) . '?';
        $query = t(
            'INSERT INTO `%s` (`%s`,%s) VALUES (null,%s)',
            $this->meta['table'],
            $this->model->getIdentifierField(),
            implode(', ', $this->_dbColNames),
            $valueQues
        );

        $retValues = $this->db->Execute($query, $this->_fieldValues);
        $this->_resetToC5DB();
        return $retValues;
    }

    /**
     * update
     * 
     * @param mixed $id   Description.
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function update($id,$args)
    {
        $this->_getDB();
        $this->_getDBFieldNames($args);
        $idField = $this->model->getIdentifierField();
        unset($this->_dbColNames[$idField]);
        $valueQues = implode('=?, ', $this->_dbColNames) . '=?';

        $query = t(
            'UPDATE `%s` SET %s WHERE `%s`=?',
            $this->meta['table'],
            $valueQues,
            $idField
        );
        $this->_fieldValues[] = $id;
        $retValues = $this->db->Execute($query, $this->_fieldValues);
        $this->_resetToC5DB();
        return $retValues;
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
        $qb = $this->initQB();
        $idField = $this->model->getIdentifierField();

        foreach ($this->fields as $key=>$field) {
            if (isset($field['db_col'])) {
                $qbField[$key]['db_col'] = $field['db_col'];
            }
        }

        $qbField[$idField]['db_col'] = $idField;

        $qb->fields = $qbField;
        
        $qb->filters = array($idField=>$id);
        $query = $qb->getQuery();
        $values = $qb->getArgs();
        $retValues = $this->_getDB()->getRow($query, $values);
        $this->_resetToC5DB();
        return $retValues;
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
        $this->_getDB();
        $idField = $this->model->getIdentifierField();
        $query = t(
            'DELETE FROM `%s` WHERE `%s`=?',
            $this->meta['table'],
            $idField
        );
        $retValues = $this->db->Execute($query, array($id));
        $this->_resetToC5DB();
        return $retValues;
    }

    /**
     * initQB
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function initQB()
    {
        $qb = new MysqlQueryBuilder();
        $qb->tables = $this->meta['table'];
        return $qb;
    }

    /**
     * getQBFilterStr
     * 
     * @param mixed $filter Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getQBFilterStr($filter)
    {
        if ($filter != null) {
            preg_match_all("/`(.*?)`/", $filter, $dbCols);
            foreach ($dbCols[1] as $dbCol) {
                $filter = str_replace(
                    "`$dbCol`",
                    "`{$this->fields[$dbCol]['db_col']}`",
                    $filter
                );
                $param[$dbCol] = $args[$dbCol];
            }
            return $filter;
        }
        return null;
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
        if($args == null) $args = $_POST;

        $qb = $this->initQB();
        $field = array(
            'db_col' => $this->fields[$col]['db_col'],
            'distinct' => true
        );

        $qb->fields = array($col => $field);
        $qb->filters = $this->getQBFilterStr($filter);
        $qb->addArgs($args);
        $query = $qb->getQuery();
        $values = $qb->getArgs();

        if ($qb->hasNull()) {
            return array();
        }
        $rows = array();
        foreach ($this->_getDB()->getAll($query, $values) as $row) {
            $rows[] = $row[$col];
        }
        $this->_resetToC5DB();
        return $rows;
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
        $defaultFields = $this->model->getDefaultFields();
        if ($defaultFields == null) {
            foreach ($this->fields as $key=>$field) {
                if (isset($field['db_col'])) {
                    $defaultFields[] = $key;
                    break;
                }
            }
        }

        if ($args == null) {
            $args = $_POST;
        }
        $this->_getDB();
        $qb = $this->initQB();

        $selectField = $this->model->getIdentifierField();
        $field[$selectField]['db_col'] = $selectField;
        foreach ($this->model->getDefaultFields() as $defField) {
            if (isset($this->fields[$defField])) {
                $field[$defField]['db_col'] = $this->fields[$defField]['db_col'];
            }
        }
        $qb->fields = $field;
        $qb->filters = $this->getQBFilterStr($filter);
        
        $qb->addArgs($args);
        $query = $qb->getQuery();
        $values = $qb->getArgs();
        if ($qb->hasNull()) {
            return array();
        }
        $values = $this->_getDB()->getAll($query, $values);
        $this->_resetToC5DB();
        return $values;
    }

    /**
     * getDB
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _getDB()
    {
        if (!empty($this->meta)) {
            $this->db = Loader::db(
                $this->meta['server'],
                $this->meta['userName'],
                $this->meta['password'],
                $this->meta['database'],
                $this->meta['autoConnect']
            );
        } else {
            $this->db = Loader::db(null, null, null, null, true);
        }
            
        return $this->db;
    }

    /**
     * resetToC5DB
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _resetToC5DB()
    {
        if (!empty($this->meta)) {
            $this->db = Loader::db(null, null, null, null, true);
        }
    }

    /**
     * getDBFieldNames
     * 
     * @param mixed $values Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _getDBFieldNames($values = null)
    {
        if ($values == null) {
            $values = $this->model->getValues();
        }
        foreach ($this->fields as $key=>$field) {
            if (isset($field["db_col"])) {
                $this->_dbColNames[] ='`' . $field["db_col"] . '`';
                $this->_fieldValues[] = $values[$key];
            }
        }
        return $this->_dbColNames;
    }

    /**
     * find
     * 
     * @param mixed $filter Description.
     * @param mixed $args   Description.
     * @param mixed $sort   Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function find($filter,$args = null,$sort = null)
    {
        $st = microtime(true);
        $qb = $this->initQB();
        $idField = $this->model->getIdentifierField();

        foreach ($this->fields as $key=>$field) {
            if (isset($field['db_col'])) {
                $qbField[$key]['db_col'] = $field['db_col'];
            }
        }

        $qbField[$idField]['db_col'] = $idField;

        $qb->fields = $qbField;
        $qb->addArgs($args);
        $qb->filters = $this->getQBFilterStr($filter);
        $qb->sorts = $sort;
        $query = $qb->getQuery();
        $values = $qb->getArgs();
        $retValues = $this->_getDB()->getAll($query, $values);
        $this->_resetToC5DB();
        return $retValues;
    }
}
