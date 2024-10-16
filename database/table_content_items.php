<?php
class tableContentItems extends dbTable {
    public function name() {
        return 'content_items';
    }

    public function fields() {
        /*
        itemType -> 
            0-Image
            1-Video
            2-Günlük Ders Programı
            3-Haftalık Ders Programı
            4-Katılımcı Listesi
        */
        $fields = [
            ['name' => 'id'             , 'type' => DB::FieldTypeAutoInc ],
            ['name' => 'idContent'      , 'type' => DB::FieldTypeInt     , 'required' => true],
            ['name' => 'active'         , 'type' => DB::FieldTypeBoolean , 'default' => true],
            ['name' => 'priority'       , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'itemType'       , 'type' => DB::FieldTypeInt     ],
            ['name' => 'content'        , 'type' => DB::FieldTypeString  ],
            ['name' => 'duration'       , 'type' => DB::FieldTypeInt     ],
            ['name' => 'info'           , 'type' => DB::FieldTypeString  ],
            ['name' => 'properties'     , 'type' => DB::FieldTypeString  ],
            ['name' => 'orginalWidth'   , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'orginalHeight'  , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'orginalDuration', 'type' => DB::FieldTypeInt     , 'default' => 0],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

}