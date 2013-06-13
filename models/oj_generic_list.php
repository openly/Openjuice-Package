<?php
Loader::model('list/converter','openjuice');
Loader::model('list/wrapper','openjuice');
/*
 * Created on Jan 22, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */
class OJGenericList extends DatabaseItemList{
	protected $table;
	protected $joins = array();
	protected $searchFields;
	
	protected $name;
	protected $pageTitle;

	private $ajaxUrl;
	private $searchUrl;
	private $deleteUrl;
	
	protected $displayFields;
	
	protected $idField = 'id';
	
	protected $showDelete = true;
	
	public function __construct(){
		$query = t('SELECT * FROM %s',$this->table);
		foreach($this->joins as $tbl => $clause){
			$query .= t(' inner join `%s` on %s',$tbl,$clause);
		}
		$this->setQuery($query);
	}
	
	public function setEditUrl($url){$this->editURL = $url;}
	public function setAjaxUrl($url){$this->ajaxURL = $url;}
	public function setDeleteUrl($url){$this->deleteUrl = $url;}
	
	public function getEditUrl(){return $this->editURL;}
	public function getAjaxUrl(){return $this->ajaxURL;}
	public function getDeleteUrl(){return $this->deleteUrl;}
	
	public function getIDField(){return $this->idField;}
	
	public function getTitle(){return ($this->pageTitle?$this->pageTitle:('Search ' . $this->name));}
	
	public function getHeaders(){
		$retval = array();
		foreach($this->displayFields as $filed){
			$retval[] = $filed[1];
		}
		if($this->showDelete){
			$retval[] = '';
		}
		return $retval;
	}
	
	public function getAddButton(){
		$ih = Loader::helper('concrete/interface');
		return $ih->button(('Add ' . $this->name), $this->editURL,'right','primary');
	}
	
	public function getRecords(){
		$recs = $this->get();
		$retval = array();
		foreach($recs as $rec){
			$theRec = array();
			foreach($this->displayFields as $field){
				$val = $rec[$field[0]];
				$class = $field[2];
				if($class){
					$classAttrs = split(' ',$class);
					$class = array_shift($classAttrs);
					$class = ucfirst($class) . "Converter"; 
					$converter = new $class($classAttrs);
					$val = $converter->convert($val);
				}
				if($field[3]){
					$class = ucfirst($field[3]) . "Wrapper";
					$wrapper = new $class($this,$rec);
					$val = $wrapper->wrap($val);
				}
				$theRec[] = $val;
			}
			if($this->showDelete){
				$deleteWrapper = new DeleteWrapper($this,$rec);
				$theRec[] = $deleteWrapper->wrap('Delete');
			}
			$retval[] = $theRec;
		}
		return $retval;
	}
	
}
