<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('templating/renderable', 'openjuice');
Loader::library('util', 'openjuice');

/**
* ModelListController
*
* @uses     Controller
*
* @category Category
* @package  OpenJuice
* @author   Abhi
*/
class ModelListController extends Controller
{
    protected $model = null;
    protected $fieldsToShow = array();
    protected $fieldsToSearch = array();
    protected $showDelete = true;
    protected $resultsPerPageArr = array('10','25','50','100');
    protected $resultsPerPage = '10';
    protected $showPagination = true;
    protected $paginationLabel = 'Results Per Page';
    protected $searchFormTemplate = '{{{formFields}}}';
    protected $footerTemplate = '
    {{#has_pagination}}
    <div class="pagination ccm-pagination">
        <ul>
            <li class="prev">
                <a href="{{prev_url}}" title="Prev">&laquo; Prev</a>
            </li>
            {{{page_list}}}
            <li class="next">
                <a href="{{next_url}}" title="Next">Next &raquo;</a>
            </li>
        </ul>
    </div>
    {{/has_pagination}}';
    protected $modelObj;
    // To retrieve actual name after replacements for relations
    // eg: relation1.column will be replaced to relation1_column.
    // So this replacement is saved here for easy access
    protected $fieldNameMapping = array();
    protected $pagination;

    protected $searchFieldCustomizations = array(
        'template' => '<div class="span3">
            <label class="control-label" for="{{fieldPrefix}}{{fieldName}}">{{label}}</label>
            <div class="controls">{{{field}}}</div>
        </div>',
        'fieldAttrs' => array('style'=>'width:80px;'),
        'type' => 'text'
    );

    protected $pageTitle = '';
    protected $pageDesc = '';


    protected $list;

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
            $html->javascript('dashboard_model_list.js', 'openjuice')
        );
    }

    /**
     * __construct
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct()
    {
        $this->cleanupConf();
        if ($this->pageTitle == '') {
            $this->pageTitle = 'Search ' . ucfirst($this->model);
        }
        if ($this->pageDesc == '') {
            $this->pageDesc = 'Search the ' 
            . ucfirst($this->model) 
            . ' of your site and perform bulk actions on them.';
        }
    }

    /**
     * cleanupConf
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function cleanupConf()
    {
        $this->fieldsToShow = OJUtil::confArrToHash($this->fieldsToShow);
        $this->fieldsToSearch = OJUtil::confArrToHash($this->fieldsToSearch);
        $this->resultsPerPageArr = OJUtil::arrayToHash($this->resultsPerPageArr);
        foreach ($this->fieldsToShow as $key => &$customizations) {
            $newKey = str_replace('.', '_', $key);
            $this->fieldNameMapping[$newKey] = $key;
            if ($customizations['wrapper']) {
                if(!is_array($customizations['wrapper']))
                    $customizations['wrapper'] = array(
                        'name' => $customizations['wrapper']
                    );
            }
        }
        foreach ($this->fieldsToSearch as $fieldName => &$customizations) {
            if (! $customizations['query']) {
                if (!$customizations['type'] || $customizations['type'] == 'text') {
                    $customizations['query'] = "`$fieldName` like {{{$fieldName}}}";
                } else {
                    $customizations['query'] = "`$fieldName`={{{$fieldName}}}";
                }
            }
        }
    }

    /**
     * view
     * 
     * @access public
     *
     * @return mixed Value.
     */
    public function view()
    {
        if ($_GET['ajax']) {
            $_SERVER['REQUEST_URI'] = str_replace('&ajax=true', '', $_SERVER['REQUEST_URI']);
            $str = $this->renderList();
            echo '<div class="ccm-pane-body">' . $str . '</div>';
            echo '<div class="ccm-pane-footer">' . $this->getFooter() . '</div>';
            exit;
        }
    }

    /**
     * delete
     * 
     * @param mixed $id Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function delete($id=null)
    {
        if ($id!= null) {
            $this->getModelObj()->delete($id);
            $this->set('message', get_class($this->getModelObj()) . ' Deleted.');
            $this->view();
        }
    }

    /**
     * renderList
     * 
     * @param mixed $args Description.
     * @param mixed $ajax Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function renderList($args = null, $ajax = true)
    { // Called render because it can also render...
        if($args==null) $args = $_GET;
        $listTemplate = file_get_contents(
            dirname(__FILE__) . '/../../assets/templates/dashboard_list.html'
        );
        $list = $this->getResultList($args);

        $m = new Renderable($listTemplate);
        $resultList = $this->formatList($list->getPage());
        $pagination = $list->getPagination();
        $params = array(
            'headers' => $this->getListHeaders(),
            'list' => $resultList,
        );
        $this->pagination = array(
            'page_list' => $pagination->getPages('li'),
            'no_pages' => $pagination->number_of_pages,
            'cur_page' => $pagination->current_page,
            'item_start' => $pagination->result_lower,
            'item_end' => $pagination->result_upper,
            'next_url' => $pagination->getNextURL(),
            'prev_url' => $pagination->getPreviousURL(),
            'has_pagination' => $pagination->number_of_pages > 1
        );
        $str = $m->getMarkup(array_merge($params, $this->pagination));
        if (empty($resultList['rows'])) {
            $str .= $this->getEmptyResultMessage();
        }
        return $str;
    }

    /**
     * getEmptyResultMessage
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getEmptyResultMessage()
    {
        $str ='<div class="no-results"><p>No results</p></div>';
        return $str;
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
        $m = new Renderable($this->footerTemplate);
        return $m->getMarkup($this->pagination);
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
        $r = new SimpleRenderable($template);
        $vars = array();
        $vars['header'] = $this->getHeader();
        $vars['has_options'] = $this->hasSearch();
        $vars['options'] = $this->getSearchForm();
        $vars['main_content'] = $this->renderList($_GET, false);
        $vars['footer'] = $this->getFooter();
        echo $r->render($vars);
    }

    /**
     * getSearchForm
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getSearchForm()
    {
        $retstr = '<form action="" method="get" class="form-horizontal" accept-charset="utf-8">
            <div class="ccm-pane-options-permanent-search">';
        $frm = new OJForm($this->getSearchFields());
        $frm->setFormTemplate($this->searchFormTemplate);
        $retstr .= $frm->getMarkup();
        $retstr .= '<div class="span2">
            <input type="submit" class="btn" value="Search">
            <img height="11" width="43" id="ccm-search-loading" class="ccm-search-loading" src="/concrete/images/loader_intelligent_search.gif">
        </div>';
        $retstr .= '</div></form>';
        return $retstr;
    }

    /**
     * hasSearch
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function hasSearch()
    {
        return is_array($this->fieldsToSearch) && count($this->fieldsToSearch) > 0;
    }

    /**
     * getSearchFields
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getSearchFields()
    {
        if(!$this->hasSearch()) return array();
        $modelObj = $this->getModelObj();
        $mFields = $modelObj->getFields();
        $fields = array();
        foreach ($this->fieldsToSearch as $fieldname => $customizations) {
            $theField = array_merge($this->searchFieldCustomizations, $customizations);
            if ($theField['type'] == 'dropdown') {
                $theField['type'] = 'distinctModelColoumnDropdown';
                $theField['model'] = $this->model;
                $theField['column'] = $fieldname;
            }
            $fields[$fieldname] = $theField;
        }
        if ($this->showPagination) {
            $fields['items_per_page'] = array_merge(
                $this->searchFieldCustomizations, 
                array(
                    'label' => $this->paginationLabel,
                    'type' => 'select',
                    'values' => $this->resultsPerPageArr,
                    'default' => $this->resultsPerPage
                )
            );
        }
        return $fields;
    }

    /**
     * getModelObj
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getModelObj()
    {
        if(!$this->modelObj)
            $this->modelObj = new $this->model();
        return $this->modelObj;
    }

    /**
     * getListHeaders
     * 
     * @param mixed $args Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getListHeaders($args=null)
    {
        if(!$args) $args = $_GET;
        $mfields = $this->getModelObj()->getFields();
        $headers = array();
        foreach ($this->fieldsToShow as $fieldName => $customizations) {
            // Make usre we are using right sql for sorting
            $sortFieldname = str_replace('.', '_', $fieldName); 
            $thisColSorted = $args['sort_col'] == $sortFieldname;
            $thisSortDesc = $thisColSorted && ($args['sort_dir'] == 'desc');
            $sortDir = $thisSortDesc?'asc':'desc';
            $class = $thisColSorted? ('ccm-results-list-active-sort-' . $args['sort_dir']) : '';
            list($fName) = explode('.', $fieldName);
            $headers[] = array(
                'label' => OJUtil::thisOrThat(
                    $customizations,
                    $mfields[$fName],
                    'label'
                ),
                'hasSort' => !($customizations['no_sort']),
                'sort_link' => OJUtil::queryLink(
                    $_GET,
                    array(
                        'sort_col'=>$sortFieldname,
                        'sort_dir'=> $sortDir
                    )
                ),
                'class' => $class
            );
        }
        if ($this->showDelete) {
            $headers[] = array('label'=>'','hasSort'=>false);
        }
        return $headers;
    }

    /**
     * getResultList
     * 
     * @param mixed $args Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getResultList($args=null)
    {
        if($this->list) return $this->list;
        if($args == null) $args = $_GET;
        $modelObj = $this->getModelObj();
        $modelList = $modelObj->getList();
        $mFields = $modelObj->getFields();
        $modelList->setConvert(false);
        $modelList->setItemsPerPage(
            $args['items_per_page'] ?
            $args['items_per_page'] : $this->resultsPerPage
        );
        if ($args['sort_col']) {
            $modelList->sortBy($args['sort_col'], $args['sort_dir']);
        }

        foreach ($this->fieldsToSearch as $fieldName => $customizations) {
            if ($args[$fieldName]) {
                $modelList->addFilter($customizations['query']);
                if (!$customizations['type'] || $customizations['type'] == 'text') {
                    $args[$fieldName] = '%' . $args[$fieldName] . '%';
                }
            }
        }
        $modelList->addArgs($args);

        $modelList->setFields(array_keys($this->fieldsToShow));
        $this->list = $modelList;
        return $modelList;
    }

    /**
     * formatList
     * 
     * @param mixed $list Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function formatList($list)
    {
        if(!is_array($list)) return array();
        $mFields = $this->getModelObj()->getFields();
        $rows = array();
        foreach ($list as $origRows) {
            $cols = array();
            foreach ($origRows as $colname => $col) {
                if($colname == '__id__') continue;
                $colKey = $this->fieldNameMapping[$colname];
                if ($this->fieldsToShow[$colKey]['wrapper']) {
                    $wrapper = $this->fieldsToShow[$colKey]['wrapper'];
                    $col = call_user_func(
                        array($this, $wrapper['name'] . 'Wrap'),
                        $col,
                        $origRows,
                        $wrapper
                    );
                }
                $cols[] = array('col' => $col);
            }
            if ($this->showDelete) {
                $col = '<a href="' . View::url($this->c->getCollectionPath()) . 'delete/' . $origRows['__id__'] . '"
                 title="Delete" onclick="return confirm(\'Are you sure you want to delete this item?\')">
                 Delete
                 </a>';
                $cols[] = array('col'=>$col);
            }
            $rows[] = array('cols' => $cols);
        }
        return array('rows'=>$rows);
    }

    /**
     * editWrap
     * 
     * @param mixed $col  Description.
     * @param mixed $row  Description.
     * @param mixed $wrap Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function editWrap($col,$row,$wrap)
    {
        return '<a href="' . $wrap['url'] . $row['__id__'] . '" title="' . $col . '">' . $col . '</a>';
    }

    /**
     * checkWrap
     * 
     * @param mixed $col  Description.
     * @param mixed $row  Description.
     * @param mixed $wrap Description.
     *
     * @access protected
     *
     * @return mixed Value.
     */
    protected function checkWrap($col,$row,$wrap)
    {
        return ($col?'&#x2713;':'');
    }
}