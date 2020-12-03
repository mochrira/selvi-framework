<?php 

function is_https() {
    if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return TRUE;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return TRUE;
    } elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return TRUE;
    }
    return FALSE;
}

function base_url() {
    if (isset($_SERVER['SERVER_ADDR'])){
        if (strpos($_SERVER['SERVER_ADDR'], ':') !== FALSE) {
            $server_addr = '['.$_SERVER['SERVER_ADDR'].']';
        } else {
            if(isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] != $_SERVER['SERVER_ADDR']) {
                $server_addr = $_SERVER['SERVER_NAME'];
            } else {
                $server_addr = $_SERVER['SERVER_ADDR'];
            }
        }

        $base_url = (is_https() ? 'https' : 'http').'://'.$server_addr.substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME'])));
    } else {
        $base_url = 'http://localhost/';
    }
    return $base_url;
}
