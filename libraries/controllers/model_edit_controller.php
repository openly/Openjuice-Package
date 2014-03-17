<?php 
require_once __DIR__ . '/../form/form.php';

/**
* ModelEditController
*
* @uses     Controller
*
* @category Category
* @package  Package
* @author   Abhi
*/
class ModelEditController extends Controller
{

    protected $form  = null;
    protected $model  = null;
    protected $errors = null;
    protected $modelName = '';
    protected $pageTitle = '';
    protected $pageDesc = '';
    protected $modelFile = '';// Required for AJAX Rendering
    protected $modelPackage = '';

    protected $listURL = null;

    protected $ajaxFieldReloads = array();
    protected $ajaxFieldGroupReloads = array();

    protected $fieldTemplate = '<div class="clearfix" {{parentAttrs}}>
    <label for="{{fieldPrefix}}{{fieldName}}">{{label}}</label>
    <div class="input input-xxlarge">{{{field}}}</div>
    </div>';

    protected $footerTemplate = '
    <a href="{{listURL}}" title="Cancel" class="btn error">Cancel</a>
    {{#has_finish_btn}}
    <input type="submit" name="{{finish_btn_name}}" value="{{finish_btn_name}}" class="btn ccm-button-v2-right primary">
    {{/has_finish_btn}}
    {{#has_next_btn}}
    <input type="submit" name="{{next_btn_name}}" value="{{next_btn_name}}" class="btn ccm-button-v2-right">
    {{/has_next_btn}}
    {{#has_prev_btn}}
    <input type="submit" name="{{prev_btn_name}}" value="{{prev_btn_name}}" class="btn ccm-button-v2-right">
    {{/has_prev_btn}}';

    /**
    * __construct
    * 
    * @access public
    *
    * @return mixed Value.
    */
    public function __construct()
    {
        $this->pageTitle = '';
    }

    /**
    * on_start
    * 
    * @access public
    *
    * @return mixed Value.
    */
    public function on_start()
    {
        $html = Loader::helper('html');
        $this->addHeaderItem(
            $html->javascript('dashboard_model_edit.js', 'openjuice')
        );
        $uh = Loader::helper('concrete/urls');
        $changeStrs = '$(function(){';
        foreach ($this->ajaxFieldReloads as $field => $reloadField) {
            if (is_array($reloadField)) {
                foreach ($reloadField as $theReloadField) {
                    $changeStrs .= "$('#$field').change(function(){
                        refreshField('$theReloadField',$(this).closest('form'))
                    });\n";
                }
            } else {
                $changeStrs .= "$('#$field').change(function(){
                    refreshField('$reloadField',$(this).closest('form'))
                });\n";  
            }
        }
        foreach ($this->ajaxFieldGroupReloads as $field => $reloadFieldGroup) {
            $changeStrs .= "$('#$field').change(function(){
                refreshFieldGroup('$reloadFieldGroup',$(this).closest('form'))
            });\n";
        }
        $changeStrs .= '});';
        $this->addHeaderItem(
            '<script type="text/javascript">
            window.renderFieldUrl="' . View::url($this->c->getCollectionPath() . '/refreshField') . '";
            window.renderFieldGroupUrl="' . View::url($this->c->getCollectionPath() . '/refreshFieldGroup') . '";'
            . $changeStrs . 
            '</script>'
        );
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
                $modelObj->getForm()->getForm()
                    ->renderFieldGroup($_REQUEST['field_grp'], $_POST);
            } else {
                $modelObj->getForm()
                    ->renderFieldGroup($_REQUEST['field_grp'], $_POST);
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
        $this->model = new $this->modelName();
        $this->_init($this->model);
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
    public function load($id=null)
    {
        $this->model = new $this->modelName($id);
        $this->_init($this->model);
    }

    /**
    * _init
    * 
    * @param mixed $model Description.
    *
    * @access private
    *
    * @return mixed Value.
    */
    private function _init($model)
    {
        $this->form = $this->getForm($model);
        
        if ($this->isPost()) {
            $processRes = $model->process($this->post());
            
            if ($processRes) {
                //To Do: Status code correction
                $status = $model->getStatus();
                //To Do: good mechanism for list url and edit url
                if (($this->listURL != null)
                    && ($status == 'Added' || $status == 'Updated')
                ) {
                    //To Do: set success messsage
                    $this->redirect($this->listURL);
                }
            }
        }
    }

    /**
     * getForm
     * 
     * @param mixed &$model Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    protected function getForm(&$model)
    {
        if ($this->form == null) {
            
            $this->form = $model->useWizzard ?
                $this->_getOJWizard($model):
                $this->_getOJForm($model);
            
            $this->form->setFieldTemplate($this->fieldTemplate);
        }

        $this->form->setFieldValues($model->getValues());

        return $this->form;
    }

    /**
     * _getOJForm
     * 
     * @param mixed &$model Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _getOJForm(&$model)
    {
        return new OJForm(
            $model->fields,
            $model->formMultiFieldValidations,
            $model->formRules,
            $model->fieldGroups,
            $model->values
        );
    }

    /**
     * _getWizard
     * 
     * @param mixed &$model Description.
     *
     * @access private
     *
     * @return mixed Value.
     */
    private function _getOJWizard(&$model)
    {
        if (count($model->steps) > 0) {
            $wiz = OJWizard::getWizard(
                $model->fields,
                $model->steps,
                $model->formMultiFieldValidations,
                $model->formRules,
                $model->fieldGroups,
                $model->values
            );
            return $wiz;
        }
        //To DO: Throw Exception
        return null;
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
        $template = file_get_contents(
            dirname(__FILE__) . '/../../assets/templates/dashboard.html'
        );
        $r = new Renderable($template);
        $vars = array();
        $vars['header'] = $this->getHeader();
        $vars['errors'] = $this->_getErrors();
        $vars['main_content'] = $this->_getDetailForm();
        $vars['has_options'] = $this->model->usesWizzard();
        if ($this->model->usesWizzard()) {
            $vars['options'] = '<h3><i>Step ' . 
                $this->form->getCurrentStepNumber() . ': </i> ' . 
                $this->form->getCurrentStepDisplayName() . '</h3>';
        }
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
    private function _getDetailForm()
    {
        $retstr = "<form action='' method='post' enctype='multipart/form-data'>";
        $retstr .= $this->form->getMarkup();
        return $retstr;
    }

    /**
     * _getErrors
     * 
     * @access private
     *
     * @return mixed Value.
     */
    private function _getErrors()
    {
        $errors = OJUtil::getErrorsList(
            $this->model->getErrors()
        );
        $html = '';
        if (!empty($errors)) {
            $html = '<ul class="error">';
            foreach ($errors as $error) {
                $html .= "<li>$error</li>";
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /**
    * getHeader
    * 
    * @access public
    *
    * @return mixed Value.
    */
    public function getHeader()
    {
        $header = Loader::helper('concrete/dashboard')
        ->getDashboardPaneHeaderWrapper(
            t($this->pageTitle),
            t($this->pageDesc),
            false,
            false
        );
        return $header;
    }

    /**
    * getFooter
    * 
    * @access public
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