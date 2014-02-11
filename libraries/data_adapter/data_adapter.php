<?php

/**
* DataAdapter
*
* @uses     
*
* @category Category
* @package  Package
* @author   Abhi
*/
abstract class DataAdapter
{

    protected $model;
    protected $fields;
    protected $meta;
    protected $relations;
    protected $list;


    /**
     * __construct
     * 
     * @param mixed &$model     Description.
     * @param mixed &$meta      Description.
     * @param mixed &$fields    Description.
     * @param mixed &$relations Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function __construct(&$model,&$meta,&$fields,&$relations)
    {
        $this->model     =& $model;
        $this->meta      =& $meta;
        $this->fields    =& $fields;
        $this->relations =& $relations;
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
        if (!is_object($this->list)) {
            $this->list = $this->generateList();
        }
        return $this->list;
    }

    /**
     * generateList
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected abstract function generateList();
    

    /**
     * create
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public abstract function create($args);
    
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
    public abstract function update($id,$args);
    
    /**
     * load
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public abstract function load($id);
    
    /**
     * delete
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public abstract function delete($id);
    
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
    public abstract function find($filter,$args,$sort);
}