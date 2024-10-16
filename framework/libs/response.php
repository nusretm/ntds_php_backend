<?php
class Response {
    public static function json($arr) {
        $res = json_encode($arr, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_NUMERIC_CHECK);
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo $res;
        ob_end_flush();
        exit();
    }

    public static function error($code, $msg) {
        self::json([
             'success' => false
            ,'error' => ['code' => $code, 'msg' => $msg]
        ]);
    }
    
    public static function success($result) {
        self::json([
             'success' => true
            ,'error' => ['code' => 0, 'msg' => '']
            ,'result' => $result
        ]);
    }    
}