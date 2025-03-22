<?php
require_once __DIR__.'/drivers/db.driver.base.php';
require_once __DIR__.'/drivers/db.driver.sqlsrv.php';
require_once __DIR__.'/drivers/db.driver.sqlite3.php';

class DB {
    const FieldTypeEMail = 'email';
    const FieldTypeUsername = 'username';
    const FieldTypePassword = 'password';
    const FieldTypeAutoInc = 'autoinc';
    const FieldTypeString = 'string';
    const FieldTypeBlob = 'blob';
    const FieldTypeBoolean = 'boolean';
    const FieldTypeInt = 'int';
    const FieldTypeDouble = 'double';
    const FieldTypeUUID = 'uuid';
    const FieldTypeDate = 'date';
    const FieldTypeTime = 'time';
    const FieldTypeDateTime = 'datetime';
    
    private static $driver, $tables = [];
    public static function params()
    {
        return ['driver' => Config::$dbDriver, 'database' => Config::$dbDatabase, 'username' => Config::$dbUsername, 'password' => Config::$dbPassword];
    }

    public static function recordTimeFields() {
        return [
            ['name' => 'dtCreate'  , 'type' => DB::FieldTypeDateTime, 'fillable' => false],
            ['name' => 'dtUpdate'  , 'type' => DB::FieldTypeDateTime, 'fillable' => false],
            ['name' => 'dtDelete'  , 'type' => DB::FieldTypeDateTime, 'fillable' => false],
        ];
    }

    public static function connect() {
        if(!isset(self::$driver)) {
            switch(self::params()['driver']) {
                case 'sqlsrv':
                    self::$driver = new dbDriverSQLSRV(self::params());
                case 'sqlite3':
                    self::$driver = new dbDriverSQLITE3(self::params());
            }
        }
        if(isset(self::$driver)) {
            self::loadDatabaseBlueprints();
            return true;
        }
        return false;
    }

    private static function loadDatabaseBlueprints() {
        $blueprintFolder = FOLDER_ROOT.FOLDER_DATABASE;
        $blueprintCacheFolder = FOLDER_ROOT.FOLDER_STORAGE.'cache/blueprints';
        if(!is_dir($blueprintCacheFolder)) {
            mkdir($blueprintCacheFolder, 0755, true); 
        }

        $files = App::scandir($blueprintFolder);
        foreach($files as $file) {
            if( (!$file['isFolder']) && ($file['ext'] == 'php') && (substr($file['name'], 0, 6) == 'table_')) {
                $tmp = explode('_', substr($file['name'], 0, strlen($file['name'])-strlen($file['ext'])-1));
                $className = '';
                if(count($tmp) > 0) {
                    foreach($tmp as $name) {
                        if($className == '') {
                            $className = $tmp[0];
                        } else {
                            $className.= ucfirst($name);
                        }
                    }
                }
                require_once($blueprintFolder.$file['name']);
                if(!method_exists($className, 'name')) Response::error(1000, "Table Blueprint Error: ".$file['name']." -> class ".$className);
                $tableBlueprint = new $className();
                self::$tables[$tableBlueprint->name()] = $tableBlueprint;

                $cacheFilename = $blueprintCacheFolder."/".$file['name'].".cache";
                if(file_exists($cacheFilename)) {
                    $oldFile = json_decode(file_get_contents($cacheFilename), true);
                    if(is_array($oldFile)) {
                        if( ($oldFile['size'] == $file['size']) || ($oldFile['mtime'] == $file['mtime']) ) {
                            continue;
                        }
                    }
                }
                if(self::tableExists($tableBlueprint->name())) {
                    //self::tableAlter($tableBlueprint);
                } else {
                    self::tableCreate($tableBlueprint);
                }
                file_put_contents($cacheFilename, json_encode($file, JSON_PRETTY_PRINT));
            }
        }
    }

    public static function tableNames() {
        return array_keys(DB::$tables);
    }

    public static function table($tableName) {
        if(isset(DB::$tables[$tableName])) {
            return DB::$tables[$tableName];
        }
        return false;
    }

    private static function tableExists($tableName) {
        return DB::$driver->tableExists($tableName);
    }

    private static function tableCreate($tableBlueprint) {
        return DB::$driver->tableCreate($tableBlueprint);
    }

    private static function tableAlter($tableBlueprint) {
        print 'Alter table '.$tableBlueprint->name()."<br/>";
        print "this function not implemented yet -> DB::tableAlter()<br/>";
        exit;
    }

    public static function clearQueryCache() {
    }

    public static function rawQuery($sql, $params=null) {
        return self::$driver->rawQuery($sql, $params);
    }

    public static function select(String $table, Array $params=null) {
        /*
        $this->select(
            'users', 
            [
                'where' => [
                    ['id', '=', 1],
                    ['active', '<>', false],
                ],
                'order' => [
                    ['dtCreate', 'desc'],
                    ['name', 'asc'],
                ],
                'columns' => [
                    'id', 
                    'name', 
                    'active',
                ],
                'limit' => 10,
                'offset' => 0,
            ]
        );
        */
        return self::$driver->select($table, $params);
    }
    
    public static function selectFromId(String $tableName, int $id, Array $columns=null){
        return self::$driver->selectFromId($tableName, $id, $columns);
    }
    
    public static function insert(String $tableName, Array $data, bool $getRecord=false) {
        return self::$driver->insert($tableName, $data, $getRecord);
    }
    
    public static function update(String $table, Array $data, Array $wheres, bool $getRecord=false) {
        return self::$driver->update($table, $data, $wheres, $getRecord);
    }

    public static function insertOrUpdate(String $table, Array $data, Array $wheres, bool $getRecord=false) {
        return self::$driver->insertOrUpdate($table, $data, $wheres, $getRecord);
    }
}