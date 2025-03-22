<?php
class Update extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function check() {
        $receivedVersion = Request::post('version', Request::get('version', ''));
        $ntdsUIVersionZipFilename = FOLDER_ROOT.FOLDER_STORAGE.'update/ntds-ui.zip';
        $ntdsUIVersionFilename = FOLDER_ROOT.FOLDER_STORAGE.'update/ntds-ui.version';
        $ntdsUIVersion = '';
        if(file_exists($ntdsUIVersionFilename)) {
            $ntdsUIVersion = file_get_contents($ntdsUIVersionFilename);
        }
        $res = [
            'available' => false,
            'version' => $ntdsUIVersion,
        ];
        if(file_exists($ntdsUIVersionZipFilename) && $ntdsUIVersion != '' && $ntdsUIVersion != $receivedVersion) {
            $res['available'] = true;
        }
        Response::success($res);
    }
}