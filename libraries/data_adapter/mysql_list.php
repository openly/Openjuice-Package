<?php

/**
* MysqlList
*
* @uses     DataList
*
* @category Category
* @package  Package
* @author   Abhi
*/
class MysqlList extends DataList
{
    
    protected $model;

    protected $table;

    protected $filters        = array();
    protected $sort           = '';
    protected $args           = array();
    protected $sortArgs       = array();
    protected $requiredFields = array();

    protected $relModelObjs = array();

    protected $db;

    /**
     * getDB
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getDB()
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
     * getRelModelObj
     * 
     * @param mixed $name Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getRelModelObj($name)
    {
        if(!in_array($name, array_keys($this->relModelObjs)))
            $this->relModelObjs[$name] = new $this->relations[$name]['model']();
        return $this->relModelObjs[$name];
    }

    /**
     * getReqiredTables
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getReqiredTables()
    {
        $tables = array();
        $modelClsName = get_class($this->model);
        $tables[$modelClsName] = $this->meta['table'];
        foreach ($this->requiredFields as $name=>$value) {
            list($tblName,$colName) = explode('.', $name);
            if (!$colName) {
                continue; // No "." so field from same table
            }
            if (in_array($tblName, array_keys($this->relations))) {
                $relModel = $this->getRelModelObj($tblName);
                $relModelMeta = $relModel->getMeta();
                $tables[$tblName] = $relModelMeta['table'];
                $this->filters[] = $this->relations[$tblName]['key'] . '=' . 
                    $tblName . '.' . $relModel->getIdentifierField();
            } else {
                $tables[$tblName] = $tblName;
            }
        }
        return $tables;
    }

    /**
     * getFilters
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getFilters()
    {
        return $this->filters;
    }

    /**
     * getRequiredFields
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getRequiredFields()
    {
        $newFields = array(
            '__id__' => array(
                'db_col' => $this->model->getIdentifierField()
            )
        );
        foreach ($this->requiredFields as $name => $value) {
            list($tblName,$colName) = explode('.', $name);
            if (!$colName) {
                if (is_array($this->fields[$name])) {
                    $value = array_merge($this->fields[$name], $value);
                }
            } else {
                if (in_array($tblName, array_keys($this->relations))) {
                    $relModel = $this->getRelModelObj($tblName);
                    $relModelFields = $relModel->getFields();
                    if (is_array($relModelFields[$colName])) {
                        $value = array_merge($relModelFields[$colName], $value);
                        $value['db_col'] = $tblName . '.' . $value['db_col'];
                    }
                } else {
                    $tables[$tblName] = $tblName;
                }
                $name = str_replace('.', '_', $name);
                $this->requiredFields[$name] = $value;
            }
            $newFields[$name] = $value;
        }
        return $newFields;
    }

    /**
     * getQB
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _getQB()
    {
        $qb = new MysqlQueryBuilder();
        $qb->tables = $this->getReqiredTables();
        $qb->fields = $this->getRequiredFields();
        $qb->filters = $this->getFilters();
        $qb->addArgs($this->args);
        return $qb;
    }

    /**
     * getItems
     * 
     * @param int $itemsToGet Description.
     * @param int $offset     Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getItems($itemsToGet = 0, $offset = 0)
    {
        $qb = $this->_getQB();
        $qb->limits = array($offset,$itemsToGet);
        $qb->sorts = $this->sort;
        $retValues = $this->getDB()->getAll($qb->getQuery(), $qb->getArgs());
        $this->_resetToC5DB();
        return $retValues;
    }

    /**
     * getTotal
     * 
     * @param mixed $additionalFilter Description.
     * @param mixed $args             Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getTotal($additionalFilter = null,$args=null)
    {
        $qb = $this->_getQB();
        if ($additionalFilter) {
            $qb->filters = array_merge(
                $this->filters,
                array($this->replaceColNames($additionalFilter))
            );
            if ($args) {
                $qb->addArgs($args);
            }
        }
        
        $qb->fields = array(
            'cnt' => array(
                'db_col' => 'count(' . $this->model->getIdentifierField() . ')'
            )
        );
        $res = $this->getDB()->getRow($qb->getQuery(), $qb->getArgs());
        $this->_resetToC5DB();
        return intval($res['cnt']);
    }

    /**
     * addPattern
     * 
     * @param mixed $pattern Description.
     * @param mixed $args    Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addPattern($pattern,$args)
    {

    }

    /**
     * addFilter
     * 
     * @param mixed $filter Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addFilter($filter)
    {
        if (preg_match('/\w/', $filter)) {
            $filter = $this->replaceColNames($filter);
            $this->filters[] = $filter;
        }
    }

    /**
     * replaceColNames
     * 
     * @param mixed $str Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function replaceColNames($str)
    {
        if (preg_match_all('/`(.*?)`/', $str, $matches)) {
            $matches = $matches[1];
            foreach ($matches as $colName) {
                if ($this->fields[$colName] && $this->fields[$colName]['db_col']) {
                    $str = str_replace(
                        "`$colName`",
                        get_class($this->model)  . '.' . $this->fields[$colName]['db_col'],
                        $str
                    );
                }
            }
        }
        return $str;
    }

    /**
     * setFields
     * 
     * @param mixed $fields Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFields($fields)
    {
        $this->requiredFields = OJUtil::confArrToHash($fields);
    }

    /**
     * addArgs
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addArgs($args)
    {
        if (is_array($args)) {
            $this->args = array_merge($this->args, $args);
        }
        $this->clauseArgs[] = $args;
    }

    /**
     * sortBy
     * 
     * @param mixed $col Description.
     * @param mixed $dir Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function sortBy($col,$dir)
    {
        $this->sort = "$col $dir";
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
}