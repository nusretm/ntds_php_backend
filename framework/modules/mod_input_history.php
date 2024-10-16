<?php

class ModInputHistory extends AppModuleBase {
    public static function list(String $owner, String $name) {
        return DB::table('input_history')->select([ 
            'where' => [
                ['owner', '=', $owner],
                ['name', '=', $name],
            ],
         ]);
    }

    public static function save(String $owner, String $name, String $value) {
        return DB::table('input_history')->insertOrUpdate(
            [ 
                'owner' => $owner, 
                'name' => $name, 
                'value' => $value, 
            ], 
            [ 
                ['owner', '=', $owner], 
                ['name', '=', $name], 
                ['value', '=', $value ],
            ]
        );
    }

    public static function delete(String $owner, String $name, String $value) {
        /*
        return DB::table('input_history')->delete(
            [ 
                ['owner', '=', $owner], 
                ['name', '=', $name], 
                ['value', '=', $value ],
            ]
        );
        */
    }
}