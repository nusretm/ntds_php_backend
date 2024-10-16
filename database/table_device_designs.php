<?php
class tableDeviceDesigns extends dbTable {
    public function name() {
        return 'device_designs';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'             , 'type' => DB::FieldTypeAutoInc ],
            ['name' => 'active'         , 'type' => DB::FieldTypeBoolean ],
            ['name' => 'priority'       , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'allDevices'     , 'type' => DB::FieldTypeBoolean , 'default' => true],
            ['name' => 'name'           , 'type' => DB::FieldTypeString  , 'required' => true],
            ['name' => 'info'           , 'type' => DB::FieldTypeString  ],
            ['name' => 'designerWidth'  , 'type' => DB::FieldTypeInt     , 'default' => 1366],
            ['name' => 'designerHeight' , 'type' => DB::FieldTypeInt     , 'default' => 768],
            ['name' => 'timing'         , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'dtStart'        , 'type' => DB::FieldTypeDate    ],
            ['name' => 'dtEnd'          , 'type' => DB::FieldTypeDate    ],
            ['name' => 'tmStartHour'    , 'type' => DB::FieldTypeInt     ],
            ['name' => 'tmStartMin'     , 'type' => DB::FieldTypeInt     ],
            ['name' => 'tmEndHour'      , 'type' => DB::FieldTypeInt     ],
            ['name' => 'tmEndMin'       , 'type' => DB::FieldTypeInt     ],
            ['name' => 'daysOfWeek'     , 'type' => DB::FieldTypeString  ],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

}