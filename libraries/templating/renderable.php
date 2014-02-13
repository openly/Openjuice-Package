<?php
require_once dirname(__FILE__) . '/mustache.php';

/**
* Renderable
*
* @uses     Mustache
*
* @category Category
* @package  Package
* @author   Abhi
*/
class Renderable extends Mustache
{

    protected $fieldsToRender = array();
    protected $template;

    /**
     * __construct
     * 
     * @param mixed $template Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($template=null)
    {
        $this->template = $template;
    }

    /**
     * getTemplateVars
     * 
     * @access protected
     *
     * @return mixed Value.
     */
    protected function getTemplateVars()
    {
        $retval = array();
        foreach ($this->fieldsToRender as $field) {
            $retval[$field] = $this->{$field};
        }
        return $retval;
    }

    /**
     * getMarkup
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function getMarkup(&$args = null)
    {
        if (!is_array($args)) {
            $args = $this->getTemplateVars();
        }
        $m = new Mustache;
        return $m->render($this->template, $args);
    }

    /**
     * render
     * 
     * @param mixed &$args Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function render(&$args = null)
    {
        echo $this->getMarkup($args);
    }

    /**
     * setTemplate
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function setTemplate($str)
    {
        $this->template = $str;
    }
}

/**
* SimpleRenderable
*
* @uses     Renderable
*
* @category Category
* @package  Package
* @author   Abhi
*/
class SimpleRenderable extends Renderable
{
    /**
     * __construct
     * 
     * @param mixed $template Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($template=null)
    {
        $this->template = $template;
    }
}
