<?php 

namespace Selvi\Database;

class QueryBuilder {

    private static $lastQuery = '';
    private static $rawDefault = ['select' => 'SELECT *'];
    private static $raw = [];

    private static $argsDefault = [];
    private static $args = [];

    private static function getRaw($name) {
        return (isset(self::$raw[$name])?self::$raw[$name]:
                (isset(self::$rawDefault[$name])?self::$rawDefault[$name]:NULL));
    }

    private static function getArgs($name) {
        return (isset(self::$args[$name]))?self::$args[$name]:
                (isset(self::$argsDefault[$name])?self::$argsDefault[$name]:[]);
    }

    public static function limit($limit = null) {
        if($limit !== null) {
            self::$raw['limit'] = 'LIMIT '.$limit;
        }
    }

    public static function innerJoin($tbl, $cond)
	{
        if(!isset(self::$raw['join'])) { self::$raw['join'] = []; }
		self::$raw['join'][] = 'INNER JOIN '.$tbl.' ON '.$cond;
	}

	public static function leftJoin($tbl, $cond)
	{
        if(!isset(self::$raw['join'])) { self::$raw['join'] = []; }
        self::$raw['join'][] = 'LEFT JOIN '.$tbl.' ON '.$cond;
    }
    
    public static function join($tbl, $cond = null)
    {
        if(!isset(self::$raw['join'])) { self::$raw['join'] = []; }
        self::$raw['join'][] = 'JOIN '.$tbl.($cond != null ? ' ON '.$cond : '');
    }

    public static function offset($offset) {
        if($offset !== null) {
            self::$raw['offset'] = 'OFFSET '.$offset;
        }
    }

    public static function where($param = '', $param2 = null) {        
        $str = '';
        $args = [];

        if(is_array($param) && count($param) > 0){
            $i=0;
            foreach($param as $p){
                if($i++ !=0){ $str .= ' AND '; }
                if(is_array($p)){
                    if(count($p) == 2) {
                        $str .= $p[0].' '.($p[1] === null ? 'IS NULL' : '= ?');
                        if($p[1] !== null) $args[] = $p[1];
                    } else if(count($p) == 3){
                        $str .= $p[0].' '.$p[1].' '.($p[2] === null ? 'NULL' : '?');
                        if($p[2] !== null) $args[] = $p[2];
                    }
                }else if(is_string($p)){
                    $str .= $p;
                }
            }
        }

        if(is_string($param) && strlen($param)>0){
            if(strlen($param2)>0){
                $str .= $param.' '.($param2 == null ? 'IS NULL' : '= ?');
                if($param2 != null) $args[] = $param2;
            }else{
                $str .= $param;
            }
        }

        if(is_string($param) && $param2 == null) {
            $str = $param;
        }

        if(strlen($str)>0){
            if(isset(self::$raw['where']) && strlen(self::$raw['where']) > 0){
                self::$raw['where'] .= ' AND ('.$str.')';
            }else{
                self::$raw['where'] = 'WHERE ('.$str.')';
            }
            self::$args['where'] = array_merge(self::getArgs('where'), $args);
        }
    }

    public static function orWhere($param = '', $param2 = null) {
        $str = '';
        $args = [];

        if(is_array($param) && count($param) > 0){
            $i=0;
            foreach($param as $p){
                if($i++ !=0){ $str .= ' OR '; }
                if(is_array($p)){
                    if(count($p) == 2) {
                        $str .= $p[0].' '.($p[1] === null ? 'IS NULL' : '= ?');
                        if($p[1] !== null) $args[] = $p[1];
                    } else if(count($p) == 3){
                        $str .= $p[0].' '.$p[1].' '.($p[2] === null ? 'NULL' : '?');
                        if($p[2] !== null) $args[] = $p[2];
                    }
                }else if(is_string($p)){
                    $str .= $p;
                }
            }
        }

        if(is_string($param) && strlen($param)>0){
            if(strlen($param2)>0){
                $str .= $param.' '.($param2 == null ? 'IS NULL' : '= ?');
                if($param2 != null) $args[] = $param2;
            }else{
                $str .= $param;
            }
        }

        if(is_string($param) && $param2 == null) {
            $str = $param;
        }

        if(strlen($str)>0){
            if(isset(self::$raw['where']) && strlen(self::$raw['where']) > 0){
                self::$raw['where'] .= ' AND ('.$str.')';
            }else{
                self::$raw['where'] = 'WHERE ('.$str.')';
            }
            self::$args['where'] = array_merge(self::getArgs('where'), $args);
        }
    }

    public static function groupBy($group) {
        $str = '';
        if(is_array($group)) {
            $str .= implode(' , ', $group);
        } else if(is_string($group)){
            $str .= $group;
        }
        if(strlen($str) > 0) {
            $str = ((self::getRaw('group') == null) ? ' GROUP BY ' : ' , ').$str;
            if(isset(self::$raw['group'])) {
                self::$raw['group'] .= $str;
            } else {
                self::$raw['group'] = $str;
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

    private static function is_json($str) {
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private static function prepareValue($val) {
        if(is_bool($val)) {
            $val = ($val == true ? 1 : 0);
        }
        return $val;
    }

    public static function insert($tbl, $data) {
        $cols = array_keys($data);
        $colStr = implode(", ", $cols);

        $values = array_map(function ($v) {
            return self::prepareValue($v);
        }, array_values($data));

        $valStr = implode(', ', array_map(function ($item) {
            return '?';
        }, $values));

        self::$raw = self::$rawDefault;
        self::$args = self::$argsDefault;
        return [
            'sql' => 'INSERT INTO '.$tbl.' ('.$colStr.') VALUES ('.$valStr.')',
            'args' => $values
        ];
    }

    public static function update($tbl, $data) {
        $setStr = implode(', ', array_map(function ($key) {
            return $key.'=?';
        }, array_keys($data)));

        $values = array_map(function ($v) {
            return self::prepareValue($v);
        }, array_values($data));

        $args = array_merge($values, self::getArgs('where'));

        $result = [
            'sql' => 'UPDATE '.$tbl.' SET '.$setStr.' '.self::getRaw('where'),
            'args' => $args
        ];

        self::$raw = self::$rawDefault;
        self::$args = self::$argsDefault;
        return $result;
    }

    public static function delete($tbl) {
        $sql = implode(' ', array(
            'DELETE '.$tbl.' FROM '.$tbl, 
            self::getRaw('join') !== null ? implode(' ', self::getRaw('join')) : '',
            self::getRaw('where')
        ));

        $args = array_merge(
            self::getArgs('join'),
            self::getArgs('where')
        );

        self::$raw = self::$rawDefault;
        self::$args = self::$argsDefault;

        return [
            'sql' => $sql,
            'args' => $args
        ];
    }

    public static function get($table = NULL) {
        $sql = implode(' ', array(
            self::getRaw('select'),
            isset($table) ? 'FROM '.$table : '',
            self::getRaw('join') !== null ? implode(' ', self::getRaw('join')) : '',
            self::getRaw('where'),
            self::getRaw('group'),
            self::getRaw('order'),
            self::getRaw('limit'),
            self::getRaw('offset')
        ));

        $args = array_merge(
            self::getArgs('select'),
            self::getArgs('join'),
            self::getArgs('where'),
            self::getArgs('group'),
            self::getArgs('order'),
            self::getArgs('limit'),
            self::getArgs('offset')
        );

        self::$raw = self::$rawDefault;
        self::$args = self::$argsDefault;

        return [
            'sql' => $sql,
            'args' => $args
        ];
    }

    public static function createDb($name) {
        return 'CREATE DATABASE `'.$name.'`';
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
        self::$raw = self::$rawDefault;
        return $sql;
    }

    public static function createSchema($name) {
        return 'CREATE DATABASE '.$name;
    }

    public static function dropSchema($name) {
        return 'DROP DATABASE '. $name;
    }

    public static function createIndex($table, $index_name, $cols) {
        return 'CREATE INDEX '.$index_name.' ON '.$table.'('.implode(',', $cols).')';
    }

    public static function rename($table, $new_table) {
        $sql = 'ALTER TABLE '.$table.' RENAME TO '.$new_table;
        return $sql;
    }

    public static function createLike($table, $new_table) {
        $sql = 'CREATE TABLE '.$new_table.' LIKE '.$table;
        return $sql;
    }

    public static function copyData($table, $new_table) {
        $sql = 'INSERT INTO '.$new_table.' SELECT * FROM '.$table;
        return $sql;
    }

    public static function truncate($table) {
        $sql = 'TRUNCATE '.$table;
        return $sql;
    }

    public static function drop($table) {
        $sql = 'DROP TABLE IF EXISTS '.$table;
        return $sql;
    }

    public static function dropIndex($table, $index_name) {
        return 'ALTER TABLE '.$table.' DROP INDEX '.$index_name;
    }

    public static function modifyColumn($column, $type) {
        self::$raw['alter'][] = 'MODIFY COLUMN '.$column.' '.$type;
    }

    public static function changeColumn($table, $column, $new_column, $type) {
        $sql = 'ALTER TABLE '.$table.' CHANGE COLUMN '.$column.' '.$new_column.' '.$type;
        return $sql;
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

    public static function dropPrimary(){
        self::$raw['alter'][] = 'DROP PRIMARY KEY';
    }

    public static function addPrimary($column){
        self::$raw['alter'][] = 'ADD PRIMARY KEY('.$column.')';
    }

    public static function startTransaction() {
        return 'START TRANSACTION;';
    }

    public static function rollback() {
        return 'ROLLBACK;';
    }

    public static function commit() {
        return 'COMMIT;';
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