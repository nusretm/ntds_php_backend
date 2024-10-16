<?php
class tableClassrooms extends dbTable {
    public function name() {
        return 'classrooms';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'        , 'type' => DB::FieldTypeAutoInc ],
            ['name' => 'name'      , 'type' => DB::FieldTypeString  , 'unique' =>true, 'required' => true],
            ['name' => 'description', 'type' => DB::FieldTypeString],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

}