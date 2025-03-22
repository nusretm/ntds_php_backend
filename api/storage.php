<?php

use \Gumlet\ImageResize;

require_once(FOLDER_ROOT.FOLDER_FRAMEWORK.'libs/getID3-1.9.23/getid3/getid3.php');

class storage extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    private function storageURL() {
        return App::baseURL().'/'.FOLDER_STORAGE.'gallery';
    }

    private function storagePath() {
        return FOLDER_ROOT.FOLDER_STORAGE.'gallery/';
    }

    private function getMimeType($filename)
    {
        $mimetype = false;
        if(function_exists('mime_content_type')) {
           $mimetype = mime_content_type($filename);
        }
        return $mimetype;
    }
    
    public function list() {
        $expectedListTypes = [
            //'files',
            'images',
            'videos',
        ];
        $listTypes = Request::post('types', Request::get('types', ''));
        if($listTypes == '') {
            $listTypes = "images,videos";
        }
        $listTypes = explode(",", $listTypes);
        $res = [
            'diskFree' => floor(disk_free_space('/')),
            'diskSize' => floor(disk_total_space('/')),
        ];
        foreach($expectedListTypes as $listType) {
            $res[$listType."URL"] = $this->storageURL()."/$listType/";
            $res[$listType] = [];
            if(in_array($listType, $listTypes)){
                $fileList = App::scandir($this->storagePath().$listType);
                for($i=0; $i<count($fileList); $i++) {
                    $file = $fileList[$i];
                    if(pathinfo($file['name'], PATHINFO_EXTENSION) != 'info') {
                        $file['info'] = $file['name'];
                        $file['properties'] = '{}';
                        $infoFilename = $this->storagePath()."$listType/".$file['name'].".info";
                        if(file_exists($infoFilename)) {
                            $infoContent = file_get_contents($infoFilename);
                            if(json_validate($infoContent)) {
                                $file['properties'] = json_decode($infoContent, true);
                                $file['info'] = $file['properties']['orginalName'];
                            }
                        }
                        $res[$listType][] = $file;
                    }
                }
            }
        }
        Response::success($res);
    }

    public function get() {
        $params = App::uriList();
        array_splice($params, 0, 3);
        $filename = implode('/', $params);
        $filenameWithPath = FOLDER_ROOT.FOLDER_STORAGE.$filename;
        if(is_file($filenameWithPath)) {
            $fp = fopen($filenameWithPath, 'rb');
            $mimeType = 'application/octet-stream';
            header("Content-Type: $mimeType");
            header("Content-Length: " . filesize($filenameWithPath));
            fpassthru($fp);
            exit;
        }
        http_response_code(404);
    }
    
    public function gallery() {
        /* 
        for ImageResize:
        ----------------------------------------------------------------
            sudo apt-get install libgd3 php-gd

        for Video Thumbnails: https://github.com/PHP-FFMpeg/PHP-FFMpeg
        ----------------------------------------------------------------
            sudo apt update & sudo apt upgrade
            sudo apt-get install libav-tools
            sudo apt install nasm yasm
            sudo wget https://ffmpeg.org/releases/ffmpeg-6.1.1.tar.xz
            tar -xf ffmpeg-6.1.1.tar.xz
            sudo rm ffmpeg-6.1.1.tar.xz
            cd ffmpeg-6.1.1
            ./configure
            make
            make install

            Check installation:
            -------------------
            ffmpeg -version
            ls /usr/local/bin/ffmpeg

            Clear Files:
            -------------------
            cd ..
            rm -rf ffmpeg-6.1.1

        */
        $width = Request::post('width', Request::get('width', 0));
        $height = Request::post('height', Request::get('height', 0));
        $params = App::uriList();
        array_splice($params, 0, 3);
        $filename = implode('/', $params);
        $filenameWithPath = $this->storagePath().$filename;
        if(is_file($filenameWithPath)) {
            $mimeType = $this->getMimeType($filenameWithPath);
            if( ($width >0) || ($height >0) ) {
                if(strpos($mimeType, 'image/') !== false) {
                    $image = new ImageResize($filenameWithPath);
                    if( ($width >0) && ($height >0) ) {
                        $image->resizeToBestFit($width, $height);
                    } elseif($width >0) {
                        $image->resizeToWidth($width);
                    } elseif($height >0) {
                        $image->resizeToHeight($height);
                    }
                    $image->output();
                    exit;
                } elseif(strpos($mimeType, 'video/') !== false) {
                }
            }

            $fp = fopen($filenameWithPath, 'rb');
            if($mimeType === false) {
                /* binary */
                $mimeType = 'application/octet-stream';
            }
            header("Content-Type: $mimeType");
            header("Content-Length: " . filesize($filenameWithPath));
            fpassthru($fp);
            exit;
        }
        http_response_code(404);
        exit;
    }

    private function moveUploadedFile($fileObj, $filename='') {
        if($filename == '') $filename = $fileObj['tmp_name'];
        $mimeType = $this->getMimeType($filename);
        if(strpos($mimeType, 'image/') !== false) {
            $targetPath = $this->storagePath().'images/';
        } else {
            $targetPath = $this->storagePath().'videos/';
        }
        if(!is_dir($targetPath)){
            if(!mkdir($targetPath, 0755, true)) {
                Response::error(8, "Klasör ouşturulamadı (chown): \n".str_replace(dirname(__DIR__).'/', '', $targetPath));
            }        
        }
        $hash = hash_file('crc32b', $filename);
        $array = unpack('N', pack('H*', $hash));
        $fileHash = dechex($array[1]);
        $targetFilename = $fileHash.".".pathinfo($fileObj['name'], PATHINFO_EXTENSION);
        if(file_exists($targetPath.$targetFilename)) {
            unlink($filename);
        } else{
            if(!rename($filename, $targetPath.$targetFilename)) {
                $targetFilename = '';
                return '';
            }
        }
        $fileWidth = 0;
        $fileHeight = 0;
        $videoDuration = 0;
        if(strpos($mimeType, 'image/') !== false) {
            $imageSize = getimagesize($targetPath.$targetFilename);
            $fileWidth = $imageSize[0];
            $fileHeight = $imageSize[1];
        } else
        if(strpos($mimeType, 'video/') !== false) {
            $getID3 = new getID3;
            $videoFileInfo = $getID3->analyze($targetPath.$targetFilename);
            if(
                isset($videoFileInfo) 
                && isset($videoFileInfo["video"])
                && isset($videoFileInfo["video"]["resolution_x"])
                && isset($videoFileInfo["video"]["resolution_y"])
                && isset($videoFileInfo["playtime_seconds"])
            ) {
                $fileWidth = $videoFileInfo["video"]["resolution_x"];
                $fileHeight = $videoFileInfo["video"]["resolution_y"];
                $videoDuration = floor($videoFileInfo["playtime_seconds"]);
            }
        }
        $infoRec = [
            'name' => $targetFilename,
            'orginalName' => basename($fileObj['name']),
            'mimeType' => $mimeType,
            'width' => $fileWidth,
            'height' => $fileHeight,
            'videoDuration' => $videoDuration
        ];
        file_put_contents($targetPath.$targetFilename.".info", json_encode($infoRec, JSON_PRETTY_PRINT));
        return array_merge(App::fileOrFolderInfo($targetPath.$targetFilename), ['info' => $infoRec['orginalName'], 'properties' => $infoRec]);
    }

    public function upload() {
        //sleep(1);
        //http_response_code(409); exit;
        $tmpFolder = $this->storagePath().'tmp';
        if(!is_dir($tmpFolder)) {
            if(!mkdir($tmpFolder, 0755, true)) {
                Response::error(8, "Klasör ouşturulamadı (chown): \n".str_replace(dirname(__DIR__).'/', '', $tmpFolder));
            }
        }
        $headers = getallheaders();
        $content = [
            'get' => $_GET,
            'post' => $_POST,
            'headers' => $headers,
            'files' => $_FILES,
        ];
        $start = false;
        $end = false;
        $size = false;
        if (isset($headers['Content-Range'])) {
            $ranges = explode(',', substr($headers['Content-Range'], 6));
            foreach ($ranges as $range) {
                $parts = explode('-', $range);
                $start = $parts[0];
                $end = $parts[1];
                if(strpos($end, '/') !== false) {
                    $tmp = explode('/', $end);
                    $end = $tmp[0];
                    $size = $tmp[1];
                }
                if ($start > $end) {
                    $start = false;
                    $end = false;
                }
            }
        }
        $content['size'] = $size;
        $content['start'] = $start;
        $content['end'] = $end;
        if( ($start === false) & ($end === false) ) {
            /* upload files */
            $uploadedFiles = [];
            foreach($content['files'] as $file) {
                $uploadedFiles[] = $this->moveUploadedFile($file);
            }
            Response::json($uploadedFiles);
        } else {
            /* chunked file upload */
            $file = $content['files'][array_key_first($content['files'])];
            $filename = $file['name'];
            $tmpFilename = "$tmpFolder/$filename.tmp";
            $tmpContent = file_get_contents($file['tmp_name']);
            $f = fopen($tmpFilename, "c");
            fseek($f, $content['start']);
            fwrite($f, $tmpContent);
            fclose($f);
            if($content['end'] + 1 == $content['size']) {
                /* chunked file upload completed */
                $uploadedFile = $this->moveUploadedFile($file, $tmpFilename);
                Response::json($uploadedFile);
                print($newFilename);
            }
          
        }
        exit;
    }

    public function delete() {
        //$this->checkToken();
        $filePath = Request::post('path', Request::get('path', ''));
        $fileName = Request::post('name', Request::get('name', ''));
        if( ($filePath == '') && ($fileName == '' ) ) {
            Response::error(3, 'path ve name parametreleri eksik');
        }
        if($filePath == '') {
            Response::error(1, 'path parametresi eksik');
        }
        if($fileName == '') {
            Response::error(2, 'name parametresi eksik');
        }
        $filePath = $this->storagePath().rtrim($filePath).'/';
        if(!is_file($filePath.$fileName)) {
            Response::error(4, 'Dosya bulunamadı');
        }
        $qry = DB::table('content_items')->select([
            'where' => [
                ['properties', 'like', '%"name":"'.$fileName.'"%']
            ],
        ]);
        if(count($qry) > 0) {
            $contentNames = "";
            foreach($qry as $row) {
                $qryContent = DB::table('contents')->select([
                    'where' => [
                        ['id', '=', $row['idContent']],
                    ],
                ]);
                if(count($qryContent) > 0) {
                    $contentNames.=$qryContent[0]['name'].", ";
                }
            }
            $contentNames = rtrim($contentNames, ', ');
            Response::error(4, "İçerik olarak kullanılıyor.\n\nKullanıldığı İçerikler: \n".$contentNames);
        }
        if(unlink($filePath.$fileName)) {
            unlink($filePath.$fileName.".info");
            Response::success([]);
        } else {
            Response::error(5, 'Dosya silinemedi');
        }
    }
}