<?php
class dbDriverSQLSRV extends dbBASE {
	function connect(){
        try {
            $this->conn = new PDO("sqlsrv:server=".$this->config['server']."; Database=".$this->config['database'], $this->config->config['username'], $this->config->config['password']);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch( PDOException $e ) {
            $this->error($e, "Database connection error");
        }
	}
}