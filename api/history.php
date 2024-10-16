<?php
class History extends ApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function list() {
        /*
        curl --location 'http://netteamdigitalsignage.localhost/history/list?=' \
        --form 'owner="lessons"' \
        --form 'name="teacher"'
        */
        $res = ModInputHistory::list(Request::post('owner', ''), Request::post('name', ''));
        Response::success($res);
    }
}