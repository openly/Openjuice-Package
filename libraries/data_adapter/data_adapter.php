<?php

abstract class DataAdapter {

	protected $model;
	protected $fields;
	protected $meta;
	protected $relations;
	protected $list;


	function __construct(&$model,&$meta,&$fields,&$relations){
		$this->model     =& $model;
		$this->meta      =& $meta;
		$this->fields    =& $fields;
		$this->relations =& $relations;
	}

	public function getList(){
		if(! is_object($this->_list)){
			$this->_list = $this->generateList();
		}
		return $this->_list;
	}

	protected abstract function generateList();
	public abstract function create($args);
	public abstract function update($id,$args);
	public abstract function load($id);
	public abstract function delete($id);
	public abstract function find($filter,$args,$sort);
}