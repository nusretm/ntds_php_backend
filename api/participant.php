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
        $this->checkToken();
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
    
}