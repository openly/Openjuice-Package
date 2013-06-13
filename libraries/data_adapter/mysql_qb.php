<?php

/**
* 
*/
Loader::library('util','openjuice');

class MysqlQueryBuilder
{

	protected $_fields;
	protected $_tables;
	protected $_filters;
	protected $_sorts;
	protected $_limits;

	protected $hasNull = false;

	protected $_unsortedArgs = array();

	protected $_args = array();

	public function __construct(&$tables=null,&$fields='*',&$filters='1=1',&$limits='',&$sorts=''){
		$this->_tables  =& $tables;
		$this->_fields  =& $fields;
		$this->_filters =& $filters;
		$this->_limits  =& $limits;
		$this->_sorts   =& $sorts;
	}

	public function __set($name,$val){
		if($val == null)return;
		$var = '_' . $name;
		$this->{$var} = $val;
	}

	public function __get($name){
		$var = '_' . $name;
		return $this->{$var};
	}

	public function addArg($name,$val){
		$this->_unsortedArgs[$name] = $arg;
	}

	public function addArgs($arr){
		if($arr != null)
			$this->_unsortedArgs = array_merge($this->_unsortedArgs,$arr);
	}

	public function getArgs(){
		return $this->_args;
	}

	public function getQuery(){
		if(! $this->canBuildQuery())
			throw new Exception("Not enough data to build the query", 1);
		$this->_args = array();

		return 'SELECT ' . $this->getFields() . ' FROM ' . $this->getTables() . ' WHERE ' . $this->getFilter() . $this->getSort() . $this->getLimits();
	}

	protected function canBuildQuery(){
		if(isset($this->_tables) && isset($this->_fields))
			return true;
	}

	protected function getFields(){
		if(is_string($this->_fields)){
			return $this->_fields;
		}
		foreach ($this->_fields as $key => $field) {
			if(is_array($field)){
				if($field['distinct']){
					$fieldArr[] = 'distinct(' . $field['db_col'] . ') ' . $key;
				}else{
					$fieldArr[] = $field['db_col'] . ' ' . $key;	
				}
			}else{
				$fieldArr[] = $field;
			}
		}
		return implode(',', $fieldArr);
	}

	protected function getTables(){
		if(is_string($this->_tables)){
			return $this->_tables;
		}
		$tablesArr = array();
		foreach ($this->_tables as $alias => $table) {
			$tablesArr[] = $table . ' ' . $alias;
		}
		return implode(', ', $tablesArr);
	}

	protected function getFilter(){
		if(OJUtil::isAssoc($this->_filters)){
			$this->_args = array_merge($this->_args,array_values($this->_filters));
			return implode('=? AND ', array_keys($this->filters)) . '=?';
		}else if(is_array($this->_filters)){
			$filterStr = implode(' AND ', $this->_filters);
		}else if(is_string($this->_filters)){
			$filterStr = $this->_filters;
		}else{
			$filterStr = '1=1';
		}
		preg_match_all('/\{\{(.*?)\}\}/', $filterStr, $matches);
		$this->hasNull = false;
		foreach ($matches[1] as $match) { // 0 has all the matches and 1 and later will have groups
			//echo "HERE: " . implode(", ", array_values($this->_unsortedArgs));echo $match . '<br />';
			if($this->_unsortedArgs[$match] == null)
				$this->hasNull = true;
			$this->_args[] = $this->_unsortedArgs[$match];
		}
		return preg_replace('/\{\{.*?\}\}/', '?', $filterStr);
	}

	public function hasNull(){
		return $this->hasNull;
	}

	protected function getSort(){
		if(!$this->_sorts) return '';
		return ' ORDER BY ' . (is_array($this->_sorts)?join(',',$this->_sorts):$this->_sorts);
	}

	protected function getLimits(){
		if(is_array($this->_limits))
			return ' 
		LIMIT ' . $this->_limits[0] . ',' . $this->_limits[1];
		return '';
	}
	/*if(is_array($fields)){
			$fieldArr = array();
			foreach ($fields as $key => $field) {
				if(is_array($field)){
					$fieldArr[] = $field['db_col'] . ' ' . $key;
				}else{
					$fieldArr[] = $field;
				}
			}
			$fieldsStr = implode(',', $fieldArr);
		}else{
			$fieldsStr = $fields;
		}

		if(is_array($clause)){
			$clauseArr = array();

			foreach ($clause as $col => $theClause) {
				if(is_array($theClause)){
					$clauseArr[] = $col . ' in (' . implode(',',$key) . ')';
				}else{
					$fieldArr[] = $field;
				}
			}
			$fieldsStr = implode(',', $fieldArr);
		}else{
			$fieldsStr = $fields;
		} 
	*/
}