<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('form/form','openjuice');

class OJWizard extends Renderable{
	
	protected $_steps = array();
	protected $_fields = array();
	//protected $currentStep = null;
	protected $currentStepName = null;
	protected $_currentStepFields = array();
	protected $_count = 0;
	protected $currentStepIndex = 0;
	protected $sessionKey = null;
	protected $values = array();
	protected $_errors = array();
	protected $completed = false;
	protected $showUnSteppedFields = true;
	protected $formMultiFieldValidations = array();
	protected $formRules = NULL;
	protected $fieldGroups = NULL;
	protected $fieldTemplate = null;

	private $_stepValidations = NULL;

	protected $_stepsKeys =array();

	protected $prevBtnName = "Previous";
	protected $nextBtnName = "Next";
	protected $finBtnName = "Finish";

	protected $template = "{{{formFields}}";

	protected $completeTemplate = "<h2>Wizard Completed....Data saved to the DB.</h2>";

	public function __construct(&$fields,&$steps,&$formMultiFieldValidations = NULL,&$formRules = NULL,&$fieldGroups = null){
		$this->_fields = $fields;
		$this->formMultiFieldValidations = $formMultiFieldValidations;
		$this->formRules = $formRules;
		$this->fieldGroups = $fieldGroups;
		
		foreach($this->_fields as $key=>$field)
		{
			if(isset($field["show_in_form"]) && !$field["show_in_form"])
				unset($this->_fields[$key]);
			elseif(!isset($field["step"]))
				$this->_fields[$key]["step"] = "NoStepName";
		}
				
		$this->_steps = $steps;
		if($this->showUnSteppedFields)
		{
			$this->currentStepName = "NoStepName";//$this->_stepsKeys[0];
			$this->getCurrentStepFields();
			if(count($this->currentStepFields)>0)
			{
				$this->_steps["NoStepName"] = "No Step Name";
			}
		}
		
		$this->_count = count($this->_steps);
		$this->_stepsKeys = array_keys($this->_steps);
		
		$this->currentStepName = $this->_stepsKeys[0];
		//$this->getCurrentStepFields();
		$this->currentStepIndex = 0;
		
		$this->sessionKey = get_class($this) . date('Ymdhis');
		$this->saveToSession();
	}
	
	public static function getWizard(&$fields,&$steps,&$formMultiFieldValidations,&$formRules,&$fieldGroups){
		session_start();
		if($_REQUEST['wizard_key']){
			$key = $_REQUEST['wizard_key'];
			return unserialize($_SESSION[$key]);
		}
		return new OJWizard($fields,$steps,$formMultiFieldValidations,$formRules,$fieldGroups);
	}

	public function process(&$args = null){
		if(!$args) $args = $_POST;
		$this->values = array_merge($this->values,$this->getForm()->getFieldValues());
		//$this->values = array_merge($this->values,$args);

		if(isset($args[$this->nextBtnName]))
			$this->gotoNextStep();
		else if(isset($args[$this->prevBtnName]))
			$this->gotoPreviousStep();
		else if(isset($args[$this->finBtnName]))
			$this->complete();
		else
			echo "Invalid Button";
	}

	private function validateForm($args =NULL)
	{
		if($args == NULL) $args = $_POST;
		$retValue = true;
		$form = $this->getForm();
		if(!$form->validate($args)){
			$this->_errors = $form->getErrors();
			$this->_stepValidations[$this->currentStepIndex] = false;
			$retValue = false;
		}

		return $retValue;
	}

	public function gotoNextStep(){		
		//validate the current form Fields and then get the next step fields
		if(!$this->validateForm()) return;

		$this->_stepValidations[$this->currentStepIndex] = true;
		$this->currentStepIndex++;

		if($this->currentStepIndex == $this->_count){
			$this->completed = true;
			$this->currentStepFields = array();
			return;
		}

		$this->currentStepName = $this->_stepsKeys[$this->currentStepIndex];

		//$this->getCurrentStepFields();
		
		$this->saveToSession();
	}

	public function gotoPreviousStep(){
		if($args == NULL) $args = $_POST;
		
		$form = $this->getForm();
		if(!$form->validate($args))
			unset($this->_stepValidaions[$this->currentStepIndex]);
		
		$this->currentStepIndex--;
		if($this->currentStepIndex == -1){
			$this->currentStepIndex = 0;
		}
		$this->currentStepName = $this->_stepsKeys[$this->currentStepIndex];
		//$this->getCurrentStepFields();
		$this->saveToSession();		
	}

	private function saveToSession(){
		$_SESSION[$this->sessionKey] = serialize($this);
	}

	public function reset(){
		$this->currentStepName =$steps[0];
		$this->currentStepIndex = 0;
	}


	public function getMarkup($args=NULL){
		$retstr = t('<input type="hidden" name="%s" value="%s">','wizard_key',$this->sessionKey);

		$m = new Mustache;
		
		if($this->completed)
		{
			$retstr .= $m->render($this->completeTemplate);
		}
		else
		{
			$form = $this->getForm();
			$form->setFieldValues($this->values);
			$formFields = $form->getMarkup($this->values);

			//To Do: Call these methods and check in Mustache itself
			if($this->hasPrevStep())
				$previousButton = array("prevBtnName"=>$this->prevBtnName);

			if($this->hasNextStep())
				$nextButton = array("nextBtnName"=>$this->nextBtnName);

			if($this->canFinishAtThisStep())
				$finButton = array("finBtnName"=>$this->finBtnName);

			$data = array("currentStepName" => $this->getCurrentStepDisplayName(),
							"currentStepNumber" => $this->currentStepIndex + 1,
							"TotalNumberOfSteps" => $this->count,
							"prevBtnExists"=>$previousButton,
							"nextBtnExists"=>$nextButton,
							"finBtnExists"=>$finButton,
							"formFields"=>$formFields
						);

			$retstr .= $m->render($this->template,$data);
		}
		return $retstr;
	}

	public function render($args = null){
		echo $this->getMarkup($args);
	}

	public function renderField($field,$args=null)
	{
			$this->getForm()->renderField($field,$args);
	}

	public function getForm(){
		$this->getCurrentStepFields();
		$frm = new OJForm($this->currentStepFields,
			$this->formMultiFieldValidations[$this->currentStepName],
			$this->formRules,$this->fieldGroups,$this->values);

		if($this->fieldTemplate != null){ $frm->setFieldTemplate($this->fieldTemplate);}
		return $frm;
	}

	public function hasPrevStep(){
		return !$this->completed && $this->currentStepIndex > 0; 
	}

	public function hasNextStep(){
		return !$this->completed && $this->currentStepIndex < ($this->_count - 1);
	}

	public function canFinishAtThisStep(){
		//Need to code here to check whether it can finish the wizard at this time
		$canFinish = false;
		if(!$this->completed)
		{
			$canFinish = true;
			$currentstepIndex = $this->currentStepIndex;
			$reload = false;

			if($this->hasNextStep())
			{
				for($i = 0; $i<$this->_count;$i++)
				{
					if(!isset($this->_stepValidations[$i]))
					{
						$this->currentStepIndex = $i;
						$this->currentStepName = $this->_stepsKeys[$this->currentStepIndex];
						$this->getCurrentStepFields();
						$this->_stepValidations[$i] = $this->validateForm($this->values);
						$reload = true;
					}
					if(!$this->_stepValidations[$i])
					{
						$canFinish = false;
						break;
					}
				}

				if($reload)
				{
					$this->currentStepIndex = $currentstepIndex;
					$this->currentStepName = $this->_stepsKeys[$this->currentStepIndex];
					$this->getCurrentStepFields();
				}
			}
			else
				$canFinish = true;
		}

		return $canFinish;
		//return true;
	}

	public function isComplete(){
		return $this->completed;
	}

	public function complete(){
		if(!$this->validateForm()) return;

		$this->_stepValidations[$this->currentStepIndex] = true;

		if($this->canFinishAtThisStep()){
			$this->completed = true;
			$this->currentStepFields = array();
			$this->currentStepName = "";
			$this->currentStepIndex = $this->_count + 1;
		}else{
			throw new Exception("Cannot complete wizzard at this stage", 1);	
		}
	}

	public function getErrors(){
		return $this->_errors; 
	}

	public function getCurrentStepDisplayName(){
		return $this->_steps[$this->currentStepName];
	}
	
	public function hasErrors(){
		return count($this->_errors) > 0;
	}

	private function getCurrentStepFields()	{
		$this->currentStepFields = array();
		foreach ($this->_fields as $key=>$field)
			if($field['step'] == $this->currentStepName)
				$this->currentStepFields[$key] = $field;
	}

	public function getCurrentStepNumber(){
		return $this->currentStepIndex + 1;
	}

	public function getPrevButtonName(){return $this->prevBtnName;}
	public function getNextButtonName(){return $this->nextBtnName;}
	public function getFinishButtonName(){return $this->finBtnName;}

	public function setFieldTemplate($tmpl){$this->fieldTemplate = $tmpl;}
	public function getValues(){
		return $this->values;
	}

	public function setFieldValues(&$values=null){
		if(!$values) $values = $_POST;
		foreach ($this->_fields as $key=>$field) {
			if(isset($values[$key])){
				$this->_fields[$key]['default'] = $values[$key];
			}
		}
		$this->values = array_merge($this->values,$values);
		$this->saveToSession();
	}

	public function isNewWizard()
	{
		return !isset($_REQUEST['wizard_key']);
	}


	public function getFields()
	{
		return $this->_fields;
	}

	public function setFields($value)
	{
		$this->_fields = $value;
	}
}
