<?php
class tableParticipants extends dbTable {
    public function name() {
        return 'participants';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'             , 'type' => DB::FieldTypeAutoInc ],
            ['name' => 'idClassroom'    , 'type' => DB::FieldTypeInt     , 'required' => true],
            ['name' => 'classroomName'  , 'type' => DB::FieldTypeString  , 'required' => true],
            ['name' => 'dtBegin'        , 'type' => DB::FieldTypeDate    , 'required' => true],
            ['name' => 'dtEnd'          , 'type' => DB::FieldTypeDate    , 'required' => true],
            ['name' => 'itemCount'      , 'type' => DB::FieldTypeInt     , 'required' => true],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

}