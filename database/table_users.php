<?php
class tableUsers extends dbTable {
    public function name() {
        return 'users';
    }

    public function fields() {
        $fields = [
            ['name' => 'id'         , 'type' => DB::FieldTypeAutoInc   ],
            ['name' => 'isAdmin'    , 'type' => DB::FieldTypeBoolean   , 'default' => false],
            ['name' => 'active'     , 'type' => DB::FieldTypeBoolean   , 'default' => true],
            ['name' => 'title'      , 'type' => DB::FieldTypeString    , 'size' => 200],
            ['name' => 'firstName'  , 'type' => DB::FieldTypeString    , 'required' => true, 'size' => 30],
            ['name' => 'lastName'   , 'type' => DB::FieldTypeString    , 'required' => true, 'size' => 30],
            ['name' => 'email'      , 'type' => DB::FieldTypeEMail     , 'required' => true, 'unique' => true],
            ['name' => 'password'   , 'type' => DB::FieldTypePassword  , 'required' => true],
            ['name' => 'token'      , 'type' => DB::FieldTypeUUID      , 'fillable' => false],
            ['name' => 'dtLogin'    , 'type' => DB::FieldTypeDateTime  , 'fillable' => false],
        ];
        return array_merge($fields, DB::recordTimeFields());
    }

    public function records() {
        $recs = array();
        $recs[] = [
            'isAdmin' => true,
            'title' => 'Software Developer',
            'firstName' => 'System',
            'lastName' => 'Admin',
            'email' => 'admin@ntds.com',
            'password' => App::passwordHash('black**1'),
        ];
        return $recs;
    }
}