<?php
class User extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function login() {
        //sleep(1);
        /*
        curl --location 'http://netteamdigitalsignage.localhost/user/login' \
        --form 'email="admin@ntds.com"' \
        --form 'password="black**1"'
        */
        $receivedCheckPassword = Request::post('checkPassword', Request::get('checkPassword', false));
        $receivedEMail = Request::post('email', Request::get('email', ''));
        $receivedPassword = Request::post('password', Request::get('password', ''));
        if($receivedEMail == '' || $receivedPassword == '') {
            Response::error(0, 'E-Posta ve şifre boş bırakılamaz');
        }
        $user = DB::table('users')->select([
            'where' => [
                ['email', '=', $receivedEMail], 
                ['dtDelete', '=', null],
            ],
        ]);
        if(!$user) {
            Response::error(2, 'E-Posta adresi bulunamadı');
        }
        $user = $user[0];
        if(!$user['active']) {
            Response::error(3, 'Hesap aktif değil');
        }
        if(password_verify($receivedPassword, $user['password'])) {
            if(!$receivedCheckPassword) {
                do {
                    $token = md5(uniqid());
                } while(count(DB::table('users')->select([
                    'where' => [
                        ['token', '=', $token],
                    ],
                ])) > 0);
                $user['token'] = $token;
                $user['dtLogin'] = date('Y-m-d H:i:s');
                $user = DB::table('users')->update($user, [], true);
            }
            unset($user['password']);
            $versionFilename = 'ntds.version';
            if(file_exists(FOLDER_ROOT.$versionFilename)) {
                $version = file_get_contents(FOLDER_ROOT.$versionFilename);
                $user['version'] = $version;
            }
            Response::success($user);
        }
        Response::error(3, 'Parola hatalı');
    }
    
    public function delete() {
        $this->checkToken(true);
        $id = Request::post('id', Request::get('id', 0));
        if($id < 1) {
            Response::error(6, 'Parametre eksik: id');
        }
        if($this->user['id'] == $id) {
            Response::error(8, 'Kendi hesabınızı silemezsiniz');
        }
        $rec = DB::table('users')->delete([], [ ['id' ,'=', $id] ], true);
        Response::success($rec);
    }
    
    public function save() {
        $this->checkToken(true);
        $received = json_decode(Request::post('data', Request::get('data', '{}')), true);
        if(count($received) == 0) {
            Response::error(0, 'Parametre eksik: data');
        }
        if(isset($received['password']) && ($received['password'] == '')) {
            unset($received['password']);
        } else {
            $received['password'] = App::passwordHash($received['password']);
        }
        if($received['id'] < 1) {
            $qry = DB::table('users')->select([
                'where' => [
                    ['email', '=', $received['email']],
                ],
            ]);
            if(count($qry) > 0) {
                $rec = $qry[0];
                $rec['dtDelete'] = null;
                $received = array_merge($rec, DB::table('users')->prepareReceivedRecord($received));
            }
        }
        if(isset($received['token'])) {
            unset($received['token']);
        }
        $user = DB::table('users')->insertOrUpdate($received, [], true);
        if($user != false) {
            Response::success($user);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }

    /*
    list all users
    ----------------------------------------------------------------
    http://netteamdigitalsignage.localhost/user/list
    ================================================================
    list deleted users
    ----------------------------------------------------------------
    http://netteamdigitalsignage.localhost/user/list?deleted=1
    ================================================================
    */
    public function list() {
        //$this->checkToken(true);
        if(Request::get('deleted', false)) {
            $deletedOpr = '<>';
        } else {
            $deletedOpr = '=';
        }
        $users = DB::table('users')->select([ 
            'where' => [
                ['dtDelete', $deletedOpr, null],
            ],
        ]);
        for($i=0;$i<count($users);$i++) {
            unset($users[$i]['password']);
            unset($users[$i]['token']);
        }
        Response::success($users);
    }
    
}