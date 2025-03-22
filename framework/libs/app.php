<?php

function dd($vars) {
    ob_clean();
    ob_flush();
    ob_start();
    print "<pre>";
    print json_encode($vars, JSON_PRETTY_PRINT);
    print "</pre>";
    exit;
}

if(!function_exists('json_validate')) {
    function json_validate($data) {
        if (!empty($data)) { 
            return 
                is_string($data) &&  
                is_array(json_decode($data, true)) ? true : false
            ; 
        } 
        return false; 
    } 
}

class App {
    private static $modulesNames=[], $apiItems=[];

    public static function init() {
        require_once __DIR__.'/../config.php';
        require_once __DIR__.'/php-image-resize/ImageResizeExeption.php';
        require_once __DIR__.'/php-image-resize/ImageResize.php';
        require_once __DIR__.'/request.php';
        require_once __DIR__.'/response.php';
        require_once __DIR__.'/db.php';
        require_once __DIR__.'/db_table.php';
        require_once __DIR__.'/app_module.php';
        require_once __DIR__.'/api_module.php';
        
        DB::connect();
        self::checkRequest();
        self::loadModules();
    }

    public static function checkLogin() {
        /*
        $token = Request::post('token', false);
        if(!$token)
            Response::error(1, 'invalid_token');
        $db = new ClassDatabase();
        $qry = $db->query("select * from users where [token]=':token'", ['token' => $token]);
        if(!$qry) {
            Response::error(1, 'invalid_token');
        }
        return $qry[0];
        */
    }

    public static function checkRequest() {
    }

    public static function scandir($dir) {
        if(!is_dir($dir)) {
            return array();
        }
        $ignored = array('.', '..', '.svn', '.htaccess');
    
        $fileList = scandir($dir);
        $files = array();    
        foreach ($fileList as $file) {
            if (in_array($file, $ignored)) continue;
            $files[] = self::fileOrFolderInfo($dir . '/' . $file);
        }
    
    
        return $files;
    }

    public static function fileOrFolderInfo($fileOrFolder) {
        if(is_file($fileOrFolder) || is_dir($fileOrFolder)) {
            return [
                'name' => basename($fileOrFolder),
                'ext' => pathinfo($fileOrFolder, PATHINFO_EXTENSION),
                'isFolder' => is_dir($fileOrFolder),
                'size' => filesize($fileOrFolder),
                'mtime' => filemtime($fileOrFolder),
                'ctime' => filectime($fileOrFolder),
            ];
        }
        return false;
    }

    public static function generateGUID() {
        $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }    

    public static function httpPost(string $url, Array $data) {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data) );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Ignore SSL Certificate errors
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $html = curl_exec($ch);
        if (curl_errno($ch)) {
            $html = 'ERROR: ' . curl_error($ch);
        }
        
        curl_close ($ch);
    
        return $html;
    }

    private static function loadModules() {
        $modulesFolder = realpath(__DIR__.'/../../modules');
        $files = App::scandir($modulesFolder);
        foreach($files as $file) {
            if( (!$file['isFolder']) && ($file['ext'] == 'php') && (substr($file['name'], 0, 4) == 'mod_')) {
                $tmp = explode('_', substr($file['name'], 0, strlen($file['name'])-strlen($file['ext'])-1));
                $className = '';
                if(count($tmp) > 0) {
                    foreach($tmp as $name) {
                        if($className == '') {
                            $className = ucfirst($tmp[0]);
                        } else {
                            $className.= ucfirst($name);
                        }
                    }
                    self::$modulesNames[] = $className;
                }
                require_once($modulesFolder."/".$file['name']);
            }
        }
    }

    public static function moduleNames() {
        return self::$modulesNames;
    }

    public static function apiList() {
        if(count(self::$apiItems) == 0) {
            $files = App::scandir(FOLDER_ROOT.FOLDER_API);
            self::$apiItems = [];
            foreach($files as $file) {
                if($file['ext'] == 'php') {
                    self::$apiItems[] = str_replace('.php', '', $file['name']);
                }
            }
        }
        return self::$apiItems;
    }

    public static function uriList() {
        $uri = ltrim(rtrim($_SERVER['REQUEST_URI'], '/'), '/');
        if(strpos($uri, '?') !== false){
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $uriList = explode('/', $uri);
        if(count($uriList) == 0) {
            Response::error(1, 'Invalid request');
        }
        if(count($uriList) == 2) {
            $uriList[] = 'index';
        }
        return $uriList;
    }

    public static function baseURL() {
        $uri = ltrim(rtrim($_SERVER['REQUEST_URI'], '/'), '/');
        $uri = explode('/', $uri);
        return '/'.$uri[0];
    }

    public static function run() {
        $sleepSecs = Request::post('sleep', Request::get('sleep', false));
        if($sleepSecs !== false) {
            $sleepSecs = intval($sleepSecs);
            if($sleepSecs > 0) {
                sleep($sleepSecs);
            }
        }
        $uriList = self::uriList();
        if(($uriList[0] == URI_API) && (count($uriList) > 2)) {
            $apiModuleName = $uriList[1];
            if(count($uriList) > 2) {
                $apiModuleFunctionName = $uriList[2];
            } else {
                $apiModuleFunctionName = 'run';
            }
            //var_dump($apiModuleName, $apiModuleFunctionName, $apiModuleParams); exit;
            $apiModule = self::loadAPI($apiModuleName);
            if($apiModule !== false) {
                $apiModule->run($apiModuleFunctionName);
            } else {
            }    
        } elseif($uriList[0] == URI_API_BROWSER && in_array($_SERVER['REMOTE_ADDR'], TRUSTED_IP_LIST)) {
            //header("Location: /admin");
            $data = [
                'navbar' => [
                    'modules' => ['icon' => 'api', 'text' => 'Modules', 'menu' => [
                    ]],
                    'db' => ['icon' => 'table', 'text' => 'Database', 'menu' => [
                    ]],
                ],
            ];
            $activeNavbarName = '';
            $uriList = self::uriList();
            foreach($data['navbar'] as $name => &$item) {
                if( (count($uriList) > 1) && ($uriList[1] == $name)) {
                    $activeNavbarName = $name;
                }
                $item['name'] = $name;
                $item['link'] = '/'.URI_API_BROWSER.'/'.$name;
                $item['active'] = $name == $activeNavbarName;
            }
            if($activeNavbarName == '') {
                $firstItemName = array_key_first($data['navbar']);
                $activeNavbarName = $firstItemName;
                $data['navbar'][$firstItemName]['active'] = true;
                unset($firstItemName);
            }

            /* Modules Menu */
            $activeModuleName = '';
            foreach(App::apiList() as $api) {
                if( ($activeNavbarName == 'modules') && (count($uriList) > 2) && ($uriList[2] == $api)) {
                    $activeModuleName = $api;
                }
                $data['navbar']['modules']['menu'][$api] = [
                    'name' => $api,
                    'text' => ucfirst($api),
                    'link' => "/".URI_API_BROWSER."/modules/$api",
                    'active' => $api == $activeModuleName,
                ];
            }
            if($activeModuleName == '') {
                $firstItemName = array_key_first($data['navbar']['modules']['menu']);
                $activeModuleName = $firstItemName;
                $data['navbar']['modules']['menu'][$firstItemName]['active'] = true;
                unset($firstItemName);
            }
            if($activeNavbarName == 'modules') {
                $activeMenuName = $activeModuleName;
            }
            
            /* Database Menu */
            $activeTableName = '';
            foreach(DB::tableNames() as $tableName) {
                if( ($activeNavbarName == 'db') && (count($uriList) > 2) && ($uriList[2] == $tableName)) {
                    $activeTableName = $tableName;
                }
                $data['navbar']['db']['menu'][$tableName] = [
                    'name' => $tableName,
                    'text' => $tableName,
                    'link' => "/".URI_API_BROWSER."/db/$tableName",
                    'active' => $tableName == $activeTableName,
                ];
            }
            if($activeTableName == '') {
                $firstItemName = array_key_first($data['navbar']['db']['menu']);
                $activeTableName = $firstItemName;
                $data['navbar']['db']['menu'][$firstItemName]['active'] = true;
                unset($firstItemName);
            }
            if($activeNavbarName == 'db') {
                $activeMenuName = $activeTableName;
            }
            include(FOLDER_ROOT.FOLDER_THEMES.Config::$themeName."/index.php");
            exit;
        }

        include(FOLDER_ROOT.FOLDER_PUBLIC.'/index.html');
    }

    public static function loadAPI(String $apiModuleName) {
        try{
            $apiModuleFilename = FOLDER_ROOT.FOLDER_API.$apiModuleName.'.php';
            if(!file_exists($apiModuleFilename)) {
                Response::error(4, "api_not_found ($apiModuleName)");
            }
            require_once($apiModuleFilename);
            $apiModuleClassName = ucfirst($apiModuleName);
            return new $apiModuleClassName();
        } catch(\Exception $e) {
            Response::error(7, $e->getMessage());
        }
    }


    public static function passwordHash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}