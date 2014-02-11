<?php

/**
* DataList
*
* @uses     ItemList
*
* @category Category
* @package  Package
* @author   Abhi
*/
class DataList extends ItemList
{

    protected $model;
    protected $fields;
    protected $meta;
    protected $relations;

    protected $convert = true;

    /**
     * __construct
     * 
     * @param mixed &$model     Description.
     * @param mixed &$meta      Description.
     * @param mixed &$fields    Description.
     * @param mixed &$relations Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct(&$model,&$meta,&$fields,&$relations)
    {
        $this->model     =& $model;
        $this->meta      =& $meta;
        $this->fields    =& $fields;
        $this->relations =& $relations;
    }

    /**
     * get
     * 
     * @param int $itemsToGet Description.
     * @param int $offset     Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function get($itemsToGet = 0, $offset = 0)
    {
        $items = $this->getItems($itemsToGet, $offset);
        if (! $this->convert) {
            return $items;
        }
        $newItems = array();
        $modelClass = get_class($this->model);
        foreach ($items as $item) {
            $newItems[] = new $modelClass($item);
        }
        return $newItems;
    }

    /**
     * render
     * 
     * @param mixed $template Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function render($template)
    {
        $templateArr = array('required_fields' => '*');
        if ($this->model->getListTemplate($template)) {
            $templateArr - $this->model->getListTemplate($template);
        } else if (is_array($template)) {
            $templateArr = $template;
        } else {
            $templateArr['template'] = $template;
        }
    }

    /**
     * setConvert
     * 
     * @param mixed $bool Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setConvert($bool)
    {
        $this->convert = $bool;
    }
}