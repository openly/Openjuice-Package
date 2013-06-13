<?php
/*
 * Created on Jan 22, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */

Loader::model('form/form','openjuice');
Loader::model('form/validator','openjuice');

class OJGenericModel{
	protected $table;
	private $row = array();
	protected $idField = 'id';
	protected $name;
	
	private $errors;
	private $status;
	
	private $stdFieldsMap = array(
		'int' => 'text',
		'rich_text' => 'RichTextarea',
		'no_edit' => 'HiddenWithDisplay'
	);
	
	public function __construct($args){
		if(! $this->name)
			$this->name = get_class($this);
			
		if(is_array($args)){
			$this->loadRow($args);
		}
		if(is_numeric($args)){
			$this->findById($args);
		}
	}
	
	public function findById($id){
		$db = Loader::db();
		$query = t('SELECT * FROM %s WHERE `%s`=?',$this->table,$this->idField);
		$row = $db->getRow($query,array($id));
		$this->loadRow($row);
	}
	private function loadRow($args){
		foreach($this->fields as $field)
				$this->row[$field[0]] = $args[$field[0]];
		$this->row[$this->idField] = $args[$this->idField];
	}
	
	public function save(){
		if($this->validate()){
			$this->on_before_save();
			if($this->isNew()){
				$valueQues = str_repeat('?,',count($this->getDBFieldNames()) - 1) . '?';
				$query = t('INSERT INTO %s (`%s`,%s) VALUES (null,%s)',
								$this->table,
								$this->idField,
								implode(', ',$this->getDBFieldNames()),
								$valueQues
						);
				$this->status = $this->name . ' added.';
			}else{
				$valuesRow = $this->row;
				unset($valuesRow[$this->idField]);
				foreach($this->getNonDBFields() as $field){unset($valuesRow[$field]);}
				$valueQues = '`' . implode('`=?, `',array_keys($valuesRow)) . '`=?';
				$query = t('UPDATE %s SET %s WHERE `%s`=?',
								$this->table,
								$valueQues,
								$this->idField
						);
				$this->status = $this->name . ' updated.';
			}
			$db = Loader::db();
			$db->Execute($query,$this->getValues());
			return $this;
		}
		return false;
	}
	
	public function find(){}
	public function delete(){
		$this->on_before_delete();
		$db = Loader::db();
		$db->Execute('DELETE FROM ' . $this->table . ' WHERE ' . $this->idField . '=?',array($this->row[$this->idField]));
	}
	
	public function validate(){
		Loader::helper('validation/error');
		$errors = new ValidationErrorHelper;
		
		foreach($this->fields as $field){
			$validation = $field[3];
			$key = $field[0];
			$errMsg = $field[4];

			if($validation){
				$validations = explode(' ',$validation);
				foreach($validations as $validation){
					$class = ucfirst($validation) . 'Validator';
					$validator = new $class();
					if(!$validator->test($this->row[$key])){
						 $errors->add($errMsg?
							$errMsg:(($validation == 'required')?
								($field[2] . ' is required.'):('Please enter valid ' . $field[2])));
					}
				}
			}
		}
		
		$this->errors = $errors;
		
		if(count($errors->getList()) > 0)
			return false;
		else
			return true;
	}
	
	public function getErrors(){return $this->errors;}
	public function getStatus(){return $this->status;}
	
	private function getDBFieldNames(){
		$fieldNames = array();
		foreach($this->fields as $field){
			if(preg_match('/NO_DB/',$field[0])){
				continue;
			} 
			$fieldNames[]='`' . $field[0] . '`';
		}
		return $fieldNames;
	}
	
	private function getValues(){
		$values = array();
		foreach($this->fields as $field){
			if(preg_match('/NO_DB/',$field[0])){
				continue;
			} 
			$values[]= $this->row[$field[0]];
		}
		if(!$this->isNew())
			$values[] = $this->row[$this->idField];
		return $values;

	}

	private function getNonDBFields(){
		$fieldNames = array();
		foreach($this->fields as $field){
			if(preg_match('/NO_DB/',$field[0])){
				$fieldNames[] = $field[0];
			} 
		}
		return $fieldNames;
	}
	
	public function getCol($name){return array_key_exists($name,$this->row)?$this->row[$name]:null;}
	public function setCol($name,$val){$this->row[$name] = $val;return $this;}
	
	public function getForm(){
		$form = new OJForm($this->name,
			($this->isNew()?'Add ':'Update ') .$this->name );
		if(!$this->isNew()){
			$form->addField(array('type'=>'hidden','name'=>$this->idField,'default' => $this->row[$this->idField]));
		}
		
		foreach($this->fields as $field){
			$detail = array('name' => $field[0]);
			$detail['type'] = $this->mapField($field[1]);
			$detail['default'] = $this->getCol($field[0]);
			$detail['label'] = $field[2]?$field[2]:ucfirst($field[0]);
			if(preg_match('/required/i',$field[3]))
				$detail['label'] .= ' *';
			$form->addField($detail,$field[5]);
		}
		return $form;
	}
	
	public function isNew(){
		if($this->row && $this->row[$this->idField]){
			return false;
		}
		return true;
	}
	
	private function mapField($name){
		if(array_key_exists($name,$this->stdFieldsMap))
			return $this->stdFieldsMap[$name];
		return $name;
	}

	public function on_before_save(){
	}
	
	public function on_before_delete(){
	}
}
