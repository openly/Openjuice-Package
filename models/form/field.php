<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
/*
 * Created on Jan 22, 2012
 *
 * jti - package_name
 * 
 * Author: abhilash
 * 
 * Description: 
 */
class Field{
	protected $label;
	protected $field;
	protected $fieldName;
	protected $hidden = false;
	
	public function getLabel(){return $this->label;}
	public function getField(){return $this->field;}
	public function isHidden(){return $this->hidden;}
}

class TextField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->text($fieldName,$default);
	}
}

class TextAreaField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->textarea($fieldName,$default);
	}
}

class RichTextareaField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->textarea($fieldName,$default,array('style' => 'width: 100%;', 'class' => 'ccm-advanced-editor'));
	}
}
class CheckBoxField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->checkbox($fieldName,1,$default);
	}
}

class SelectField extends Field{
	public function __construct($fieldName,$label,$default,$options){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		if(!is_array($options) && strlen($options) > 0){
			$dProvider = new $options();
			$options = $dProvider->getData();
		}
		$this->field = $form->select($fieldName,$options,$default);
	}
}

class PageField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$pageSelector = Loader::helper('form/page_selector');
		$this->field = $pageSelector->selectPage($fieldName, $default); 
	}
}

class ImageField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$ast = Loader::helper('concrete/asset_library');
		$this->field = $ast->image($fieldName, $fieldName, $this->label,$default? File::getByID($default):null) ;
	}
}

class FileField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$ast = Loader::helper('concrete/asset_library');
		$this->field = $ast->file($fieldName, $fieldName, $this->label,$default? File::getByID($default):null) ;
	}
}

class HiddenField extends Field{
	protected $hidden = true;
	
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->hidden($fieldName,$default);
	}
}
class HiddenWithDisplayField extends Field{
	public function __construct($fieldName,$label,$default){
		$this->fieldName = $fieldName;
		$this->label = $label;
		
		$form = Loader::helper('form');
		$this->field = $form->hidden($fieldName,$default) . t('<b>%s</b>',$default);
	}
}
