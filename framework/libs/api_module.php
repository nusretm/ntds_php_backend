<?php
class ApiModule {
    protected $functionInfo = [];
    public $user;

    public function __construct() {
        $this->user = null;
    }
    public function checkToken($mustHave = false) {
        $token = Request::get('token', Request::post('token', ''));
        if($token != '') {
            $user = DB::table('users')->select([
                'where' => [
                    ['token', '=' , $token],
                ],
             ]);
            if(count($user) > 0) {
                $this->user = $user[0];
                unset($this->user['password']);
            } else {
                $token = '';
            }
        }
        if($token == '') {
            $this->user = null;
            Response::error(1, 'Yetkisiz erişim');
        }
        return !is_null($this->user);
    }

    public function methodNames() {
        $methods = get_class_methods($this);
        unset($methods[array_search('__construct', $methods)]);
        unset($methods[array_search('index', $methods)]);
        unset($methods[array_search('methodNames', $methods)]);
        unset($methods[array_search('checkToken', $methods)]);
        unset($methods[array_search('run', $methods)]);
        return array_values($methods);
    }

    public function run(String $apiModuleFunctionName, Array $apiModuleParams=[]) {
        try{
            if(is_null($apiModuleParams)) {
                $uriList = App::uriList();
                if(count($uriList) > 3) {
                    $apiModuleParams = array_slice($uriList, 3);
                } else {
                    $apiModuleParams = [];
                }    
            }
            if(!method_exists($this, $apiModuleFunctionName)) Response::error(5, 'request_unknown');
            $res = $this->$apiModuleFunctionName($apiModuleParams);
        } catch(\Exception $e) {
            Response::error(7, $e->getMessage());
        }
        Response::error(1, 'request_unknown');
    }

    public function index() {
        $res = [
            'name' => get_class($this),
            'methods' => $this->methodNames(),
        ];
        Response::success($res);
    }
}