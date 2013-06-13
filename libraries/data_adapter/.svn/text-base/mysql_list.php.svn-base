<?php

/**
* 
*/
class MysqlList extends DataList
{
	
	protected $model;

	protected $table;

	protected $_filters        = array();
	protected $_sort           = '';
	protected $_args     	   = array();
	protected $_sortArgs       = array();
	protected $_requiredFields = array();

	protected $_relModelObjs = array();

	protected $_db;

	protected function getDB(){
		if(! is_object($this->_db)){
			$this->_db = Loader::db();
		}
		return $this->_db;
	}

	protected function getRelModelObj($name){
		if(!in_array($name, array_keys($this->_relModelObjs)))
			$this->_relModelObjs[$name] = new $this->relations[$name]['model']();
		return $this->_relModelObjs[$name];
	}

	protected function getReqiredTables(){
		$tables = array();
		$modelClsName = get_class($this->model);
		$tables[$modelClsName] = $this->meta['table'];
		foreach ($this->_requiredFields as $name=>$value) {
			list($tblName,$colName) = explode('.',$name);
			if(!$colName) continue; // No "." so field from same table
			if(in_array($tblName, array_keys($this->relations))){
				$relModel = $this->getRelModelObj($tblName);
				$relModelMeta = $relModel->getMeta();
				$tables[$tblName] = $relModelMeta['table'];
				$this->_filters[] = $this->relations[$tblName]['key'] . '=' . $tblName . '.' . $relModel->getIdentifierField();
			}else{
				$tables[$tblName] = $tblName;
			}
		}
		return $tables;
	}

	protected function getFilters(){
		return $this->_filters;
	}

	protected function getRequiredFields(){
		$newFields = array('__id__'=> array('db_col'=>$this->model->getIdentifierField()));
		foreach ($this->_requiredFields as $name => $value) {
			list($tblName,$colName) = explode('.',$name);
			if(!$colName){
				if(is_array($this->fields[$name]))
					$value = array_merge($this->fields[$name],$value);
			}else{
				if(in_array($tblName, array_keys($this->relations))){
					$relModel = $this->getRelModelObj($tblName);
					$relModelFields = $relModel->getFields();
					if(is_array($relModelFields[$colName])){
						$value = array_merge($relModelFields[$colName],$value);
						$value['db_col'] = $tblName . '.' . $value['db_col'];
					}
				}else{
					$tables[$tblName] = $tblName;
				}
				$name = str_replace('.', '_', $name);
				$this->_requiredFields[$name] = $value;
			}
			$newFields[$name] = $value;
		}
		return $newFields;
	}

	private function getQB(){
		$qb = new MysqlQueryBuilder();
		$qb->tables = $this->getReqiredTables();
		$qb->fields = $this->getRequiredFields();
		$qb->filters = $this->getFilters();
		$qb->addArgs($this->_args);
		return $qb;
	}

	public function getItems($itemsToGet = 0, $offset = 0){
		$qb = $this->getQB();
		$qb->limits = array($offset,$itemsToGet);
		$qb->sorts = $this->_sort;
		//echo $qb->getQuery() . '<br />'; var_dump($qb->getArgs());echo '<br />';exit;
		return $this->getDB()->getAll($qb->getQuery(),$qb->getArgs());
	}

	public function getTotal($additionalFilter = null,$args=null){
		$qb = $this->getQB();
		if($additionalFilter){
			$qb->filters = array_merge($this->_filters,array($this->replaceColNames($additionalFilter)));
			if($args) $qb->addArgs($args);
		}
		
		$qb->fields = array('cnt' => array('db_col'=>'count(' . $this->model->getIdentifierField() . ')'));
		$res = $this->getDB()->getRow($qb->getQuery(),$qb->getArgs());
		return intval($res['cnt']);
	}

	public function addPattern($pattern,$args){

	}

	public function addFilter($filter){
		if(preg_match('/\w/', $filter)){
			$filter = $this->replaceColNames($filter);
			$this->_filters[] = $filter;
		}
	}

	protected function replaceColNames($str){
		if(preg_match_all('/`(.*?)`/', $str,$matches)){
			$matches = $matches[1];
			foreach ($matches as $colName) {
				if($this->fields[$colName] && $this->fields[$colName]['db_col'])
					$str = str_replace("`$colName`",get_class($this->model)  . '.' . $this->fields[$colName]['db_col'], $str);
			}
		}
		return $str;
	}

	public function setFields($fields){
		$this->_requiredFields = OJUtil::confArrToHash( $fields );
	}

	public function addArgs($args){
		if(is_array($args)){
			$this->_args = array_merge($this->_args,$args);
		}
		$this->_clauseArgs[] = $args;
	}

	public function sortBy($col,$dir){
		$this->_sort = "$col $dir";
	}
}