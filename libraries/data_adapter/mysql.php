<?php

Loader::library('data_adapter/data_adapter','openjuice');
Loader::library('data_adapter/data_list','openjuice');
Loader::library('data_adapter/mysql_list','openjuice');
Loader::library('data_adapter/mysql_qb','openjuice');

class MysqlDataAdapter extends DataAdapter{

	protected $model;

	protected $table;

	protected $_clause         = '';
	protected $_sort           = '';
	protected $_clauseArgs     = array();
	protected $_sortArgs       = array();
	protected $_requiredFields = array();
	private $_dbColNames = NULL;
	private $_fieldValues = NULL;
	protected $_db = NULL;
	
	protected function generateList(){
		return new MysqlList($this->model,$this->meta,$this->fields,$this->relations);
	}

	public function create($args){
		$this->getDB();
		$valueQues = str_repeat('?,',count($this->getDBFieldNames($args)) - 1) . '?';
		$query = t('INSERT INTO `%s` (`%s`,%s) VALUES (null,%s)',
								$this->meta['table'],
								$this->model->getIdentifierField(),
								implode(', ',$this->_dbColNames),
								$valueQues
						);

		$retValues = $this->_db->Execute($query, $this->_fieldValues);
		$this->resetToC5DB();
		return $retValues;
	}

	public function update($id,$args){
		$this->getDB();
		$this->getDBFieldNames($args);
		$idField = $this->model->getIdentifierField();
		unset($this->_dbColNames[$idField]);
		$valueQues = implode('=?, ',$this->_dbColNames) . '=?';

		$query = t('UPDATE `%s` SET %s WHERE `%s`=?',
						$this->meta['table'],
						$valueQues,
						$idField
					);
		$this->_fieldValues[] = $id;
		$retValues = $this->_db->Execute($query, $this->_fieldValues);
		$this->resetToC5DB();
		return $retValues;
	}

	public function load($id){
		$qb = $this->initQB();
		$idField = $this->model->getIdentifierField();

		foreach($this->fields as $key=>$field)
		{
			if(isset($field['db_col']))
				$qbField[$key]['db_col'] = $field['db_col'];
		}

		$qbField[$idField]['db_col'] = $idField;

		$qb->fields = $qbField;
		
		$qb->filters = array($idField=>$id);
		$query = $qb->getQuery();
		$values = $qb->getArgs();
		$retValues = $this->getDB()->getRow($query,$values);
		$this->resetToC5DB();
		return $retValues;
	}

	public function delete($id){
		$this->getDB();
		$idField = $this->model->getIdentifierField();
		$query = t('DELETE FROM `%s` WHERE `%s`=?',
				$this->meta['table'],
				$idField
		);
		$retValues = $this->_db->Execute($query, array($id));
		$this->resetToC5DB();
		return $retValues;
	}

	protected function initQB(){
		$qb = new MysqlQueryBuilder();
		$qb->tables = $this->meta['table'];
		return $qb;
	}

	protected function getQBFilterStr($filter){
		if($filter != NULL){
			preg_match_all("/`(.*?)`/", $filter, $dbCols);
			foreach($dbCols[1] as $dbCol)
			{
				
				$filter = str_replace("`$dbCol`", "`{$this->fields[$dbCol]['db_col']}`", $filter);
				$param[$dbCol] = $args[$dbCol];
			}
			return $filter;
		}
		return null;
	}

	public function getDistinctRowsFor($col,$filter=null,$args=null){
		if($args == null) $args = $_POST;

		$qb = $this->initQB();
		$field = array('db_col' => $this->fields[$col]['db_col'],'distinct' => true);

		$qb->fields = array($col => $field);
		$qb->filters = $this->getQBFilterStr($filter);
		$qb->addArgs($args);
		$query = $qb->getQuery();
		$values = $qb->getArgs();
		//echo $query;var_dump($values);
		if($qb->hasNull()){return array();}
		$rows = array();
		foreach($this->getDB()->getAll($query,$values) as $row){
			$rows[] = $row[$col];
		}
		$this->resetToC5DB();
		return $rows;
	}

	public function getDistinctRows($filter = NULL,$args = NULL){
		$defaultFields = $this->model->getDefaultFields();
		if($defaultFields == NULL)
		{
			foreach($this->fields as $key=>$field)
				if(isset($field['db_col']))
				{
					$defaultFields[] = $key;
					break;
				}
		}

		if($args == NULL) $args = $_POST;
		$this->getDB();
		$qb = $this->initQB();

		$selectField = $this->model->getIdentifierField();
		$field[$selectField]['db_col'] = $selectField;
		foreach($this->model->getDefaultFields() as $defField)
		{
			if(isset($this->fields[$defField]))
			{
				$field[$defField]['db_col'] = $this->fields[$defField]['db_col'];
			}
		}
		$qb->fields = $field;
		$qb->filters = $this->getQBFilterStr($filter);
		
		$qb->addArgs($args);
		$query = $qb->getQuery();
		$values = $qb->getArgs();
		if($qb->hasNull()){return array();}
		$values = $this->getDB()->getAll($query,$values);
		$this->resetToC5DB();
		return $values;
	}

	private function getDB()
	{
		// if($this->_db == NULL){
			if(!empty($this->meta)){
				$this->_db = Loader::db($this->meta['server'],$this->meta['userName'],$this->meta['password'],$this->meta['database'],$this->meta['autoConnect']);
			}else{
				$this->_db = Loader::db(null, null, null, null, true);
			}
		// }
			
		return $this->_db;
	}

	private function resetToC5DB(){
		if(!empty($this->meta)){
			$this->_db = Loader::db(null, null, null, null, true);
		}
	}

	private function getDBFieldNames($values = NULL){
		if($values == NULL) $values = $this->model->getValues();
		foreach($this->fields as $key=>$field){
			if(isset($field["db_col"])){
				$this->_dbColNames[] ='`' . $field["db_col"] . '`';
				$this->_fieldValues[] = $values[$key];
			}
		}
		return $this->_dbColNames;
	}

	public function find($filter,$args = null)
	{
		$st = microtime(true);
		$qb = $this->initQB();
		$idField = $this->model->getIdentifierField();

		foreach($this->fields as $key=>$field)
		{
			if(isset($field['db_col']))
				$qbField[$key]['db_col'] = $field['db_col'];
		}

		$qbField[$idField]['db_col'] = $idField;

		$qb->fields = $qbField;
		$qb->addArgs($args);
		$qb->filters = $this->getQBFilterStr($filter);
		
		$query = $qb->getQuery();
		$values = $qb->getArgs();
		$retValues = $this->getDB()->getAll($query,$values);
		$this->resetToC5DB();
		return $retValues;
	}
}
