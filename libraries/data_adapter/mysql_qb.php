<?php

Loader::library('util', 'openjuice');

/**
* MysqlQueryBuilder
*
* @uses     
*
* @category Category
* @package  Package
* @author   Abhi
*/
class MysqlQueryBuilder
{

    protected $fields;
    protected $tables;
    protected $filters;
    protected $sorts;
    protected $limits;

    protected $hasNull = false;

    protected $unsortedArgs = array();

    protected $args = array();

    /**
     * __construct
     * 
     * @param mixed  &$tables  Description.
     * @param string &$fields  Description.
     * @param string &$filters Description.
     * @param string &$limits  Description.
     * @param string &$sorts   Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct(&$tables=null,
        &$fields='*',
        &$filters='1=1',
        &$limits='',
        &$sorts=''
    ) {
        $this->tables  =& $tables;
        $this->fields  =& $fields;
        $this->filters =& $filters;
        $this->limits  =& $limits;
        $this->sorts   =& $sorts;
    }

    /**
     * __set
     * 
     * @param mixed $name Description.
     * @param mixed $val  Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __set($name,$val)
    {
        if ($val == null) {
            return;
        }

        $this->{$name} = $val;
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
        return $this->{$name};
    }

    /**
     * addArg
     * 
     * @param mixed $name Description.
     * @param mixed $val  Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addArg($name,$val)
    {
        $this->unsortedArgs[$name] = $arg;
    }

    /**
     * addArgs
     * 
     * @param mixed $arr Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function addArgs($arr)
    {
        if ($arr != null) {
            $this->unsortedArgs = array_merge($this->unsortedArgs, $arr);
        }
    }

    /**
     * getArgs
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * getQuery
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getQuery()
    {
        if(! $this->canBuildQuery())
            throw new Exception("Not enough data to build the query", 1);
        $this->args = array();

        return 'SELECT ' . $this->getFields() .
        ' FROM ' . $this->getTables() .
        ' WHERE ' . $this->getFilter() .
        $this->getSort() . $this->getLimits();
    }

    /**
     * canBuildQuery
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function canBuildQuery()
    {
        if(isset($this->tables) && isset($this->fields)) {
            return true;
        }
    }

    /**
     * getFields
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getFields()
    {
        if (is_string($this->fields)) {
            return $this->fields;
        }
        foreach ($this->fields as $key => $field) {
            if (is_array($field)) {
                if ($field['distinct']) {
                    $fieldArr[] = 'distinct(' . $field['db_col'] . ') ' . $key;
                } else {
                    $fieldArr[] = $field['db_col'] . ' ' . $key;    
                }
            } else {
                $fieldArr[] = $field;
            }
        }
        return implode(',', $fieldArr);
    }

    /**
     * getTables
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getTables()
    {
        if (is_string($this->tables)) {
            return $this->tables;
        }
        $tablesArr = array();
        foreach ($this->tables as $alias => $table) {
            $tablesArr[] = $table . ' ' . $alias;
        }
        return implode(', ', $tablesArr);
    }

    /**
     * getFilter
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getFilter()
    {
        if (OJUtil::isAssoc($this->filters)) {
            $this->args = array_merge(
                $this->args,
                array_values($this->filters)
            );
            return implode('=? AND ', array_keys($this->filters)) . '=?';
        } else if (is_array($this->filters)) {
            $filterStr = implode(' AND ', $this->filters);
        } else if (is_string($this->filters)) {
            $filterStr = $this->filters;
        } else {
            $filterStr = '1=1';
        }
        preg_match_all('/\{\{(.*?)\}\}/', $filterStr, $matches);
        $this->hasNull = false;
        foreach ($matches[1] as $match) {
            // 0 has all the matches and 1 and later will have groups
            if($this->unsortedArgs[$match] == null)
                $this->hasNull = true;
            $this->args[] = $this->unsortedArgs[$match];
        }
        return preg_replace('/\{\{.*?\}\}/', '?', $filterStr);
    }

    /**
     * hasNull
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function hasNull()
    {
        return $this->hasNull;
    }

    /**
     * getSort
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getSort()
    {
        if (!$this->sorts) {
            return '';
        }
        return ' ORDER BY ' . (is_array($this->sorts) ?
        join(',', $this->sorts) : $this->sorts);
    }

    /**
     * getLimits
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getLimits()
    {
        if (is_array($this->limits)) {
            return ' LIMIT ' . $this->limits[0] . ',' . $this->limits[1];
        }
        return '';
    }
}