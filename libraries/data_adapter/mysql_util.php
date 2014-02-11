<?php

/**
* MysqlUtil
*
* @uses     
*
* @category Category
* @package  Package
* @author   Abhi
*/
class MysqlUtil
{
    /**
     * getQuery
     * 
     * @param string $table   Description.
     * @param string &$fields Description.
     * @param string &$clause Description.
     * @param string &$limits Description.
     * @param string &$sorts  Description.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
    public static function getQuery($table,
        &$fields='*',
        &$clause='1=1',
        &$limits='',
        &$sorts=''
    ) {
        if (is_array($fields)) {
            $fieldArr = array();
            foreach ($fields as $key => $field) {
                if (is_array($field)) {
                    $fieldArr[] = $field['db_col'] . ' ' . $key;
                } else {
                    $fieldArr[] = $field;
                }
            }
            $fieldsStr = implode(',', $fieldArr);
        } else {
            $fieldsStr = $fields;
        }

        if (is_array($clause)) {
            $clauseArr = array();

            foreach ($clause as $col => $theClause) {
                if (is_array($theClause)) {
                    $clauseArr[] = $col . ' in (' . implode(',', $key) . ')';
                } else {
                    $fieldArr[] = $field;
                }
            }
            $fieldsStr = implode(',', $fieldArr);
        } else {
            $fieldsStr = $fields;
        }

        
    }
}