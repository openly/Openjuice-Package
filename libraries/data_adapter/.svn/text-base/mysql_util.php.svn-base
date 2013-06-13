<?php

/**
* 
*/
class MysqlUtil
{
	public static function getQuery($table,&$fields='*',&$clause='1=1',&$limits='',){
		if(is_array($fields)){
			$fieldArr = array();
			foreach ($fields as $key => $field) {
				if(is_array($field)){
					$fieldArr[] = $field['db_col'] . ' ' . $key;
				}else{
					$fieldArr[] = $field;
				}
			}
			$fieldsStr = implode(',', $fieldArr);
		}else{
			$fieldsStr = $fields;
		}

		if(is_array($clause)){
			$clauseArr = array();

			foreach ($clause as $col => $theClause) {
				if(is_array($theClause)){
					$clauseArr[] = $col . ' in (' . implode(',',$key) . ')';
				}else{
					$fieldArr[] = $field;
				}
			}
			$fieldsStr = implode(',', $fieldArr);
		}else{
			$fieldsStr = $fields;
		}

		
	}
}