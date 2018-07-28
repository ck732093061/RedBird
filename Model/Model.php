<?php

namespace RedBird\Model;

class Model{
	private $_db		  = null;
	private $_dbname	= '';  
    private $_stmt		= false;
    private $_vendor   = 'mysql'; 
    private $_connStr='';
	private $_u='';
	private $_p='';
	public $table=false;
	
	private function __connect(){
      $u = "";
      $p = "";
      if($this->_db === null){
           try{
               $this->_db = new \PDO($this->_connStr, $this->_u, $this->_p);
               $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			   $this->_db->beginTransaction();
           }catch(PDOException $e){
			   throw new \Exception(__FILE__ . " " . $e->getMessage());
           }
	  }
	}

	function __construct(){
	   if(!$this->table){
		   throw new \Exception("table must not be null");
	   }
	   $this->_vendor=Config('vender');
       if (!in_array($this->_vendor, array('mssql', 'mysql'))){
		  throw new \Exception("only for mysql and mssql");
	   }
	   if($this->_vendor == 'mssql'){
           $this->_dbname = Config('mssql.DB');
           $this->_u = Config('mssql.USERNAME');
           $this->_p = Config('mssql.PASSWORD');
           $this->_connStr = 'dblib:host='.Config('mssql.HOST').':'.Config('mssql.PORT').';dbname=' . Config('mssql.DB');
	  }else if($this->_vendor == 'mysql'){
           $this->_dbname = Config('mysql.DB');
           $this->_u = Config('mysql.USERNAME');
           $this->_p = Config('mysql.PASSWORD');
           $this->_connStr = 'mysql:dbname='.Config('mysql.DB').';port='.Config('mysql.PORT').';host=' . Config('mysql.HOST');
	  }
	}

	private function q($q, $params=array()){
      try{
		 $this->__connect();
		 $this->_stmt = $this->_db->prepare($q);
		 $this->_stmt->execute($params);
      }catch(PDOException $e){
		  throw new \Exception(__FILE__ . " " . $e->getMessage());
      }
	}

    private function query($q, $params=array()){
        $this->q($q, $params);
    }
    
    public function affectedRows(){
        return $this->_stmt->rowCount();
    }


	private function getResults($q, $params=array()){
		$this->q($q, $params);
		return $this->_stmt->fetch(\PDO::FETCH_ASSOC);
	}

	private function getResultsSet($q, $params=array()){
		$this->q($q, $params);
		return $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	private function prepareGet($where=false, $multipleRows=false){
		if ($where){
			$whereValues = array_values($where);
			foreach($where as $key => $value){
				$where[$key] = '?';
			}
			$where = " WHERE " . http_build_query($where, '', ' AND ');
		}
		$q = urldecode(" SELECT * FROM {$this->_dbname}.{$this->table} " . $where);
		if ($multipleRows){
			return $this->getResultsSet($q, $whereValues);
		}else{
			return $this->getResults($q, $whereValues);
		}
	}
	
	public function Add($data=false){
		if (!$data){
			throw new \Exception(__FILE__ . " " . 'insertdata must not be null');
		}
		if (!is_array($data)){
			throw new \Exception(__FILE__ . " " . 'insertdata must be array');
		}
		$addValues = array_values($data);
		$addKeys = implode(",", array_keys($data));
		foreach($data as $key => $value){
			$data[$key] = '?';
		}
		$Addparams=implode(",", array_values($data));
		$q = "INSERT INTO {$this->_dbname}.{$this->table}({$addKeys}) VALUES({$Addparams})";
		$this->q($q, $addValues);
	}
	
	public function Update($update=false, $where=""){
		if (!$update){
			throw new \Exception(__FILE__ . " " . 'update param must not be null');
		}
		$updateValues = array_values($update);
		foreach($update as $key => $value){
			$update[$key] = '?';
		}
		$update = http_build_query($update, '', ' AND ');
		if ($where){
			$whereValues = array_values($where);
			foreach($where as $key => $value){
				$where[$key] = '?';
			}
			$where = http_build_query($where, '', ' AND ');
			$updateValues = array_merge($updateValues, $whereValues);
		}
		$q = urldecode("UPDATE {$this->_dbname}.{$this->table} SET {$update} WHERE {$where}");
		$this->q($q, $updateValues);
	}
	
	public function FindAll($where=false){
		return $this->prepareGet($where, true);
	}

	public function FindOne($where=false){
		return $this->prepareGet($where, false);
	}
    
    public function FindBy($select, $name, $value, $orderBy = null, $smartFetch = true,$like = false){
        $this->__connect();
        if($like) {
            $matchingOperator = 'LIKE';
        }
        else {
            $matchingOperator = '=';
        }
        if($this->_mssql) {
            $select = 'TOP 1000 ' . $select;
        }
        if(is_array($select)) {
            $select = implode(', ', $select);
        }
        $query = "SELECT " . $select . " FROM " . $this->table . " WHERE ";
        if(is_array($name)) {
            foreach($name as $selector) {
                $query .= $selector . " " . $matchingOperator . " ? AND ";
            }
            $query = rtrim($query, " AND ");
        }
        else {
            $query .= $name . " " . $matchingOperator . " ?";
        }
        if($orderBy) {
            $query .= " ORDER BY " . $orderBy;
        }
        if(!$this->_mssql) {
            $query .= ' LIMIT 1000';
        }
        $this->_stmt = $this->_db->prepare($query);
        if(is_array($value)) {
            $this->_stmt->execute($value);
        }
        else {
            $this->_stmt->execute(array($value));
        }
        $results = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(count($results) > 1) {
            return $results;
        }
        elseif(count($results == 1) && $smartFetch) {
            return reset($results);
        }
        elseif(count($result == 1) && !$smartFetch) {
            return $results;
        }
        else {
            return array();
        }
    }

	function getLastInsertId(){
		return $this->_db->lastInsertId();
	}
}
