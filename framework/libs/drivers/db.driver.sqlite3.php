<?php
class dbDriverSQLITE3 extends dbBASE {
	function connect(){
        try {
            $dbFolder = FOLDER_ROOT.FOLDER_STORAGE.'database/';
            $dbFilename = $this->config['database'].".sqlite3";
            if(!is_dir($dbFolder)) {
                mkdir($dbFolder, 0755, true); 
            }
            $this->conn = new PDO("sqlite:".$dbFolder.$dbFilename);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch( PDOException $e ) {
            $this->error($e, "Database connection error");
        }
	}

    public function tableExists($tableName) {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        $res = $this->rawQuery($sql, [$tableName]);
        return count($res) > 0;
    }

    public function tableCreate($tableBlueprint) {
        $sql = "CREATE TABLE ".$tableBlueprint->name()." (\n";
        foreach($tableBlueprint->fields() as $field) {
            $sql.= "    ".$field['name']." ";
            switch($field['type']) {
                case DB::FieldTypeAutoInc:
                    $sql.= "INTEGER PRIMARY KEY AUTOINCREMENT";
                    break;
                case DB::FieldTypeDouble:
                    $sql.= "REAL";
                    if(isset($field['required']) && $field['required']) {
                        $sql.= " NOT NULL";
                    }
                    if(isset($field['default'])) {
                        $sql.= " DEFAULT ".intval($field['default']);
                    }
                    break;
                case DB::FieldTypeInt:
                    $sql.= "INTEGER";
                    if(isset($field['required']) && $field['required']) {
                        $sql.= " NOT NULL";
                    }
                    if(isset($field['default'])) {
                        $sql.= " DEFAULT ".intval($field['default']);
                    }
                    break;
                case DB::FieldTypeBoolean:
                    $sql.= "BOOLEAN";
                    if(isset($field['required']) && $field['required']) {
                        $sql.= " NOT NULL";
                    }
                    if(isset($field['default'])) {
                        $sql.= " DEFAULT ".intval($field['default']);
                    }
                    break;
                case DB::FieldTypeDate:
                case DB::FieldTypeTime:
                case DB::FieldTypeDateTime:
                    $sql.= "DATETIME";
                    if(isset($field['required']) && $field['required']) {
                        $sql.= " NOT NULL";
                    }
                    break;
                case DB::FieldTypeString:
                case DB::FieldTypeEMail:
                case DB::FieldTypePassword:
                case DB::FieldTypeUUID:
                    $sql.= "TEXT";
                    if(isset($field['size'])) {
                        $sql.= "(".$field['size'].")";
                    }
                    if(isset($field['required']) && $field['required']) {
                        $sql.= " NOT NULL";
                    }
                    if(isset($field['default'])) {
                        $sql.= " DEFAULT '".$field['default']."'";
                    }
                    if( ($field['type'] == DB::FieldTypeUUID) || (isset($field['unique']) && $field['unique']) ) {
                        $sql.= " UNIQUE";
                    }
                    break;
                default:
                    $this->error(null, "Invalid field type: ".$field['type']);
            }
            $sql.= ",\n";
        }
        $sql = rtrim($sql, ",\n");
        $sql.= "\n);\n";
        $this->rawQuery($sql);
        if(method_exists($tableBlueprint, 'records')) {
            foreach($tableBlueprint->records() as $record) {
                $tableBlueprint->insert($record);
            }
        }
        return true;
    }
}