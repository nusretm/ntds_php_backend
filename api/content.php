<?php
class Content extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function list() {
        //sleep(3);
        $res = DB::table('contents')->select([
            'order' => [
                ['timing', 'asc'],
                ['priority', 'desc'],
                ['dtStart', 'asc'],
                ['tmStartHour', 'asc'],
                ['tmStartMin', 'asc'],
                ['dtCreate', 'asc'],
            ],
        ]);
        //DB::rawQuery('drop table contents;');
        //unlink(__DIR__.'/../storage/cache/blueprints/table_contents.php.cache');
        Response::success($res);
    }

    private function updateContentTotalDuration($contentId) {
        $rec = DB::table('contents')->select([
            'where' => [
                ['id', '=', $contentId],
            ]
        ]);
        if(count($rec) > 0) {
            $rec = $rec[0];
            $rec['totalDuration'] = 0;
            $contentItems = DB::table('content_items')->select([
                'where' => [
                    ['idContent', '=', $contentId],
                    ['active', '=', 1],
                ]
            ]);
            foreach($contentItems as $item) {
                $rec['totalDuration'] += $item['duration'];
            }
            DB::table('contents')->update($rec);
        }
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
        $rec = DB::table('contents')->insertOrUpdate($data, [], true);
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
        $rec = DB::table('contents')->delete([], [ ['id' ,'=', $id] ], true);
        if($rec != false) {
            DB::rawQuery("delete from content_items where idContent=$id;");
            Response::success($rec);
        }
        Response::error(7, 'Veri silinemedi');
    }

    public function itemList() {
        //sleep(3);
        //$this->checkToken(true);
        $contentId = Request::post('contentId', Request::get('contentId', 0));
        if($contentId < 1) {
            Response::error(6, 'Parametre eksik: contentId');
        }
        $qry = DB::table('content_items')->select([ 
            'where' => [ ['idContent', '=', $contentId] ],
            'order' => [ 
                ['priority', 'desc'],
                ['dtCreate', 'asc'],
            ],
        ]);
        $res = [];
        foreach($qry as $row) {
            if(($row['properties'] == '') || ($row['properties'] == null)) {
                $row['properties'] == [];
            }
            $row['properties'] = json_decode($row['properties'], true);
            $res[] = $row;
        } 
        Response::success($res);
    }

    public function itemSave() {
        $this->checkToken(true);
        $received = Request::post('data', Request::get('data', ''));
        $data = json_decode($received, true);
        $data['properties'] = json_encode($data['properties']);
        $rec = DB::table('content_items')->insertOrUpdate($data, [], true);
        if($rec != false) {
            $this->updateContentTotalDuration($rec['idContent']);
            Response::success($rec);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }

    public function itemDelete() {
        $this->checkToken(true);
        $id = Request::post('id', Request::get('id', 0));
        if($id < 1) {
            Response::error(6, 'Parametre eksik: id');
        }
        $rec = DB::table('content_items')->delete([], [ ['id' ,'=', $id] ], true);
        if($rec != false) {
            $this->updateContentTotalDuration($rec['idContent']);
            Response::success($rec);
        }
        Response::error(7, 'Veri silinemedi');
    }

}