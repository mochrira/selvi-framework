<?php 

namespace Selvi\Database;

class QueryBuilder {

    private static $lastQuery = '';
    private static $rawDefault = ['select' => 'SELECT *'];
    private static $raw = [];

    private static function getRaw($name) {
        return (isset(self::$raw[$name])?self::$raw[$name]:
                (isset(self::$rawDefault[$name])?self::$rawDefault[$name]:''));
    }

    public static function limit($limit = null) {
        if($limit !== null) {
            self::$raw['limit'] = 'LIMIT '.$limit;
        }
    }

    public static function offset($offset) {
        if($offset !== null) {
            self::$raw['offset'] = 'OFFSET '.$offset;
        }
    }

    public static function where($param = '', $param2 = null) {
        $str = '';
        if(is_array($param) && count($param) > 0){
            $i=0;
            foreach($param as $p){
                if($i++ !=0){ $str .= ' AND '; }
                if(is_array($p)){
                    if(count($p) == 2) {
                        $str .= $p[0].' = "'.$p[1].'"';
                    } else if(count($p) == 3){
                        $str .= $p[0].' '.$p[1].' "'.$p[2].'"';
                    }
                }else if(is_string($p)){
                    $str .= $p;
                }
            }
        }

        if(is_string($param) && strlen($param)>0){
            if(strlen($param2)>0){
                $str .= $param.'="'.$param2.'"';
            }else{
                $str .= $param;
            }
        }
        if(strlen($str)>0){
            if(isset(self::$raw['where']) && strlen(self::$raw['where']) > 0){
                self::$raw['where'] .= ' AND ('.$str.')';
            }else{
                self::$raw['where'] = 'WHERE ('.$str.')';
            }	
        }        
    }

    public static function select($cols = null) {
        $str = '*';
        if($cols !== null) {
            if(is_string($cols)) {
                $str = $cols;
            }
            if(is_array($cols) && count($cols) > 0) {
                $str = implode(', ', $cols);
            }
        }
        self::$raw['select'] = "SELECT ".$str;
    }

    function insert($tbl, $data) {
        $i = 0;
        $col = '';
        $val = '';
        foreach($data as $c => $v){
            if($i++ != 0) {$col .= ', '; $val .= ', ';};
            $col .= '`'.$c.'`';
            $val .= (is_null($v)?'NULL':'"'.$v.'"');
        }
        return 'INSERT INTO '.$tbl.' ('.$col.') VALUES ('.$val.')';
    }

    function update($tbl, $data) {
        $i = 0;
        $p = '';
        foreach($data as $c => $v){
            if($i++ != 0) {$p .= ', ';};
            $p .= '`'.$c.'` = '.(is_null($v)?'NULL':'"'.$v.'"');
        }
        return implode(' ', array('UPDATE '.$tbl.' SET '.$p, self::getRaw('where')));
    }

    function delete($tbl) {
        return implode(' ', array('DELETE FROM '.$tbl, self::getRaw('where')));
    }

    public static function get($table = NULL) {
        $sql = implode(' ', array(
            self::getRaw('select'),
            isset($table) ? 'FROM '.$table : '',
            self::getRaw('join'),
            self::getRaw('where'),
            self::getRaw('order'),
            self::getRaw('limit'),
            self::getRaw('offset')
        ));
        self::$raw = self::$rawDefault;
        return $sql;
    }

    public static function create($name, $columns, $props = []){
        $sql = 'CREATE TABLE IF NOT EXISTS '.$name.' (';
        $count = count($columns);
        $i = 0;
        foreach($columns as $key => $val){
            $sql .= $key.' '.$val.' ';
            if($i<($count - 1)){
                $sql .= ',';
                $i++;
            }
        }
        $sql .= ') ';
        foreach($props as $key => $val) {
            $sql .= $key.'='.$val.' ';
        }
        return $sql;
    }

    public static function createSchema($name) {
        return 'CREATE DATABASE '.$name;
    }

    public static function drop($name) {
        $sql = 'DROP TABLE IF EXISTS '.$name;
        return $sql;
    }

    public static function modifyColumn($column, $type) {
        self::$raw['alter'][] = 'MODIFY COLUMN '.$column.' '.$type;
    }

    public static function addColumn($column, $type) {
        self::$raw['alter'][] = 'ADD COLUMN '.$column.' '.$type;
    }

    public static function addColumnFirst($column, $type) {
        self::$raw['alter'][] = 'ADD COLUMN '.$column.' '.$type.' FIRST';
    }

    public static function addColumnAfter($afterCol, $column, $type) {
        self::$raw['alter'][] = 'ADD COLUMN '.$column.' '.$type.' AFTER '.$afterCol;
    }

    public static function dropColumn($column) {
        self::$raw['alter'][] = 'DROP COLUMN '.$column;
    }

    public static function alter($table) {
        $sql = 'ALTER TABLE '.$table.' '.implode(',', self::$raw['alter']);
        self::$raw = self::$rawDefault;
        return $sql;
    }

    public static function order($param, $param2 = '') {
        $str = '';
		if(is_array($param)&&count($param)>0){
			$i=0;
			foreach($param as $key => $data){
				if($i++ != 0){$str .= ',';}
				$str .= ' '.$key.' '.$data;
			}
		}elseif(is_string($param)&&strlen($param)>0){
			$str .= $param.' '.$param2;
		}
		if(strlen($str)>0){
			if(strlen(self::getRaw('order')) > 0) {
				$str = ' , '.$str;
			}else{
				$str = 'ORDER BY '.$str;
			}
		}
		self::$raw['order'] = $str;
    }

}