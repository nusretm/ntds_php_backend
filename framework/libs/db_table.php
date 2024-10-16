<?php

class dbTable {
    
    public function __construct()
    {
    }

    public function name() { 
        return '';
    }

    public function idFieldName() { 
        foreach ($this->fields() as $field) {
            if($field['type'] == DB::FieldTypeAutoInc) {
                return $field['name'];
            }
        }
        return false;
    }

    public function fieldExists($fieldName) { 
        foreach ($this->fields() as $field) {
            if($field['name'] == $fieldName) {
                return true;
            }
        }
        return false;
    }

    public function extractRowData($receivedData) {
        $res = array();
        foreach($receivedData as $key => $value) {
            if($this->fieldExists($key)) {
                $res[$key] = $value;
            }
        }
        return $res;
    }

    public function select(Array $params=null) {
        /*
        $this->select([
            'where' => [
                ['id', '=', 1],
                ['active', '<>', false],
            ],
            'order' => [
                ['dtCreate', 'desc'],
                ['name', 'asc'],
            ],
            'columns' => [
                'id', 
                'name', 
                'active',
            ],
            'limit' => 10,
            'offset' => 0,
        ]);
        */
        return DB::select($this->name(), $params);
    }

    public function selectFromIdField($idFieldValue) {
        $rec = DB::select($this->name(), [
            'where' => [
                [$this->idFieldName(), '=', $idFieldValue],
            ],
        ]);
        if(count($rec) > 0) {
            return $rec[0];
        }
        return false;
    }

    public function fields() {
        return [];
    }

    public function requiredFields() {
        $res = [];
        foreach ($this->fields() as $field) {
            if(isset($field['required']) && $field['required']) {
                $res[] = $field;
            }
        }
        return $res;
    }

    public function uniqueFields() {
        $res = [];
        foreach ($this->fields() as $field) {
            if(isset($field['unique']) && $field['unique']) {
                $res[] = $field;
            }
        }
        return $res;
    }

    private function checkFieldValues(Array $data) {
        $idFieldName = $this->idFieldName();
        foreach($this->requiredFields() as $field) {
            if(!isset($data[$field['name']]) || (isset($data[$field['name']]) && $data[$field['name']] == '')) {
                Response::error(1, 'Required field is empty ('.$field['name'].')');
            }
        }
        foreach($this->uniqueFields() as $field) {
            if(isset($data[$field['name']])) {
                $rec = $this->select([
                    'where' => [
                        [$field['name'], '=', $data[$field['name']]],
                    ],
                ]);
                if( (count($rec) > 0) && ( (!isset($data[$idFieldName])) || (isset($data[$idFieldName]) && ($data[$idFieldName] != $rec[0]['id']) ) ) ) {
                    Response::error(1, '\''.$data[$field['name']].'\' değeri kayıtlarda zaten mevcut');
                }
            }
        }
    }

    public function fieldDefs() {
        $fieldDefs = [];
        foreach($this->fields() as $field) {
            $fieldDefs[$field['name']] = $field;
        }
        return $fieldDefs;
    }

    public function prepareReceivedRecord($rec) {
        $fieldDefs = $this->fieldDefs();
        $idFieldName = $this->idFieldName();
        $res = [];
        foreach($rec as $key => $value) {
            if(
                isset($fieldDefs[$key]) 
                && ( (!isset($fieldDefs[$key]['fillable'])) || (isset($fieldDefs[$key]['fillable']) && $fieldDefs[$key]['fillable']) )
                && ( ($key != $idFieldName) || (($key == $idFieldName)&&($value > 0)) )
                && ( ($fieldDefs[$key]['type'] != DB::FieldTypePassword) || (($fieldDefs[$key]['type'] == DB::FieldTypePassword)&&($value != '')&&($value != null)) )
            ) {
                $res[$key] = $value;
            }            
        }
        return $res;
    }

    public function insert(Array $data, bool $getRecord=false) {
        $data = $this->extractRowData($data);
        $this->checkFieldValues($data);
        if($this->fieldExists('dtCreate')) {
            $data['dtCreate'] = date("Y-m-d H:i:s");
        }
        $idFieldName = $this->idFieldName();
        if( ($idFieldName != false) && (isset($data[$idFieldName])) && ($data[$idFieldName] < 1) ) {
            unset($data[$idFieldName]);
        }
        $res = DB::insert($this->name(), $data, $getRecord);
        return $res;
    }

    public function update(Array $data, Array $wheres=null, bool $getRecord=false) {
        $data = $this->extractRowData($data);
        $rec = $this->select([
            'where' => [
                [$this->idFieldName(), '=', $data[$this->idFieldName()]],
            ],
        ]);
        $rec = $rec[0];
        $isChanged = false;
        foreach($rec as $key => $value) {
            if(isset($data[$key])) {
                if($data[$key] != $value) {
                    $isChanged = true;
                }
            } else 
            if($key == 'dtDelete') {
                if(isset($data[$key]) && ($data[$key] != $value)) {
                    $isChanged = true;
                }
            } else {
                $data[$key] = $value;
            }
        }
        $this->checkFieldValues($data);
        
        if(!$isChanged) {
            if($getRecord) {
                return $rec;
            }
            return true;
        }
        if($this->fieldExists('dtUpdate') && (!isset($data['dtDelete']) || ( isset($data['dtDelete']) && ($data['dtDelete'] == null) )) ) {
            $data['dtUpdate'] = date("Y-m-d H:i:s");
        }
        if(is_null($wheres) || (count($wheres) == 0)) {
            $wheres = [ [$this->idFieldName(), '=', $data[$this->idFieldName()]] ];
        }
        $res = DB::update($this->name(), $data, $wheres, $getRecord);
        if($getRecord && (count($res)>0)) {
            $res = $res[0];
        }
        return $res;
    }

    public function delete(Array $data, Array $wheres=null, bool $getRecord=false) {
        $data = $this->extractRowData($data);
        if( isset($data[$this->idFieldName()]) && (is_null($wheres) || ( (!is_null($wheres)) && (count($wheres) == 0) )) ) {
            $wheres = [ [$this->idFieldName(), '=', $data[$this->idFieldName()]] ];
        }
        $rec = $this->select(['where' => $wheres]);
        if(count($rec) > 0) {
            $rec = $rec[0];
            foreach($rec as $key => $value) {
                if(!isset($data[$key])) {
                    $data[$key] = $value;
                }
            }
            if(isset($rec['dtDelete'])) {
                if($rec['dtDelete'] != null) {
                    return $rec;
                }
                $data['dtDelete'] = date("Y-m-d H:i:s");
                return $this->update($data, $wheres, $getRecord);
            } else {
                DB::rawQuery("DELETE FROM ".$this->name()." WHERE ".$this->idFieldName()." = ".$rec[$this->idFieldName()]);
                return $rec;
            }
        }
        return false;
    }

    public function insertOrUpdate(Array $data, Array $wheres, bool $getRecord=false) {
        $idFieldName = $this->idFieldName();
        if( ($idFieldName != false) && (isset($data[$idFieldName])) && ($data[$idFieldName] > 0) ) {
            $rec = $this->selectFromIdField($data[$idFieldName]);
            if($rec != false) {
                $wheres = [ [$idFieldName, '=', $data[$idFieldName]] ];
                return $this->update($data, $wheres, $getRecord);
            }
        }
        return $this->insert($data, $getRecord);
    }

    public function generateRowData() {
        $res = [];
        foreach($this->fields() as $field) {
            $value = '';
            switch($field['type']) {
                case DB::FieldTypeAutoInc:
                case DB::FieldTypeInt:
                    $value = 0.0;
                    break;
                case DB::FieldTypeDouble:
                    $value = 0.1;
                    break;
                case DB::FieldTypeBoolean:
                    $value = true;
                    break;
                case DB::FieldTypeDate:
                    $value = date('Y-m-d');
                    break;
                case DB::FieldTypeDateTime:
                    $value = date('Y-m-d H:i:s');
                    break;
                case DB::FieldTypeTime:
                    $value = date('H:i:s');
                    break;
            }
            $res[$field['name']] = $value;
        }
        return $res;
    }

    public function generateDartFileName() {
        $className = $this->name();
        if(substr($className, strlen($className)-1, 1) == 's') {
            $className = substr($className, 0, strlen($className)-1);
        }
        return $className;
    }

    public function generateDartClassName() {
        $className = '';
        $tmp = explode('_', $this->name());
        foreach ($tmp as $name) {
            $className.=ucfirst($name);
        }
        if(substr($className, strlen($className)-1, 1) == 's') {
            $className = substr($className, 0, strlen($className)-1);
        }
        return $className;
    }

    public function generateDartClass() {
        $className = $this->generateDartClassName();
        $res = "import 'dart:convert';\n";
        $res.= "\n";
        $res.= "class $className {\n";

        $resConstructor = "";
        $resCopyWith = "";
        $resCopyWithBody = "    return $className(\n";
        $resToMap = "";
        $resFromMap = "";
        $resToString = "";
        $resIsEqual = "";
        $resHashCode = "";

        foreach($this->fields() as $field) {
            $res.= "  ";
            $fieldName = $field['name'];
            $isRequired = 
                (isset($field['required']) && $field['required'])
                ||
                ($field['type'] == 'autoinc')
            ;
            $dartType = "String";
            if($isRequired) $res.= "final ";
            switch($field['type']) {
                case DB::FieldTypeAutoInc:
                case DB::FieldTypeInt:
                    $dartType = "int";
                    break;
                case DB::FieldTypeDouble:
                    $dartType = "double";
                    break;
                case DB::FieldTypeBoolean:
                    $dartType = "bool";
                    break;
                /*
                case DB::FieldTypeDate:
                case DB::FieldTypeDateTime:
                case DB::FieldTypeTime:
                    $dartType = "DateTime";
                    break;
                */
            }
            $res.=$dartType;
            if($isRequired){
                $res.=" ";
            } else {
                $res.="? ";
            }
            $res.="$fieldName;";
            $res.="\n";

            $resConstructor.="    ";
            if($isRequired)$resConstructor.="required ";
            $resConstructor.="this.$fieldName,\n";

            $resCopyWith.="    $dartType? $fieldName,\n";
            $resCopyWithBody.="      $fieldName: $fieldName ?? this.$fieldName,\n";

            $resToMap.="      '$fieldName': $fieldName,\n";

            if($dartType == 'bool') {
                $resFromMap.="      $fieldName: map['$fieldName'] == 1,\n";
            } else {
                $resFromMap.="      $fieldName: map['$fieldName'],\n";
            }
            
            $resToString.="$fieldName: \$$fieldName, ";

            $resIsEqual.="      other.$fieldName == $fieldName &&\n";

            $resHashCode.="      $fieldName.hashCode ^\n";
        }

        $resToString = rtrim($resToString, ", ");
        $resIsEqual = rtrim($resIsEqual, "&&\n")."\n";
        $resHashCode = rtrim($resHashCode, "^\n")."\n";

        $res.= "\n  $className({\n$resConstructor  });\n";
        $res.= "\n  $className copyWith ({\n$resCopyWith  }) {\n$resCopyWithBody    );\n  }\n";
        $res.= "\n  Map<String, dynamic> toMap() {\n    return <String, dynamic>{\n$resToMap    };\n  }\n";
        $res.= "\n  factory $className.fromMap(Map<String, dynamic> map) {\n    return $className(\n$resFromMap    );\n  }\n";
        $res.= "\n  String toJson() => json.encode(toMap());\n";
        $res.= "\n  factory $className.fromJson(String source) => $className.fromMap(json.decode(source) as Map<String, dynamic>);\n";
        $res.= "\n  @override\n  String toString() {\n    return '$className($resToString)';\n  }\n";
        $res.= "\n  @override\n  bool operator ==(covariant $className other) {\n    if (identical(this, other)) return true;\n    return\n$resIsEqual    ;\n  }\n";
        $res.= "\n  @override\n  int get hashCode {\n    return\n$resHashCode    ;\n  }\n";
        $res.= "}";
        return $res;
    }

}