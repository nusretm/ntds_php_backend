<?php
class tableDevices extends dbTable {
    public function name() {
        return 'devices';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'              , 'type' => DB::FieldTypeAutoInc    ],
            ['name' => 'isChanged'       , 'type' => DB::FieldTypeBoolean    , 'fillable' => false, 'default' => true],
            ['name' => 'classroomId'     , 'type' => DB::FieldTypeInt        ],
            ['name' => 'uuid'            , 'type' => DB::FieldTypeUUID       , 'required' => true, 'fillable' => false],
            ['name' => 'screenWidth'     , 'type' => DB::FieldTypeInt        ],
            ['name' => 'screenHeight'    , 'type' => DB::FieldTypeInt        ],
            ['name' => 'screenPixelRatio', 'type' => DB::FieldTypeDouble     ],
            ['name' => 'hardwareId'      , 'type' => DB::FieldTypeString     ],
            ['name' => 'title'           , 'type' => DB::FieldTypeString     ],
            ['name' => 'description'     , 'type' => DB::FieldTypeString     ],
            ['name' => 'location'        , 'type' => DB::FieldTypeString     ],
            ['name' => 'floor'           , 'type' => DB::FieldTypeString     ],
            ['name' => 'ip'              , 'type' => DB::FieldTypeString     , 'fillable' => false],
            ['name' => 'dtContact'       , 'type' => DB::FieldTypeDateTime   , 'fillable' => false],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

}