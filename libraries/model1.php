<?php

Loader::library('templating/renderable','openjuice');
Loader::library('form/wizard','openjuice');
Loader::library('form/form','openjuice');

class OJModel extends Renderable{
	/* Configuration variables */
	protected $meta = array( 'adapter' => 'mysql' );
	protected $identifier = 'id';
	protected $fields = array();

	protected $relations = array();

	protected $searchPatterns = array();

	protected $listTemplates = array();
	protected $templates = array();
	
	/* Functional variables */
	protected $adapter = null;

	protected $steps = array();
	protected $useWizzard = false;
	protected $form = NULL;
	protected $defaultFields = NULL;
	protected $formMultiFieldValidations =NULL;
	protected $formRules =NULL;

	protected $status = NULL;

	public function __construct($id=null){
		$this->init();
		if($id != null)
			$this->load($id);
	}

	public function getList(){
		return $this->getDataAdapterObj()->getList();
	}

	protected function getDataAdapterObj(){
		if(!$this->adapter){
			Loader::library('data_adapter/' . $this->meta['adapter'],'openjuice');
			$className = ucfirst($this->meta['adapter']) . 'DataAdapter';
			$this->adapter = new $className(&$this,&$this->meta,&$this->fields,&$this->relations);
		}
		return $this->adapter;
	}

	public function search(){
		$args = func_get_args();
		if(is_string($args[0])){
			return $this->searchFromPattern($args[0],$args[1]);
		}else if(is_array($args[0])){
			return $this->filter($args[0],$args[1]);
		}
	}

	public function searchFromPattern($pattern,$args){
		if(in_array($pattern,array_keys($this->searchPatterns))) {
			$pattern = $this->searchPatterns[$pattern];
			$list = $this->getList();
			$list->addPattern($pattern,$args);
			return $list;
		}
	}

	public function __call($fn,$args){
		if(preg_match('/^search/', $fn)){
			$arg = lcfirst(preg_replace('/^search/', '', $fn));
			return $this->search($arg,$args[0]);
		}
	}

	public function getListTemplate($str = null){
		if($str == null){
			return $this->listTemplates[0];
		}
	}

	public function getForm()
	{
		if($this->form == NULL){
			if($this->useWizzard)
				$this->form = $this->getWizard();
			else
				$this->form = new OJForm($this->fields,$this->formMultiFieldValidations,$this->formRules,$this->fieldGroups);
		}
		return $this->form;
	}

	private function getWizard(){
		if(count($this->steps)>0)
		{
			$wiz = OJWizard::getWizard(&$this->fields,$this->steps,$this->formMultiFieldValidations,$this->formRules,$this->fieldGroups);
		}
		return $wiz;
	}

	public function getValues(){
		$this->formValues = $this->useWizzard ? $this->form->getValues() : $this->form->getFieldValues();
		return $this->formValues;
	}

	public function save(&$args,$id = NULL)
	{	//var_dump($args);exit;
		$id = ($id != NULL && $id > 0) ? $id : $this->formValues[$this->identifier];
		if($id > 0)
		{
			$this->getDataAdapterObj()->update($id,$args);
			$this->status = "Updated";
		}
		else
		{
			$this->getDataAdapterObj()->create($args);
			$this->status = "Added";
		}
	}

	public function process(){
		if($this->useWizzard)
		{
			$wiz = $this->getForm();
			$wiz->process();
			if($wiz->isComplete())
			{
				$args = $this->getValues();
				$this->on_before_save(&$args);
				$this->save(&$args);
				$this->on_after_save(&$args);
			}
		}
		else
		{
			$form = $this->getForm();
			$args = $this->getValues();var_dump($args);exit;
			if($form->validate($args))
			{
				$this->on_before_save(&$args);

				if($this->isNewRecord())
				{
					$this->getDataAdapterObj()->create($args);
					$this->status = "Added";					
				}
				else
				{
					$this->getDataAdapterObj()->update($args[$this->identifier],$args);
					$this->status = "Updated";
				}

				$this->on_after_save(&$args);
			}
		}
	}

	private function isNewRecord(){
		$value = $this->getValues();
		return !(isset($value[$this->identifier]) && !empty($value[$this->identifier]));
	}

	public function getIdentifierField(){
		return $this->identifier;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getDefaultFields(){
		return $this->defaultFields;
	}

	public function getFields(){
		return $this->fields;
	}

	public function getDistinctRows($filter = NULL,$args = NULL){
		if($this->defaultFields == NULL)
		{
			foreach($this->fields as $key=>$field)
				if(isset($field['db_col']))
				{
					$this->defaultFields[] = $key;
					break;
				}
		}
		$rows = $this->getDataAdapterObj()->getDistinctRows($filter,$args);
		$rowStrs = array();
		foreach($rows as $row)
		{
			$id = $row[$this->identifier];
			$rowStrs[$id] = $this->getRowString($row);
		}
		return $rowStrs;
	}

	public function getDistinctRowsFor($col,$filter=null,$args=null){
		return $this->getDataAdapterObj()->getDistinctRowsFor($col,$filter,$args);
	}

	public function getRowString(&$row){
		$str = "";
		//var_dump($row);
		foreach($row as $key=>$col)
		{ 
			if($key == $this->identifier)
				continue;
			$str .= $col.'-';
		}
		return substr($str,0,-1);
	}

	public function isWizard(){
		return $this->useWizzard;
	}

	public function load($id){

		if(!$this->useWizzard)
		{
			$this->addIDField($id);
		}

		$form = $this->getForm();

		//To dp:if it is new form, then only load
		if(!$this->useWizzard || $form->isNewWizard())
		{
			$this->on_before_load();
			$values = $this->getDataAdapterObj()->load($id);
			$this->on_after_load(&$values);
			$form->setFieldValues($values);
		}
		
	}

	public function addIDField($id){
		$field['name'] = $this->identifier;
		$field['db_col'] = $this->identifier;
		$field['default'] = $id;
		$field['type'] = 'hidden';
		$this->fields[$this->identifier] = $field;
	}

	public function getMeta(){
		return $this->meta;
	}

	public function usesWizzard(){
		return $this->useWizzard;
	}

	public function delete($id){
		if($id != null){
			$this->getDataAdapterObj()->delete($id);
		}
	}


	protected function on_before_load()
	{

	}

	protected function on_after_load(&$args = NULL)
	{

	}

	protected function on_before_save(&$args = NULL)
	{

	}

	protected function on_after_save()
	{
		
	}	
	protected function init(){
	}
}
