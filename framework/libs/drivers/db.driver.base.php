<?php
class dbBASE {
    public $config, $conn;

	function __construct($config){
        $this->config = $config;
        $this->connect();
	}

    function __destruct(){
		$this->conn = null;
	}

    function error($e, $msg) {
        if(empty($e)) {
            Response::error('dbfield', strtoupper($this->config['driver'])." $msg");
        } else {
            Response::error($e->getCode(), strtoupper($this->config['driver'])." $msg --> ".$e->getMessage());
        }
    }

    function connect() {}

	public function rawQuery($sql, $params=null){
        try {
            $qry = $this->conn->prepare($sql);
            $qry->execute($params);
            if($qry->rowCount() > 0){
                $result = true;
            } else {
                $result = $qry->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch( PDOException $e ) {
            $this->error($e, "rawQuery error");
        }
		return $result;
	}

    public function generateSqlWhere(Array $wheres=null){
        $res = ['sql' => '', 'data' => null];
        if( (!is_null($wheres)) && (count($wheres) > 0) ) {
            $res['data'] = [];
            $res['sql'] = "";
            if(count($wheres) > 0) {
                $res['sql'] = "WHERE \n";
                foreach($wheres as $where) {
                    if($where[1] == '!=') {
                        $where[1] = '<>';
                    }
                    if($where[2] == null) {
                        if($where[1] == '=') {
                            $res['sql'].= "\t".$where[0]." IS NULL \n";
                        } else {
                            $res['sql'].= "\t".$where[0]." IS NOT NULL \n";
                        }
                    } else {
                        $where2 = trim($where[2]);
                        if(substr($where2, 0, 2) == '$[' && substr($where2, strlen($where2)-1, 1) == ']') {
                            $res['sql'].= "\t".$where[0]." ".$where[1]." ".substr($where2, 2, strlen($where2)-3)." \n";
                        } else {
                            $res['data'][$where[0]] = $where[2];
                            $res['sql'].= "\t".$where[0]." ".$where[1]." :".$where[0]." \n";
                        }
                    }
                    $res['sql'].= " AND ";
                }
                $res['sql'] = rtrim($res['sql'], " AND ");
                $res['sql'].=" ";
            }
        }
        return $res;
    }

    public function generateSqlOrderBy(Array $orderBy=null){
        $res = '';
        if( (!is_null($orderBy)) && (count($orderBy) > 0)) {
            foreach($orderBy as $order) {
                $res.= "\t".$order[0]." ".$order[1].", \n";
            }
            $res = rtrim($res, ", \n");
            if($res != '') {
                $res = "ORDER BY \n$res \n";
            }
        }
        return $res;
    }

    public function select(String $table, Array $params=null){ 
        /*
        $this->select(
            'users', 
            [
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
            ]
        );
        */
        if(!isset($params['columns'])) {
            $params['columns'] = [];
        }
        if(!isset($params['where'])) {
            $params['where'] = [];
        }
        if(!isset($params['limit'])) {
            $params['limit'] = 0;
        }
        if( (!isset($params['offset'])) || (isset($params['offset']) && (intval($params['offset']) < 0)) ) {
            $params['offset'] = 0;
        }
        if(!isset($params['order'])) {
            $params['order'] = [];
        }

        $sql = "SELECT \n";
        if( ($params['columns'] === null) || (is_array($params['columns']) && (count($params['columns']) == 0))) {
            $sql.= "\t*";
        } else {
            foreach($params['columns'] as $column) {
                $sql.= "\t$column, \n";
            }
            $sql = rtrim($sql, ", \n");
        }
        $sql.= " \nFROM \n\t$table \n";
        $where = $this->generateSqlWhere($params['where']);
        if($where['sql'] != '') {
            $sql.= $where['sql'];
        }
        $sql.= $this->generateSqlOrderBy($params['order']);
        if( (!is_null($params['limit'])) && ($params['limit'] > 0) ) {
            $sql.= " LIMIT ".$params['limit'];
            if( (!is_null($params['offset'])) && ($params['offset'] > 0) ) {
                $sql.= " OFFSET ".$params['offset'];
            }
        }
        $sql.=";";
        try {
            $qry = $this->conn->prepare($sql);
            $qry->execute($where['data']);
            $result = $qry->fetchAll(PDO::FETCH_ASSOC);
        } catch( PDOException $e ) {
            $this->error($e, "select query error '$sql'");
        }
        return $result;
    }

    public function selectFromId(String $tableName, int $id, array $columns=null){
        $table = DB::table($tableName);
        if($table !== false) {
            $idFieldName = $table->idFieldName();
            if($idFieldName !== false) {
                $rec = $this->select($tableName, [
                    'columns' => $columns,
                    'where' => [
                        [$idFieldName, '=', $id],
                    ],
                 ]);
                if(count($rec) > 0) {
                    return $rec[0];
                }
            }
            return false;
        }
    }


    public function insert(String $tableName, Array $data, bool $getRecord=false) {
        $sqlKeys = "";
        $sqlValues = "";
        foreach (array_keys($data) as $key) {
            $sqlKeys.= "$key, ";
            $sqlValues.= ":$key, ";
        }
        $sqlKeys = rtrim($sqlKeys, ", ");
        $sqlValues = rtrim($sqlValues, ", ");
        //$sql = "INSERT INTO users (name, surname, sex) VALUES (:name, :surname, :sex)";
        $sql = "INSERT INTO $tableName ($sqlKeys) VALUES ($sqlValues);";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($data);
            $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch( PDOException $e ) {
            $this->error($e, "insert query error");
        }

        if($getRecord) {
            return $this->selectFromId($tableName, $this->lastInsertId());
        }
        return true;
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function update(String $table, Array $data, Array $wheres, bool $getRecord=false) {
        $where = $this->generateSqlWhere($wheres);
        $sqlValues = "";
        foreach (array_keys($data) as $key) {
            $sqlValues.= "$key=:$key, ";
        }
        $sqlValues = rtrim($sqlValues, ", ");

        //$sql = "UPDATE users SET name=:name, surname=:surname, sex=:sex WHERE id=:id";
        $sql = "UPDATE $table SET $sqlValues ".$where['sql'].";";
        //dd($where);
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($data, $where['data']));
        } catch( PDOException $e ) {
            $this->error($e, "update query error");
        }
        if($getRecord) {
            return $this->select($table, ['where' => $wheres]);
        }
        return true;
    }

}