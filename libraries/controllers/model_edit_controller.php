<?php 

/**
* ModelEditController
*
* @uses     Controller
*
* @category Category
* @package  Openjuice
* @author   
* @license  
* @link     
*/
class ModelEditController extends Controller
{

    protected $form  = null;
    protected $model  = null;
    protected $errors = null;
    protected $modelName = '';
    protected $modelLabel = '';
    protected $pageTitle = '';
    protected $pageDesc = '';
    protected $modelFile = '';// Required for AJAX Rendering
    protected $modelPackage = '';

    protected $listURL = null;

    protected $ajaxFieldReloads = array();
    protected $ajaxFieldGroupReloads = array();

    protected $fieldTemplate = '<div class="clearfix" {{parentAttrs}}><label for="{{fieldPrefix}}{{fieldName}}">{{label}}</label><div class="input input-xxlarge">{{{field}}}</div></div>';

    protected $footerTemplate = '
    <a href="{{listURL}}" title="Cancel" class="btn error">Cancel</a>
    {{#has_finish_btn}}<input type="submit" name="{{finish_btn_name}}" value="{{finish_btn_name}}" class="btn ccm-button-v2-right primary">{{/has_finish_btn}}
    {{#has_next_btn}}<input type="submit" name="{{next_btn_name}}" value="{{next_btn_name}}" class="btn ccm-button-v2-right">{{/has_next_btn}}
    {{#has_prev_btn}}<input type="submit" name="{{prev_btn_name}}" value="{{prev_btn_name}}" class="btn ccm-button-v2-right">{{/has_prev_btn}}';

    /**
     * on_start
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function on_start()
    {
        $this->addHeaderItem(Loader::helper('html')->javascript('dashboard_model_edit.js', 'openjuice'));
        $uh = Loader::helper('concrete/urls');
        $changeStrs = '$(function(){';
        foreach ($this->ajaxFieldReloads as $field => $reloadField) {
            if (is_array($reloadField)) {
                foreach ($reloadField as $theReloadField) {
                    $changeStrs .= "$('#$field').change(function(){refreshField('$theReloadField',$(this).closest('form'))});\n";
                }
            } else {
                $changeStrs .= "$('#$field').change(function(){refreshField('$reloadField',$(this).closest('form'))});\n";  
            }
        }
        foreach ($this->ajaxFieldGroupReloads as $field => $reloadFieldGroup) { 
            $changeStrs .= "$('#$field').change(function(){refreshFieldGroup('$reloadFieldGroup',$(this).closest('form'))});\n";
        }
        $changeStrs .= '});';
        $this->addHeaderItem('<script type="text/javascript">window.renderFieldUrl="' . View::url($this->c->getCollectionPath() . '/refreshField') . '";window.renderFieldGroupUrl="' . View::url($this->c->getCollectionPath() . '/refreshFieldGroup') . '";' . $changeStrs . '</script>');
    }

    /**
     * refreshField
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function refreshField()
    {
        if ($_REQUEST['field']) {
            $modelObj = new $this->modelName();
            $modelObj->getForm()->setFieldTemplate($this->fieldTemplate);
            if ($modelObj->usesWizzard()) {
                $modelObj->getForm()->getForm()->renderField($_REQUEST['field'], $_POST);
            } else {
                $modelObj->getForm()->renderField($_REQUEST['field'], $_POST);
            }
        }
        exit;
    }

    /**
     * refreshFieldGroup
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function refreshFieldGroup()
    {
        if ($_REQUEST['field_grp']) {
            $modelObj = new $this->modelName();
            $modelObj->getForm()->setFieldTemplate($this->fieldTemplate);
            if ($modelObj->usesWizzard()) {
                $modelObj->getForm()->getForm()->renderFieldGroup($_REQUEST['field_grp'], $_POST);
            } else {
                $modelObj->getForm()->renderFieldGroup($_REQUEST['field_grp'], $_POST);
            }
        }
        exit;
    }

    /**
     * view
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function view()
    {   
        if ($this->modelLabel) {
            $this->pageTitle = 'Add ' . $this->modelLabel;
        }
        $this->model = new $this->modelName();
        $this->init($this->model);
    }

    /**
     * load
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function load($id)
    {
        if ($this->modelLabel) {
            $this->pageTitle = 'Update ' . $this->modelLabel;
        }
        $this->model = new $this->modelName($id);
        $this->init($this->model);
    }

    /**
     * init
     * 
     * @param mixed $model Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function init($model)
    {
        if ($this->isPost()) {
            $model->process();
            if ($model->getForm()->hasErrors()) {
                $this->set('error', OJUtil::getErrorsList($model->getForm()->getErrors()));
            } else {
                $status = $model->getStatus();
                if (($this->listURL != null) && ($status == 'Added' || $status == 'Updated')) {
                    $this->redirect($this->listURL);
                }
            }
        }
        $this->form = $model->getForm();
        $this->form->setFieldTemplate($this->fieldTemplate);
    }

    /**
     * renderFullDashboard
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function renderFullDashboard()
    {
        $template = file_get_contents(dirname(__FILE__) . '/../../assets/templates/dashboard.html');
        $r = new Renderable($template);
        $vars = array();
        $vars['header'] = $this->getHeader();
        $vars['main_content'] = $this->getDetailForm();
        $vars['has_options'] = $this->model->usesWizzard();
        if($this->model->usesWizzard())
            $vars['options'] = '<h3><i>Step ' . $this->form->getCurrentStepNumber() . ': </i> ' . $this->form->getCurrentStepDisplayName() . '</h3>';
        $vars['footer'] = $this->getFooter();
        echo $r->render($vars);
    }

    /**
     * getDetailForm
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function getDetailForm()
    {
        $retstr = '<form action="" method="post" enctype="multipart/form-data">';
        $retstr .= $this->form->getMarkup();
        return $retstr;
    }

    /**
     * getDetailForm
     * 
     * @access private
     *
     * @return mixed Value.
     */
    public function getHeader()
    {
        $header = Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t($this->pageTitle), t($this->pageDesc), false, false);
        return $header;
    }

    /**
     * getDetailForm
     * 
     * @access private
     *
     * @return mixed Value.
     */
    public function getFooter()
    {
        $r = new Renderable($this->footerTemplate);
        $vars = array(
            'is_wizzard'=>$this->model->usesWizzard(),
            'is_form' => !($this->model->usesWizzard())
        );
        
        //Mustache is logicless templating...
        if ($vars['is_wizzard']) {
            $vars['has_prev_btn'] = $this->form->hasPrevStep();
            $vars['has_next_btn'] = $this->form->hasNextStep();
            $vars['has_finish_btn'] = $this->form->canFinishAtThisStep();
            $vars['prev_btn_name'] = $this->form->getPrevButtonName();
            $vars['next_btn_name'] = $this->form->getNextButtonName();
            $vars['finish_btn_name'] = $this->form->getFinishButtonName();
        } else {
            $vars['has_finish_btn'] = true;
            $vars['finish_btn_name'] = $this->form->getSubmitButtonName();
        }

        $vars['listURL'] = view::url($this->listURL);
        $retStr = $r->getMarkup($vars);
        return $retStr . '</form>';
    }
}