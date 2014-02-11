<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('form/form', 'openjuice');

/**
* OJWizard
*
* @uses     Renderable
*
* @category Category
* @package  Package
* @author   <>
*/
class OJWizard extends Renderable
{
    
    protected $steps = array();
    protected $fields = array();
    protected $currentStepName = null;
    protected $currentStepFields = array();
    protected $count = 0;
    protected $currentStepIndex = 0;
    protected $sessionKey = null;
    protected $values = array();
    protected $errors = array();
    protected $completed = false;
    protected $showUnSteppedFields = true;
    protected $formMultiFieldValidations = array();
    protected $formRules = null;
    protected $fieldGroups = null;
    protected $fieldTemplate = null;

    private $_stepValidations = null;

    protected $stepsKeys =array();

    protected $prevBtnName = "Previous";
    protected $nextBtnName = "Next";
    protected $finBtnName = "Finish";

    protected $template = "{{{formFields}}";

    protected $completeTemplate = "<h2>Wizard Completed....Data saved to the DB.</h2>";

    /**
     * __construct
     * 
     * @param mixed &$fields                    Description.
     * @param mixed &$steps                     Description.
     * @param mixed &$formMultiFieldValidations Description.
     * @param mixed &$formRules                 Description.
     * @param mixed &$fieldGroups               Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct(&$fields,
        &$steps,
        &$formMultiFieldValidations = null,
        &$formRules = null,
        &$fieldGroups = null
    ) {
        $this->fields = $fields;
        $this->formMultiFieldValidations = $formMultiFieldValidations;
        $this->formRules = $formRules;
        $this->fieldGroups = $fieldGroups;
        
        foreach ($this->fields as $key=>$field) {
            if (isset($field["show_in_form"])
                && !$field["show_in_form"]
            ) {
                unset($this->fields[$key]);
            } elseif (!isset($field["step"])) {
                $this->fields[$key]["step"] = "NoStepName";
            }
        }
                
        $this->steps = $steps;
        if ($this->showUnSteppedFields) {
            $this->currentStepName = "NoStepName";
            $this->_getCurrentStepFields();
            if (count($this->currentStepFields) > 0) {
                $this->steps["NoStepName"] = "No Step Name";
            }
        }
        
        $this->count = count($this->steps);
        $this->stepsKeys = array_keys($this->steps);
        
        $this->currentStepName = $this->stepsKeys[0];
        $this->currentStepIndex = 0;
        
        $this->sessionKey = get_class($this) . date('Ymdhis');
        $this->_saveToSession();
    }
    

    /**
     * getWizard
     * 
     * @param mixed &$fields                    Description.
     * @param mixed &$steps                     Description.
     * @param mixed &$formMultiFieldValidations Description.
     * @param mixed &$formRules                 Description.
     * @param mixed &$fieldGroups               Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getWizard(&$fields,
        &$steps,
        &$formMultiFieldValidations,
        &$formRules,
        &$fieldGroups
    ) {
        session_start();
        if ($_REQUEST['wizard_key']) {
            $key = $_REQUEST['wizard_key'];
            return unserialize($_SESSION[$key]);
        }
        return new OJWizard(
            $fields,
            $steps,
            $formMultiFieldValidations,
            $formRules,
            $fieldGroups
        );
    }

    /**
     * process
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function process(&$args = null)
    {
        if (!$args) {
            $args = $_POST;
        }
        $this->values = array_merge(
            $this->values,
            $this->getForm()->getFieldValues()
        );
        //$this->values = array_merge($this->values,$args);

        if (isset($args[$this->nextBtnName])) {
            $this->gotoNextStep();
        } else if (isset($args[$this->prevBtnName])) {
            $this->gotoPreviousStep();
        } else if (isset($args[$this->finBtnName])) {
            $this->complete();
        } else {
            echo "Invalid Button";
        }
    }

    /**
     * validateForm
     * 
     * @param mixed $args Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _validateForm($args =null)
    {
        if ($args == null) {
            $args = $_POST;
        }
        $retValue = true;
        $form = $this->getForm();
        if (!$form->validate($args)) {
            $this->errors = $form->getErrors();
            $this->_stepValidations[$this->currentStepIndex] = false;
            $retValue = false;
        }

        return $retValue;
    }

    /**
     * gotoNextStep
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function gotoNextStep()
    {     
        //validate the current form Fields and then get the next step fields
        if (!$this->_validateForm()) {
            return;
        }

        $this->_stepValidations[$this->currentStepIndex] = true;
        $this->currentStepIndex++;

        if ($this->currentStepIndex == $this->count) {
            $this->completed = true;
            $this->currentStepFields = array();
            return;
        }

        $this->currentStepName = $this->stepsKeys[$this->currentStepIndex];

        $this->_saveToSession();
    }

    /**
     * gotoPreviousStep
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function gotoPreviousStep()
    {
        if ($args == null) {
            $args = $_POST;
        }
        
        $form = $this->getForm();
        if (!$form->validate($args)) {
            unset($this->_stepValidaions[$this->currentStepIndex]);
        }
        
        $this->currentStepIndex--;
        if ($this->currentStepIndex == -1) {
            $this->currentStepIndex = 0;
        }
        $this->currentStepName = $this->stepsKeys[$this->currentStepIndex];
        $this->_saveToSession();     
    }

    /**
     * saveToSession
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _saveToSession()
    {
        $_SESSION[$this->sessionKey] = serialize($this);
    }

    /**
     * reset
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function reset()
    {
        $this->currentStepName =$steps[0];
        $this->currentStepIndex = 0;
    }


    /**
     * getMarkup
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getMarkup($args=null)
    {
        $retstr = t(
            '<input type="hidden" name="%s" value="%s">',
            'wizard_key',
            $this->sessionKey
        );

        $m = new Mustache;
        
        if ($this->completed) {
            $retstr .= $m->render($this->completeTemplate);
        } else {
            $form = $this->getForm();
            $form->setFieldValues($this->values);
            $formFields = $form->getMarkup($this->values);

            //To Do: Call these methods and check in Mustache itself
            if ($this->hasPrevStep()) {
                $previousButton = array("prevBtnName"=>$this->prevBtnName);
            }

            if ($this->hasNextStep()) {
                $nextButton = array("nextBtnName"=>$this->nextBtnName);
            }

            if ($this->canFinishAtThisStep()) {
                $finButton = array("finBtnName"=>$this->finBtnName);
            }

            $data = array(
                "currentStepName" => $this->getCurrentStepDisplayName(),
                "currentStepNumber" => $this->currentStepIndex + 1,
                "TotalNumberOfSteps" => $this->count,
                "prevBtnExists"=>$previousButton,
                "nextBtnExists"=>$nextButton,
                "finBtnExists"=>$finButton,
                "formFields"=>$formFields
            );

            $retstr .= $m->render($this->template, $data);
        }
        return $retstr;
    }

    /**
     * render
     * 
     * @param mixed $args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function render($args = null)
    {
        echo $this->getMarkup($args);
    }

    /**
     * renderField
     * 
     * @param mixed $field Description.
     * @param mixed $args  Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function renderField($field,$args=null)
    {
        $this->getForm()->renderField($field, $args);
    }

    /**
     * getForm
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getForm()
    {
        $this->_getCurrentStepFields();
        $frm = new OJForm(
            $this->currentStepFields,
            $this->formMultiFieldValidations[$this->currentStepName],
            $this->formRules,
            $this->fieldGroups,
            $this->values
        );

        if ($this->fieldTemplate != null) {
            $frm->setFieldTemplate($this->fieldTemplate);
        }
        return $frm;
    }

    /**
     * hasPrevStep
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function hasPrevStep()
    {
        return !$this->completed && $this->currentStepIndex > 0; 
    }

    /**
     * hasNextStep
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function hasNextStep()
    {
        return !$this->completed
         && ($this->currentStepIndex < ($this->count - 1));
    }

    /**
     * canFinishAtThisStep
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function canFinishAtThisStep()
    {
        //Need to code here to check whether 
        //it can finish the wizard at this time
        $canFinish = false;
        if (!$this->completed) {
            $canFinish = true;
            $currentstepIndex = $this->currentStepIndex;
            $reload = false;

            if ($this->hasNextStep()) {
                for ($i = 0; $i<$this->count;$i++) {
                    if (!isset($this->_stepValidations[$i])) {
                        $this->currentStepIndex = $i;
                        $this->currentStepName = $this->stepsKeys[$this->currentStepIndex];
                        $this->_getCurrentStepFields();
                        $this->_stepValidations[$i] = $this->_validateForm($this->values);
                        $reload = true;
                    }
                    if (!$this->_stepValidations[$i]) {
                        $canFinish = false;
                        break;
                    }
                }

                if ($reload) {
                    $this->currentStepIndex = $currentstepIndex;
                    $this->currentStepName = $this->stepsKeys[$this->currentStepIndex];
                    $this->_getCurrentStepFields();
                }
            } else {
                $canFinish = true;
            }
        }

        return $canFinish;
    }

    /**
     * isComplete
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function isComplete()
    {
        return $this->completed;
    }

    /**
     * complete
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function complete()
    {
        if (!$this->_validateForm()) {
            return;
        }

        $this->_stepValidations[$this->currentStepIndex] = true;

        if ($this->canFinishAtThisStep()) {
            $this->completed = true;
            $this->currentStepFields = array();
            $this->currentStepName = "";
            $this->currentStepIndex = $this->count + 1;
        } else {
            throw new Exception("Cannot complete wizzard at this stage", 1);    
        }
    }

    /**
     * getErrors
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getErrors()
    {
        return $this->errors; 
    }

    /**
     * getCurrentStepDisplayName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getCurrentStepDisplayName()
    {
        return $this->steps[$this->currentStepName];
    }
    

    /**
     * hasErrors
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * _getCurrentStepFields
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _getCurrentStepFields()
    {
        $this->currentStepFields = array();
        foreach ($this->fields as $key=>$field)
            if($field['step'] == $this->currentStepName)
                $this->currentStepFields[$key] = $field;
    }

    /**
     * getCurrentStepNumber
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getCurrentStepNumber()
    {
        return $this->currentStepIndex + 1;
    }

    /**
     * getPrevButtonName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getPrevButtonName()
    {
        return $this->prevBtnName;
    }

    /**
     * getNextButtonName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getNextButtonName()
    {
        return $this->nextBtnName;
    }

    /**
     * getFinishButtonName
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getFinishButtonName()
    {
        return $this->finBtnName;
    }

    /**
     * setFieldTemplate
     * 
     * @param mixed $tmpl Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldTemplate($tmpl)
    {
        $this->fieldTemplate = $tmpl;
    }

    /**
     * getValues
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * setFieldValues
     * 
     * @param mixed &$values Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFieldValues(&$values=null)
    {
        if (!$values) {
            $values = $_POST;
        }
        foreach ($this->fields as $key=>$field) {
            if (isset($values[$key])) {
                $this->fields[$key]['default'] = $values[$key];
            }
        }
        $this->values = array_merge($this->values, $values);
        $this->_saveToSession();
    }

    /**
     * isNewWizard
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function isNewWizard()
    {
        return !isset($_REQUEST['wizard_key']);
    }


    /**
     * getFields
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * setFields
     * 
     * @param mixed $value Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setFields($value)
    {
        $this->fields = $value;
    }
}
