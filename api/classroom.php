<?php
class Classroom extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function list() {
        //sleep(3);
        if(Request::get('deleted', false)) {
            $deletedOpr = '<>';
        } else {
            $deletedOpr = '=';
        }
        $res = DB::table('classrooms')->select([ 
            'where' => [
                ['dtDelete', $deletedOpr, null],
            ],
            'order' => [
                ['name', 'asc'],
            ],
        ]);
        Response::success($res);
    }

    public function save() {
        $this->checkToken(true);
        $received = $_POST;
        if($received['id'] < 1) {
            $qry = DB::table('classrooms')->select([
                'where' => [
                    ['name', '=', $received['name']],
                ],
            ]);
            if(count($qry) > 0) {
                $received = $qry[0];
                $received['dtDelete'] = null;
            }
        } else {
        }
        $classroom = DB::table('classrooms')->insertOrUpdate($received, [], true);
        if($classroom != false) {
            Response::success($classroom);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }
    
    public function delete() {
        $this->checkToken(true);
        $received = $_POST;
        if(!isset($received['id'])) {
            Response::error(6, 'Parametre eksik: id');
        }
        $classroom = DB::table('classrooms')->update(['id' => $received['id'], 'dtDelete' => date("Y-m-d H:i:s")], [], true);
        if($classroom != false) {
            Response::success($classroom);
        }
        Response::error(7, 'Veri silinemedi');
    }

}