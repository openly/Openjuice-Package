<?php
interface Converter{
	public function convert($val);
}

class PageConverter implements Converter{
	public function convert($val){
		if($val){
			$page = Page::getByID($val);
			if($page->cID){
				return t('<a href="%s" target="_blank">%s</a>',$page->getCollectionPath(),$page->getCollectionName());
			}
		}
		return null;
	}
}

class ThumbConverter implements Converter{
	private $w;
	private $h;
	private $crop;
	
	public function __construct($arr){
		$this->h = $arr[0]?$arr[0]:100;
		$this->w = $arr[1]?$arr[1]:100;
		$this->crop = $arr[2]?($arr[2]=='true'):true;
	}
	
	public function convert($val){
		if($val){
			$file = File::getByID($val);
			if(!$file->error){
				$im = Loader::helper('image');
				return t('<a href="%s" target="_blank"><img src="%s" /></a>',$file->getRelativePath(),$im->getThumbNail($file,$this->w,$this->h,$this->crop)->src);
			}
		}
	}
}

class HasManyConverter implements Converter{	
	private $tableName = '';
	private $joinCol = '';
	private $colName = 'name';
	private $editUrl = "";
	private $deleteUrl = "";
	
	public function __construct($arr){
		$this->tableName = $arr[0]?$arr[0]:'name';
		$this->joinCol = $arr[1]?$arr[1]:' ';
		$this->colName = $arr[2]?$arr[2]:'';
		$this->editUrl = $arr[3]?$arr[3]:' ';
		$this->deleteUrl = $arr[4]?$arr[4]:'';
		
	}
	
	public function convert($val){
		$editLink = "<a href='%s'>Edit</a>";
		$deleteLink = "<a href='%s'>Delete</a>";
		$listItem = "<li>%s - $editLink $deleteLink</li>";
		if($val){
			$db = Loader::db();
			$rows = $db->getAll("SELECT ID,". $this->colName ." as name FROM " . $this->tableName ." WHERE " . $this->joinCol ."=?",array($val));
			$retval = "<ul class=\"unstyled\">";
			foreach($rows as $row){
				$retval .= t($listItem,$row['name'],View::url($this->editUrl,$val,$row['ID']),View::url($this->deleteUrl,$row['ID']));
			}
			$retval .= t("</ul><a href='%s'>Add New</a>",View::url($this->editUrl,$val));
			return $retval;
		}
		return "ERROR";
	}
}
