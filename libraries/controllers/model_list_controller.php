<?php
defined('C5_EXECUTE') or die("Access Denied.");

Loader::library('templating/renderable','openjuice');
Loader::library('util','openjuice');

class ModelListController extends Controller{
	protected $model = null;
	protected $fieldsToShow = array();
	protected $fieldsToSearch = array();
	protected $showDelete = true;
	protected $resultsPerPageArr = array('10','25','50','100');
	protected $resultsPerPage = '10';
	protected $showPagination = true;
	protected $paginationLabel = 'Results Per Page';
	protected $searchFormTemplate = '{{{formFields}}}';
	protected $footerTemplate = '{{#has_pagination}}<div class="pagination ccm-pagination"><ul><li class="prev"><a href="{{prev_url}}" title="Prev">&laquo; Prev</a></li>{{{page_list}}}<li class="next"><a href="{{next_url}}" title="Next">Next &raquo;</a></li></ul></div>{{/has_pagination}}';
	protected $_modelObj;
	protected $_fieldNameMapping = array(); // To retirve actual name after replacements for relations
	// eg: relation1.column will be replaced to relation1_column. So this replacement is saved here for ease access
	protected $_pagination;

	protected $searchFieldCustomizations = array(
		'template' => '<div class="span4"><label for="{{fieldPrefix}}{{fieldName}}">{{label}}</label><div class="input">{{{field}}}</div></div>',
		'fieldAttrs' => array('style'=>'width:140px;'),
		'type' => 'text'
	);

	protected $pageTitle = '';
	protected $pageDesc = '';


	protected $_list;

	public function on_start(){
		$this->addHeaderItem(Loader::helper('html')->javascript('dashboard_model_list.js','openjuice'));
	}

	public function __construct(){
		$this->cleanupConf();
		if($this->pageTitle == ''){
			$this->pageTitle = 'Search ' . ucfirst($this->model);
		}
		if($this->pageDesc == ''){
			$this->pageDesc = 'Search the ' . ucfirst($this->model) . ' of your site and perform bulk actions on them.';
		}
	}

	protected function cleanupConf(){
		$this->fieldsToShow = OJUtil::confArrToHash($this->fieldsToShow);
		$this->fieldsToSearch = OJUtil::confArrToHash($this->fieldsToSearch);
		$this->resultsPerPageArr = OJUtil::arrryToHash($this->resultsPerPageArr);
		foreach ($this->fieldsToShow as $key => &$customizations) {
			$newKey = str_replace('.', '_', $key);
			$this->_fieldNameMapping[$newKey] = $key;
			if($customizations['wrapper']){
				if(!is_array($customizations['wrapper']))
					$customizations['wrapper'] = array('name' => $customizations['wrapper']);
			}
		}
		foreach ($this->fieldsToSearch as $fieldName => &$customizations) {
			if(! $customizations['query']){
				if(!$customizations['type'] || $customizations['type'] == 'text')
					$customizations['query'] = "`$fieldName` like {{{$fieldName}}}";
				else
					$customizations['query'] = "`$fieldName`={{{$fieldName}}}";
			}
		}
	}

	public function view(){
		if($_GET['ajax']){
			$_SERVER['REQUEST_URI'] = str_replace('&ajax=true','',$_SERVER['REQUEST_URI']);
			$str = $this->renderList();
			echo '<div class="ccm-pane-body">' . $str . '</div>';
			echo '<div class="ccm-pane-footer">' . $this->getFooter() . '</div>';
			exit;
		}
	}

	public function delete($id=null){
		if($id!= null){
			$this->getModelObj()->delete($id);
			$this->set('message', get_class($this->getModelObj()) . ' Deleted.');
			$this->view();
		}
	}

	public function renderList($args = null,$ajax = true){ // Called render because it can also render...
		if($args==null) $args = $_GET;
		$listTemplate = file_get_contents(dirname(__FILE__) . '/../../assets/templates/dashboard_list.html');
		$list = $this->getResultList($args);

		$m = new Renderable($listTemplate);
		$resultList = $this->formatList($list->getPage());
		$pagination = $list->getPagination();
		$params = array(
			'headers' => $this->getListHeaders(),
			'list' => $resultList,
		);
		$this->_pagination = array(
			'page_list' => $pagination->getPages('li'),
			'no_pages' => $pagination->number_of_pages,
			'cur_page' => $pagination->current_page,
			'item_start' => $pagination->result_lower,
			'item_end' => $pagination->result_upper,
			'next_url' => $pagination->getNextURL(),
			'prev_url' => $pagination->getPreviousURL(),
			'has_pagination' => $pagination->number_of_pages > 1
		);
		$str = $m->getMarkup(array_merge($params,$this->_pagination));

		return $str;
	}
	public function getHeader(){
		$header = Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t($this->pageTitle), t($this->pageDesc), false, false);
		return $header;
	}

	public function getFooter(){
		$m = new Renderable($this->footerTemplate);
		return $m->getMarkup($this->_pagination);
	}

	public function renderFullDashboard(){
		$template = file_get_contents(dirname(__FILE__) . '/../../assets/templates/dashboard.html');
		$r = new SimpleRenderable($template);
		$vars = array();
		$vars['header'] = $this->getHeader();
		$vars['has_options'] = $this->hasSearch();
		$vars['options'] = $this->getSearchForm();
		$vars['main_content'] = $this->renderList($_GET,false);
		$vars['footer'] = $this->getFooter();
		echo $r->render($vars);
	}

	protected function getSearchForm(){
		$retstr = '<form action="" method="get" accept-charset="utf-8">';
		$frm = new OJForm($this->getSearchFields());
		$frm->setFormTemplate($this->searchFormTemplate);
		$retstr .= $frm->getMarkup();
		$retstr .= '<div class="span4"><input type="submit" class="btn" value="Search"><img height="11" width="43" id="ccm-search-loading" class="ccm-search-loading" src="/concrete/images/loader_intelligent_search.gif"></div>';
		$retstr .= '</form>';
		return $retstr;
	}

	protected function hasSearch(){
		return is_array($this->fieldsToSearch) && count($this->fieldsToSearch) > 0;
	}

	protected function getSearchFields(){
		if(!$this->hasSearch()) return array();
		$modelObj = $this->getModelObj();
		$mFields = $modelObj->getFields();
		$fields = array();
		foreach($this->fieldsToSearch as $fieldname => $customizations){
			$theField = array_merge($this->searchFieldCustomizations,$customizations);
			if($theField['type'] == 'dropdown'){
				$theField['type'] = 'distinctModelColoumnDropdown';
				$theField['model'] = $this->model;
				$theField['column'] = $fieldname;
			}
			$fields[$fieldname] = $theField;
		}
		if($this->showPagination){
			$fields['items_per_page'] = array_merge($this->searchFieldCustomizations, array(
					'label' => $this->paginationLabel,
					'type' => 'select',
					'values' => $this->resultsPerPageArr,
					'default' => $this->resultsPerPage
			));
		}
		return $fields;
	}

	protected function getModelObj(){
		if(!$this->_modelObj)
			$this->_modelObj = new $this->model();
		return $this->_modelObj;
	}

	protected function getListHeaders($args=null){
		if(!$args) $args = $_GET;
		$mfields = $this->getModelObj()->getFields();
		$headers = array();
		foreach ($this->fieldsToShow as $fieldName => $customizations) {
			$sortFieldname = str_replace('.', '_', $fieldName); // Make usre we are using right sql for sorting
			$thisColSorted = $args['sort_col'] == $sortFieldname;
			$thisSortDesc = $thisColSorted && ($args['sort_dir'] == 'asc');
			$sortDir = $thisSortDesc?'desc':'asc';
			$class = $thisColSorted? ('ccm-results-list-active-sort-' . $args['sort_dir']) : '';
			list($fName) = explode('.',$fieldName);
			$headers[] = array(
				'label' => OJUtil::thisOrThat($customizations,$mfields[$fName],'label'),
				'hasSort' => !($customizations['no_sort']),
				'sort_link' => OJUtil::queryLink($_GET,array('sort_col'=>$sortFieldname,'sort_dir'=> $sortDir)),
				'class' => $class
			);
		}
		if($this->showDelete){
			$headers[] = array('label'=>'','hasSort'=>false);
		}
		return $headers;
	}

	protected function getResultList($args=null){
		if($this->_list) return $this->_list;
		if($args == null) $args = $_GET;
		$modelObj = $this->getModelObj();
		$modelList = $modelObj->getList();
		$mFields = $modelObj->getFields();
		$modelList->setConvert(false);
		$modelList->setItemsPerPage($args['items_per_page']?$args['items_per_page']:$this->resultsPerPage);
		if($args['sort_col']){
			$modelList->sortBy($args['sort_col'],$args['sort_dir']);
		}

		foreach($this->fieldsToSearch as $fieldName => $customizations){
			if($args[$fieldName]){
				$modelList->addFilter($customizations['query']);
				if(!$customizations['type'] || $customizations['type'] == 'text')
					$args[$fieldName] = '%' . $args[$fieldName] . '%';
			}
		}
		$modelList->addArgs($args);

		$modelList->setFields(array_keys($this->fieldsToShow));
		$this->_list = $modelList;
		return $modelList;
	}

	protected function formatList($list){
		if(!is_array($list)) return array();
		$mFields = $this->getModelObj()->getFields();
		$rows = array();
		foreach($list as $origRows){
			$cols = array();
			foreach($origRows as $colname => $col){
				if($colname == '__id__') continue;
				$colKey = $this->_fieldNameMapping[$colname];
				if($this->fieldsToShow[$colKey]['wrapper']){
					$wrapper = $this->fieldsToShow[$colKey]['wrapper'];
					$col = call_user_func(array($this,$wrapper['name'] . 'Wrap'),$col,$origRows,$wrapper);
				}
				$cols[] = array('col' => $col);
			}
			if($this->showDelete){
				$col = '<a href="' . View::url($this->c->getCollectionPath()) . 'delete/' . $origRows['__id__'] . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</a>';
				$cols[] = array('col'=>$col);
			}
			$rows[] = array('cols' => $cols);
		}
		return array('rows'=>$rows);
	}

	protected function editWrap($col,$row,$wrap){
		return '<a href="' . $wrap['url'] . $row['__id__'] . '" title="' . $col . '">' . $col . '</a>';
	}

	protected function checkWrap($col,$row,$wrap){
		return ($col?'&#x2713;':'');
	}

}