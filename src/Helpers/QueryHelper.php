<?php 

if(!function_exists('buildSearch')) {
    function buildSearch($cols, $str) {
        $orWhere = [];
        foreach($cols as $col) {
            $orWhere[] = [$col, 'LIKE', '%'.$str.'%'];
        }
        return $orWhere;
    }
}

if(!function_exists('buildOrder')) {
    function buildOrder($str) {
        $order = [];
        foreach(explode(',', $str) as $s) {
            $a = explode(':', $s);
            $order[$a[0]] = $a[1];
        }
        return $order;
    }
}