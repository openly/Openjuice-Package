<?php
require_once(dirname(__FILE__) . '/mustache.php');

class Renderable extends Mustache{

	protected $fieldsToRender = array();
	protected $template;

	public function __construct($template=null){
		$this->template = $template;
	}

	protected function getTemplateVars(){
		$retval = array();
		foreach ($this->fieldsToRender as $field) {
			$retval[$field] = $this->{$field};
		}
		return $retval;
	}

	public function getMarkup(&$args = null){
		if(!is_array($args)){
			$args = $this->getTemplateVars();
		}
		$m = new Mustache;
		return $m->render($this->template, $args);
	}

	public function render(&$args = null){
		echo $this->getMarkup($args);
	}

	public function setTemplate($str){
		$this->template = $str;
	}
}

class SimpleRenderable extends Renderable{
	public function __construct($template=null){
		$this->template = $template;
	}
}
