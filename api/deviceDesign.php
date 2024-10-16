<?php

class DeviceDesign extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function list() {
        $res = [];
        $res = DB::table('device_designs')->select([
            'order' => [
                ['timing', 'asc'],
                ['priority', 'desc'],
                ['dtStart', 'asc'],
                ['tmStartHour', 'asc'],
                ['tmStartMin', 'asc'],
                ['dtCreate', 'asc'],
            ],
        ]);
        Response::success($res);
    }

    public function save() {
        $this->checkToken(true);
        $received = Request::post('data', Request::get('data', ''));
        $data = json_decode($received, true);
        switch($data['timing']) {
            case 1: /* Tarih aralığı */
                $fmt = datefmt_create(
                    'tr_TR',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::FULL,
                    'Europe/Istanbul',
                    IntlDateFormatter::GREGORIAN,
                    'dd MMMM Y'
                );
                $dtStart = datefmt_format($fmt, strtotime($data['dtStart']));
                $dtEnd = datefmt_format($fmt, strtotime($data['dtEnd']));
                $data['info'] = str_replace([substr($dtEnd, 2, strlen($dtEnd)), date(' Y', strtotime($data['dtEnd']))], ['', ''], $dtStart)."-".$dtEnd;
                break;
            default: /* Her gün */
                $data['info'] = 'Hergün';
        }
        if(
            ($data['tmStartHour'] != 0)
            || ($data['tmStartMin'] != 0)
            || ($data['tmEndHour'] != 23)
            || ($data['tmEndMin'] != 59)
        ) {
            $tmStart = strtotime($data['tmStartHour'].":".$data['tmStartMin']);
            $tmEnd = strtotime($data['tmEndHour'].":".$data['tmEndMin']);
            $data['info'].= ", saat ".date('H:i', $tmStart)." - ".date('H:i', $tmEnd)." arası";
        }
        $rec = DB::table('device_designs')->insertOrUpdate($data, [], true);
        if($rec != false) {
            Response::success($rec);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }    

    public function delete() {
        $this->checkToken(true);
        $id = Request::post('id', Request::get('id', 0));
        if($id < 1) {
            Response::error(6, 'Parametre eksik: id');
        }
        $rec = DB::table('device_designs')->delete([], [ ['id' ,'=', $id] ], true);
        if($rec != false) {
            DB::rawQuery("delete from device_design_items where designId=$id;");
            Response::success($rec);
        }
        Response::error(7, 'Veri silinemedi');
    }

    private function storagePath() {
        return FOLDER_ROOT.FOLDER_STORAGE.'designs/';
    }

    public function designLoad() {
        //$this->checkToken(true);
        $id = Request::post('id', Request::get('id', 0));
        if($id < 1)Response::error(6, "Parametre eksik: id ($id)");
        $fileName = $this->storagePath().$id.".json";
        if(!is_file($fileName))Response::error(6, "Veri dosyası bulunamadı ($id.json)");
        $res = json_decode(file_get_contents($fileName), true);
        Response::success($res);
    }

    public function designSave() {
        $this->checkToken(true);
        $id = Request::post('id', Request::get('id', 0));
        if($id < 1)Response::error(6, "Parametre eksik: id ($id)");
        $data = Request::post('data', Request::get('data', ''));
        $data = json_decode($data, true);
        if($data == '' || $data == null)Response::error(6, "Parametre eksik: data");
        
        $fileName = $this->storagePath().$id.".json";
        if(!is_dir(basename($fileName))){
            if(!mkdir(basename($fileName), 0755, true)) {
                Response::error(8, "Klasör ouşturulamadı (chown): \n".str_replace(dirname(__DIR__).'/', '', basename($fileName)));
            }        
        }
        file_put_contents($fileName, json_encode($data, JSON_PRETTY_PRINT));
        Response::success(['id' => $id]);
    }
}