<?php

class Device extends ApiModule {
    public function __construct() {
        parent::__construct();

        $this->functionInfo['list'] = [
            'description' => 'Kayıtlı cihaz listesini verir.',
            'params' => [],
        ];

        $this->functionInfo['connection'] = [
            'description' => 'Cihaz bağlandığında çağırılmalıdır. Böylece devices tablosuna kaydedilir.',
            'params' => [
                'uuid'              => ['type' => DB::FieldTypeUUID  , 'default' => ''     , 'description' => 'Boş gönderilirse yeni bir uuid üretip verir'],
                'screenWidth'       => ['type' => DB::FieldTypeInt   , 'default' => 1280   , 'description' => 'Ekran genişliği (Pixel)', 'required' => true],
                'screenHeight'      => ['type' => DB::FieldTypeInt   , 'default' => 720    , 'description' => 'Ekran yüksekliği (Pixel)', 'required' => true],
                'screenPixelRatio'  => ['type' => DB::FieldTypeDouble, 'default' => 1      , 'description' => 'Ekranın pixel yoğunluğu'],
            ],
        ];
        
        $this->functionInfo['save'] = [
            'description' => 'Cihaz bağlandığında çağırılmalıdır. Böylece devices tablosuna kaydedilir.',
            'params' => [
                'token'         => ['default' => ''     , 'description' => 'Kullanıcı oturum açtığında elde ettiği TOKEN değeridir', 'required' => true],
                'id'            => ['type' => DB::FieldTypeInt      , 'default' => ''     , 'description' => 'Cihazın kayıt id\'sidir. Boş gönderilirse uuid değeriyle kayıt aranır. Bulunamazsa yeni bir kayıt oluşturulur.'],
                'uuid'          => ['type' => DB::FieldTypeUUID     , 'default' => ''     , 'description' => 'Boş gönderilirse ve id ile kayıt bulunamazsa yeni bir uuid üretip verir'],
                'classroomId'   => ['type' => DB::FieldTypeInt      , 'default' => ''     , 'description' => 'Sınıf kayıt id\'si'],
                'title'         => ['type' => DB::FieldTypeString   , 'default' => ''     , 'description' => 'Cihazın görünen adı'],
                'description'   => ['type' => DB::FieldTypeString   , 'default' => ''     , 'description' => 'Cihaz hakkında açıklama'],
                'location'      => ['type' => DB::FieldTypeString   , 'default' => ''     , 'description' => 'Bulunduğu lokasyon'],
                'floor'         => ['type' => DB::FieldTypeString   , 'default' => ''     , 'description' => 'Bulunduğu kat'],
            ],
        ];
        
        $this->functionInfo['getDesigns'] = [
            'description' => 'UI Cihazı tarafından kullanılır. Cihaza tanımlı ekran tasarımlarının listesini verir. Listedeki istenilen tasarım /api/deviceDesign/designLoad ile indirilebilir.',
            'params' => [
                'uuid'          => ['type' => DB::FieldTypeUUID     , 'default' => ''     , 'required' => true],
            ],
        ];

    }
    // public function drop() {
        // DB::rawQuery('drop table devices');
        // unlink(__DIR__.'/../storage/cache/blueprints/table_devices.php.cache');
        // print("devices tablosu silindi!");
        // exit;
    // }

    private function getDeviceFromUUID(&$deviceRec, $deviceUUID) {
        if(empty($deviceUUID) || is_null($deviceUUID)) {
            $deviceRec = null;
            return false;
        }
        $qry = DB::table('devices')->select([
            'where' => [
                ['uuid', '=', $deviceUUID],
            ],
        ]);
        if(count($qry) > 0) {
            $deviceRec = $qry[0];
            return true;
        }
        return false;
    }

    public function list() {
        $res = $qry = DB::table('devices')->select([]);
        Response::success($res);
    }

    public function connection() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $uuid = Request::post('uuid', Request::get('uuid', ''));
        $screenWidth = Request::post('screenWidth', Request::get('screenWidth', 0));
        $screenHeight = Request::post('screenHeight', Request::get('screenHeight', 0));
        $screenPixelRatio = Request::post('screenPixelRatio', Request::get('screenPixelRatio', 1));
        if($screenWidth == 0 || $screenHeight == 0) {
            Response::error(1, 'Invalid screen');
        }
        if(!$this->getDeviceFromUUID($res ,$uuid)) {
            $res = [];
            //Yeni cihaz bağlantısı alındı
            //ip adresine göre daha önce kayıt edilmiş mi?
            $qry = DB::table('devices')->select([
                'where' => [
                    ['ip', '=', $ip],
                ],
            ]);    
            if(count($qry) > 0) {
                $res = $qry[0];
            } else {
                //Bu yeni bir cihaz
                //Yeni cihaz için UUID üret
                do {
                    $uuid = App::generateGUID();
                    $qry = DB::table('devices')->select([
                        'where' => [
                            ['uuid', '=', $uuid],
                        ],
                    ]);    
                } while(count($qry) > 0);
                $res['uuid'] = $uuid;
            }
        }
        $res['ip'] = $ip;
        $res['dtContact'] = date("Y-m-d H:i:s");
        if($screenWidth > 0)$res['screenWidth'] = $screenWidth;
        if($screenHeight > 0)$res['screenHeight'] = $screenHeight;
        if($screenPixelRatio > 0)$res['screenPixelRatio'] = $screenPixelRatio;
        $rec = DB::table('devices')->insertOrUpdate($res, [], true);
        if($rec != false) {
            Response::success($rec);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }
    
    public function save() {
        $this->checkToken(true);
        $received = $_POST;
        if($this->getDeviceFromUUID($rec, $received['uuid'])) {
            $rec['dtDelete'] = null;
            $received = array_merge($rec, DB::table('devices')->prepareReceivedRecord($received));
        }
        $classroom = DB::table('devices')->insertOrUpdate($received, [], true);
        if($classroom != false) {
            Response::success($classroom);
        }
        Response::error(7, 'Veri kaydedilemedi');
    }    
    
    public function getDesigns() {
        $uuid = Request::post('uuid', Request::get('uuid', ''));
        if($uuid == '') {
            Response::error(1, 'uuid değeri eksik');
        }
        if(!$this->getDeviceFromUUID($device, $uuid)) {
            Response::error(2, 'uuid değerine sahip bir cihaz bulunamadı');
        }
        $wheres = [];
        //$wheres[] = ['allDevices', '=', 1];
        if($device['screenWidth'] > $device['screenHeight']) {
            $wheres[] = ['designerWidth', '>', "$[designerHeight]"]; //tablo alanının sql değerini kullanmak için $[] yazmalıyız
        } else {
            $wheres[] = ['designerWidth', '<', "$[designerHeight]"]; //tablo alanının sql değerini kullanmak için $[] yazmalıyız
        }
        $designs = DB::table('device_designs')->select([
            'where' => $wheres,
        ]);
        Response::success($designs);
    }
    
}