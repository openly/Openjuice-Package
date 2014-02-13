<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
* Expression
*
* @uses     
*
* @category Category
* @package  Package
* @author   Abhi
*/
class Expression
{
    protected $left;
    protected $right;
    protected $oper;

    protected $uniaryOperators = array('not');
    protected $binaryOperators = array('and','or');
    protected $relationOperators = array('>=','<=','==','!=','>','<');
    //array_merge($uniaryOperators, $binaryOperators);
    protected $operators = array();
    protected $fieldNames = null;


    /**
     * __construct
     * 
     * @param mixed &$fieldNames Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function __construct(&$fieldNames)
    {
        $this->operators = array_merge(
            $this->uniaryOperators,
            $this->binaryOperators,
            $this->relationOperators
        );
        $this->fieldNames = $fieldNames;
    }

    /**
     * parse
     * 
     * @param mixed $e Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function parse($e)
    {
        if(is_string($e)) 
            $arr = $this->exprStrToArray($e);
        else 
            $arr = $e;
        
        if(count($arr) == 1)
            return $arr[0];

        $exprs = array();
        $opers = array();

        $curExpr = '';

        $pCount = 0;
        foreach ($arr as $term) {
            if (in_array(strtolower($term), $this->operators) && $pCount == 0) {
                $exprs[] = $this->parse($curExpr);
                while (($opCount = count($opers)) > 0) {
                    $oper = $opers[$opCount - 1 ];
                    if ($this->higherPrecedence($oper, $term)) {
                        $exprs[] = array(
                            'oper' => $oper,
                            'right' => array_pop($exprs),
                            'left' => array_pop($exprs)
                        );
                        array_pop($opers);
                    } else {
                        break;
                    }
                }
                $opers[] = $term;
                $curExpr = '';
            } else {
                $curExpr .= ' ' . $term;
            }

            $pCount += (($term == '(') ? 1 : (($term == ')') ? -1 : 0));
        }
        $exprs[] = $this->parse($curExpr);

        while (($opCount = count($opers)) > 1) {
            $oper = array_pop($opers);
            $exprs[] = array(
                'oper' => $oper,
                'right' => array_pop($exprs),
                'left' => array_pop($exprs)
            );
        }

        return array(
            'oper' => $opers[0],
            'left'=>$exprs[0],
            'right' => $exprs[1]
        );
    }


    /**
     * higherPrecedence
     * 
     * @param mixed $op1 Description.
     * @param mixed $op2 Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function higherPrecedence($op1,$op2)
    {
        $prec1 = array_search($op1, $this->operators);
        $prec2 = array_search($op2, $this->operators);
        return $prec1 < $prec2;
    }

    /**
     * exprStrToArray
     * 
     * @param mixed $str Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function exprStrToArray($str)
    {
        $str = preg_replace('/^\s*\((.*)\)\s*$/', '$1', $str);
        $str = preg_replace('/([\(\)])/', ' $1 ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        return explode(' ', $str);
    }

    /**
     * validateExpression
     * 
     * @param mixed $condition Description.
     * @param mixed &$args     Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    function validateExpression($condition,&$args=null)
    {
        if (!$args) {
            $args = $_POST;
        }
        if (is_array($condition)) {
            $leftExpr = $this->validateExpression($condition['left'], $args);
            $rightExpr = $this->validateExpression($condition['right'], $args);
            switch (strtoupper($condition['oper']))
            {
                case "OR" :
                    $retVal = $leftExpr || $rightExpr;
                    break;
                case "AND" :
                    $retVal = $leftExpr && $rightExpr;
                    break;
                case "NOT" :
                    $retVal = !$rightExpr;
                    break;
                case ">=" :
                    $retVal = $leftExpr >= $rightExpr;
                    break;
                case "<=" :
                    $retVal = $leftExpr <= $rightExpr;
                    break;
                case "==" :
                    $retVal = $leftExpr == $rightExpr;
                    break;
                case "!=" :
                    $retVal = $leftExpr != $rightExpr;
                    break;
                case ">" :
                    $retVal = $leftExpr > $rightExpr;
                    break;
                case "<" :
                    $retVal = $leftExpr < $rightExpr;
                    break;
            }
        } else if (in_array($condition, array_keys($args))) {
            $retVal = $args[$condition];
        } else if (is_numeric($condition)) {
            $retVal = (float)$condition;
        } else {
            $retVal = $condition;
        }

        return $retVal;
    }
}