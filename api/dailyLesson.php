<?php

use PSpell\Config;

class DailyLesson extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    private function storagePath() {
        return FOLDER_ROOT.FOLDER_STORAGE.'daily_lessons/';
    }

    private function generateFilename(int $idClassRoom, string $date) {
        return $this->storagePath()."$date/$idClassRoom".'.json';
    }

    public function calendar() {
        $dateStart = strtotime(Request::post('start', Request::get('start', date('Y-m-d'))));
        $dateEnd = strtotime(Request::post('end', Request::get('end', date('Y-m-d'))));
        $path = $this->storagePath();
        $date = $dateStart;
        $res = [];
        do {
            $dailyLesson = [
                'date' => date('Y-m-d', $date),
                'classrooms' => 0,
            ];
            if(is_dir($path.date('Y-m-d', $date))) {
                $files = scandir($path.date('Y-m-d', $date), 1);
                foreach($files as $file) {
                    $filename = $path.date('Y-m-d', $date).'/'.$file;
                    print "$filename\n";
                    if(is_file($filename) && substr($file, -5) == '.json') {
                        $dailyLesson['classrooms']++;
                    }
                }
            }
            $res[] = $dailyLesson;
            //print date('Y-m-d', $dailyLesson['date'])." - ".count($dailyLesson['classrooms'])." sınıf -> ";
            //print json_encode($dailyLesson['classrooms']);
            //print "<hr>";
            $date = strtotime("+1 day", $date);
        } while($date <= $dateEnd);
        Response::success($res);
    }

    public function listDay() {
        $date = strtotime(Request::post('date', Request::get('date', date('Y-m-d'))));
        $path = $this->storagePath();
        $res = [];
        if(is_dir($path.date('Y-m-d', $date))) {
            $files = scandir($path.date('Y-m-d', $date), 1);
            foreach($files as $file) {
                $filename = $path.date('Y-m-d', $date).'/'.$file;
                if(is_file($filename) && substr($file, -5) == '.json') {
                    $res[] = json_decode(file_get_contents($filename), true);
                }
            }
        }
        //print date('Y-m-d', $dailyLesson['date'])." - ".count($dailyLesson['classrooms'])." sınıf -> ";
        //print json_encode($dailyLesson['classrooms']);
        //print "<hr>";
        Response::success($res);
    }
    
    public function deviceLessons() {
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
        $deviceClassFilename = $deviceRec['classroomId'].'.json';

        $day = date('w');
        $dateStart = date('Y-m-d', strtotime('-'.$day.' days'));

        $dirs = scandir($this->storagePath(), 1);
        $res = [];
        foreach ($dirs as $dir) {
            if($dir == '.' || $dir == '..' || $dir < $dateStart) {
                continue;
            }
            $filename = $this->storagePath().$dir.'/'.$deviceClassFilename;
            if(file_exists($filename)) {
                $res[date('Y-m-d', strtotime($dir))] = json_decode(file_get_contents($filename), true);
                $res[date('Y-m-d', strtotime($dir))]['rawDate'] = date('Y-m-d', strtotime($dir));
            }
        }
        Response::success($res);
    }

    public function save() {
        $this->checkToken(true);
        /*
        [{
            "id":0,
            "idClassroom":0,
            "date":"15.07.2024",
            "day":"PAZARTESİ",
            "items":[
                {"id":0,"timeBegin":"09:30","timeEnd":"10:15","name":"Açılış","teacher":"","lunchBreak":false},
                {"id":0,"timeBegin":"10:30","timeEnd":"11:15","name":"Resmi Tatil","teacher":"","lunchBreak":false}
            ]
        }]
        */
        $json = Request::post('plan', '');
        try {
            $plan = json_decode($json, true);
            $classroom = [
                'name' => $plan['classroomName'],
            ];
            if($plan['idClassroom'] < 1) {
                $res = DB::table('classrooms')->select([ 
                    'where' => [
                        ['name', '=', $plan['classroomName']],
                    ],
                ]);
                if(count($res) > 0) {
                    $classroom = $res[0];
                    $classroom['dtDelete'] = null;
                }
            }
            $classroom = DB::table('classrooms')->insertOrUpdate($classroom, [], true);
            $plan['idClassroom'] = $classroom['id'];
            $filename = $this->generateFilename($plan['idClassroom'], date('Y-m-d', strtotime($plan['date'])));
            if(!is_dir(dirname($filename))) {
                if(!mkdir(dirname($filename), 0755, true)) {
                    Response::error(8, "Klasör ouşturulamadı (chown): \n".str_replace(dirname(__DIR__).'/', '', dirname($filename)));
                }
            }
            file_put_contents($filename, json_encode($plan, JSON_PRETTY_PRINT));
            //$plan['folder'] = dirname($filename);
            //$plan['file'] = basename($filename);
            Response::success([$plan]);
        } catch (Exception $e) {
            Response::error(8, "plan parametresi json formatında olmalı");
        }
    }
}