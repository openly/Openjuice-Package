<?php

class DataList extends ItemList{

	protected $model;
	protected $fields;
	protected $meta;
	protected $relations;

	protected $_convert = true;

	public function __construct(&$model,&$meta,&$fields,&$relations){
		$this->model     =& $model;
		$this->meta      =& $meta;
		$this->fields    =& $fields;
		$this->relations =& $relations;
	}

	public function get($itemsToGet = 0, $offset = 0)
	{
		$items = $this->getItems($itemsToGet, $offset);
		if(! $this->_convert)
			return $items;
		$newItems = array();
		$modelClass = get_class($this->model);
		foreach($items as $item)
		{
			$newItems[]=new $modelClass($item);
		}
		return $newItems;
	}

	public function render($template){
		$templateArr = array('required_fields' => '*');
		if($this->model->getListTemplate($template)){
			$templateArr - $this->model->getListTemplate($template);
		}else if(is_array($template)){
			$templateArr = $template;
		}else{
			$templateArr['template'] = $template;
		}
	}

	public function setConvert($bool){
		$this->_convert = $bool;
	}
}