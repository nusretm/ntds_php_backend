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
        $dateStart = strtotime(Request::post('start', Request::get('start', date('d.m.Y'))));
        $dateEnd = strtotime(Request::post('end', Request::get('end', date('d.m.Y'))));
        $path = $this->storagePath();
        $date = $dateStart;
        $res = [];
        do {
            $dailyLesson = [
                'date' => date('d.m.Y', $date),
                'classrooms' => 0,
            ];
            if(is_dir($path.date('d.m.Y', $date))) {
                $files = scandir($path.date('d.m.Y', $date), 1);
                foreach($files as $file) {
                    $filename = $path.date('d.m.Y', $date).'/'.$file;
                    print "$filename\n";
                    if(is_file($filename) && substr($file, -5) == '.json') {
                        $dailyLesson['classrooms']++;
                    }
                }
            }
            $res[] = $dailyLesson;
            //print date('d.m.Y', $dailyLesson['date'])." - ".count($dailyLesson['classrooms'])." sınıf -> ";
            //print json_encode($dailyLesson['classrooms']);
            //print "<hr>";
            $date = strtotime("+1 day", $date);
        } while($date <= $dateEnd);
        Response::success($res);
    }

    public function listDay() {
        $date = strtotime(Request::post('date', Request::get('date', date('d.m.Y'))));
        $path = $this->storagePath();
        $res = [];
        if(is_dir($path.date('d.m.Y', $date))) {
            $files = scandir($path.date('d.m.Y', $date), 1);
            foreach($files as $file) {
                $filename = $path.date('d.m.Y', $date).'/'.$file;
                if(is_file($filename) && substr($file, -5) == '.json') {
                    $res[] = json_decode(file_get_contents($filename), true);
                }
            }
        }
        //print date('d.m.Y', $dailyLesson['date'])." - ".count($dailyLesson['classrooms'])." sınıf -> ";
        //print json_encode($dailyLesson['classrooms']);
        //print "<hr>";
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
            $filename = $this->generateFilename($plan['idClassroom'], $plan['date']);
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