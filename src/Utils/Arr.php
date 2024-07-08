<?php 

namespace Selvi\Utils;

class Arr {

    static function removeDuplicates(array $existing, array $new) {
        $tmp = [];
        array_push($tmp, ...(
            array_filter($new, function ($v) use ($existing) {
                return !in_array($v, $existing, true);
            })
        ));
        return $tmp;
    }
    
}