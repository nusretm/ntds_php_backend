<?php
class tableInputHistory extends dbTable {
    public function name() {
        return 'input_history';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'         , 'type' => DB::FieldTypeAutoInc    ],
            ['name' => 'owner'      , 'type' => DB::FieldTypeString     , 'required' => true, 'size' => 30, 'index' => true],
            ['name' => 'name'       , 'type' => DB::FieldTypeString     , 'required' => true, 'size' => 30],
            ['name' => 'value'      , 'type' => DB::FieldTypeString     , 'required' => true],
        ];
        return $fields;
    }
}