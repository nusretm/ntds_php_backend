<?php
class Participant extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    // public function drop() {
    //     DB::rawQuery('drop table participants');
    //     unlink(__DIR__.'/../storage/cache/blueprints/table_participants.php.cache');
    //     print("devices tablosu silindi!");
    //     exit;
    // }

    private function storagePath() {
        return FOLDER_ROOT.FOLDER_STORAGE.'participants/';
    }

    private function generateFilename(int $idParticipant) {
        return $this->storagePath()."$idParticipant.json";
    }

    public function list() {
        //sleep(1);
        $idClassroom = Request::post('idClassroom', Request::get('idClassroom', ''));
        $wheres = [];
        if($idClassroom != '') {
            $wheres[] = ['idClassroom', '=', $idClassroom];
        }
        $res = DB::table('participants')->select([
            'columns' =>[
                'id', 
                'idClassroom', 
                'classroomName', 
                'dtBegin', 
                'dtEnd', 
                'itemCount'
            ],
            'where' => $wheres, 
            'order' => [
                ['dtBegin', 'DESC'], 
                ['dtEnd', 'DESC'], 
                ['classroomName', 'ASC'],
            ], 
            'limit' => 100,
        ]);
        //DB::rawQuery('drop table participants;');
        Response::success($res);
    }
    
    public function get() {
        //$this->checkToken();
        $id = Request::post('id', Request::get('id', ''));
        if($id == '') {
            Response::error(6, 'Parametre eksik: id');
        }
        $filename = $this->generateFilename($id);
        if(!is_file($filename)) {
            Response::error(6, "Veri dosyası bulunamadı $id.json");
        }
        $res = json_decode(file_get_contents($filename), true);
        Response::success($res);
    }
    
    public function delete() {
        //$this->checkToken();
        $id = Request::post('id', Request::get('id', ''));
        if($id == '') {
            Response::error(6, 'Parametre eksik: id');
        }
        $filename = $this->generateFilename($id);
        if(is_file($filename)) {
            $rec = DB::table('participants')->delete([], [ ['id' ,'=', $id] ], true);
            unlink($filename);
            Response::success($rec);
        } else {
            Response::error(6, "Veri dosyası bulunamadı $id.json");
        }
    }
    
    public function import() {
        $this->checkToken(true);
        $dtBegin = Request::post('begin', Request::get('begin', ''));
        $dtEnd = Request::post('end', Request::get('end', ''));
        $data = Request::post('data', Request::get('data', ''));        
        if( ($dtBegin == '') || ($dtEnd == '') || ($data == '') ) {
            Response::error(1, 'import parametreleri eksik');
        }
        $data = json_decode($data, true);
        if(is_null($data['idClassroom'])) {
            Response::error(1, 'import verisi hatalı veya eksik');
        }
        /*
        if(!is_dir(FOLDER_ROOT.FOLDER_STORAGE.'temp')) {
            mkdir(FOLDER_ROOT.FOLDER_STORAGE.'temp', 0755, true);
        }
        file_put_contents(FOLDER_ROOT.FOLDER_STORAGE.'temp/participants-import-'.$data['idClassroom'].'.json', json_encode($data, JSON_PRETTY_PRINT));
        */
        $recClassroom = DB::table('classrooms')->select([
            'where' => [
                ['id', '=', $data['idClassroom']],
            ],
        ]);
        if(count($recClassroom) == 0) {
            Response::error(8, 'Sınıf kaydı bulunamadı');
        }
        $recClassroom = $recClassroom[0];
        $data['dtBegin'] = $dtBegin;
        $data['dtEnd'] = $dtEnd;
        $data['classroomName'] = $recClassroom['name'];
        $data['itemCount'] = count($data['items']);
        
        $rec = DB::table('participants')->select([
            'where' => [
                ['idClassroom', '=', $data['idClassroom']],
                ['dtBegin', '=', $data['dtBegin']],
                ['dtEnd', '=', $data['dtEnd']],
            ],
        ]);
        if(count($rec) > 0) {
            $rec = $rec[0];
            $rec['itemCount'] = $data['itemCount'];
            $rec = DB::table('participants')->update($rec, null, true);
        } else {
            $rec = DB::table('participants')->insert($data, true);
        }
        $data['id'] = $rec['id'];
        $filename = $this->generateFilename($data['id'], $dtBegin, $dtEnd);
        if(!is_dir(dirname($filename))) {
            if(!mkdir(dirname($filename), 0755, true)) {
                Response::error(8, "Klasör ouşturulamadı (chown): \n".str_replace(dirname(__DIR__).'/', '', dirname($filename)));
            }
        }
        if(!file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT))) {
            Response::error(8, "Veri dosyası oluşturulamadı (chown): \n".basename($filename));
        }
        $res = [];
        Response::success($data);
    }
    
    public function deviceParticipants() {
        $deviceUUID = Request::post('uuid', Request::get('uuid', ''));
        if($deviceUUID == '') {
            Response::error(1, 'Invalid device uuid');
        }
        
        $qry = DB::table('devices')->select([
            'where' => [
                ['uuid', '=', $deviceUUID],
            ],
        ]);
        if(count($qry) > 0) {
            $deviceRec = $qry[0];
        } else {
            Response::error(2, 'Invalid device uuid');
        }
        if(is_null($deviceRec['classroomId']) || (!is_null($deviceRec['classroomId']) && ($deviceRec['classroomId'] < 1))) {
            Response::error(3, 'Cihazın bağlı olduğu sınıf belirtilmemiş');
        }
        $day = date('w');
        $startTime = strtotime(date('Y-m-d', strtotime('-'.$day.' days'))) ;
        $list = DB::table('participants')->select([
            'columns' =>[
                'id', 
                'idClassroom', 
                //'classroomName', 
                'dtBegin', 
                'dtEnd', 
                'itemCount'
            ],
            'where' => [
                ['idClassroom', '=', $deviceRec['classroomId']],
            ],
            'order' => [
                ['dtBegin', 'ASC'], 
                ['dtEnd', 'ASC'], 
                ['classroomName', 'ASC'],
            ], 
            'limit' => 100,
        ]);
        $res = [];
        foreach($list as $item) {
            if(strtotime($item['dtEnd']) >= $startTime) {
                $filename = $this->generateFilename($item['id']);
                if(file_exists($filename)) {
                    $participants = json_decode(file_get_contents($filename), true);
                    usort($participants['items'], function($a, $b) {
                        $coll = collator_create( 'en_US' );
                        return collator_compare( $coll, $a['fullName'], $b['fullName']);
                    });
                    $res[] = $participants;
                }
            }
        }
        Response::success($res);
    }
}