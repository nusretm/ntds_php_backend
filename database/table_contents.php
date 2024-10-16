<?php
class tableContents extends dbTable {
    public function name() {
        return 'contents';
    }

    public function fields() {
        /*
        timing
        ================================================================
            0-Her gün
            1-Tarih aralığı
            2-Haftanın günleri
        ----------------------------------------------------------------
        info
        ================================================================
            Kayıt sorasında API tarafından oluşturulacak.
            timing'e göre bir metin oluşturulacak:
                - Sürekli
                - 22.04.2024 - 23.04.2024
                - 12:15 - 13:30
                - Pazartesi, Cuma
        ----------------------------------------------------------------
        */
        $fields = [
            ['name' => 'id'             , 'type' => DB::FieldTypeAutoInc ],
            ['name' => 'active'         , 'type' => DB::FieldTypeBoolean , 'default' => true],
            ['name' => 'priority'       , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'allDevices'     , 'type' => DB::FieldTypeBoolean , 'default' => true],
            ['name' => 'name'           , 'type' => DB::FieldTypeString  , 'required' => true],
            ['name' => 'info'           , 'type' => DB::FieldTypeString  ],
            ['name' => 'timing'         , 'type' => DB::FieldTypeInt     , 'default' => 0],
            ['name' => 'totalDuration'  , 'type' => DB::FieldTypeInt     ],
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