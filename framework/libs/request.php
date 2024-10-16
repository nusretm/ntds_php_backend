<?php
class Request {
    public static function get($name, $defaultValue=false) {
        if( (isset($_GET[$name])) || (!empty($_GET[$name])) ) {
            return $_GET[$name];
        }
        return $defaultValue;
    }

    public static function post($name, $defaultValue=false) {
        if( (isset($_POST[$name])) || (!empty($_POST[$name])) ) {
            return $_POST[$name];
        }
        return $defaultValue;
    }

}