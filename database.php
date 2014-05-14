<?php

/**
* @Auther: James Reeves
* @Version: 2.0v
* @Added where clause to the database class
* @File Database.php
*
* This is a helper class, with some basic database functions - Insert/Update etc
* On the insert we make sure we rollback on fail or commit on success
*
**/



class Database {

	public $mysqli;
	private $query;

	function __construct(){
		$this->mysqli = new mysqli('localhost', 'username', 'password', 'database');
		$this->mysqli->autocommit(false);
	}

	//Only use for simple queries - Not SAFE to use
	//for user enter values
	//E.G - Use to bring back all properties

	public function queryDb($querydb) {
		$query = $this->mysqli->query($querydb);
		return $query;
	}

   	public function LastRecord($id, $table) {
        	$query = "SELECT MAX($id) FROM $table";
        	$id = $query->execute();
        	return $id;
    	}

	public function insert($table, $into, $values){
		$this->query = "INSERT INTO " . $table;
		$result = $this->buildQuery($into, $values);
		$result->execute();
		if($this->mysqli->error){
			$this->mysqli->rollback();
			return 1;
		}
		else {
			$this->mysqli->commit();
			return 0;
		}
	}

	public function update($table, $into, $values, $where_cond, $id){
		$this->query = "UPDATE " . $table . " SET ";
		$result = $this->buildQueryUpdate($into, $values, $where_cond , $id);
		$result->execute();

		if($this->mysqli->error){
			$this->mysqli->rollback();
			return 1;
		}
		else {
			$this->mysqli->commit();
			return 0;
		}
	}
	public function deleteEntireTable($querydb) {
		$query = $this->mysqli->prepare($querydb);
		$query->execute();
		if($this->mysqli->error){
			$this->mysqli->rollback();
			return 1;
		}
		else {
			$this->mysqli->commit();
			return 0;
		}

	}

	public function delete($querydb, $id) {
		$query = $this->mysqli->prepare($querydb);
		$query->bind_param('i', $id);
		$query->execute();

		if($this->mysqli->error){
			$this->mysqli->rollback();
			return 1;
		}
		else {
			$this->mysqli->commit();
			return 0;
		}
	}



	private function buildQueryUpdate($into, $values, $where_cond, $id) {
		if($into === null || $values === null) {
			return 1;
		}
		else {
			//Count into and values
			$countInto = count($into);
			$countValue = count($values);
			if($countInto !== $countValue) {
				return 1;
			}
			else{
				$type = null;
				for($i = 0; $i < $countValue; $i++) {
					$into[$i] = "`".$into[$i]."`";
					$type .= $this->determineType($values[$i]);
				}

				//Add the into fields
				for($i = 0; $i<$countValue; $i++){
					if($i !== $countValue-1){
						$this->query .= $into[$i] . '= ?, ';
					}
					else{
						$this->query .= $into[$i] . '= ?';
					}

				}
				$this->query .= " WHERE " . $where_cond . "= ?";
				//Prepare Statement
				$result = $this->mysqli->prepare($this->query);
				$args = array();
				$type .= $this->determineType($id);
         		$args[] = $type;

         		foreach ($values as $prop => $val) {
            		$args[] = &$values[$prop];
         		}
         		$args[] = &$id;
				call_user_func_array(array($result, 'bind_param'), $args);
				return $result;
			}
		}
	}
	private function buildQuery($into, $values){
		//Check if there is data there
		if($into === null || $values === null) {
			return 1;
		}
		else {
			//Count into and values
			$countInto = count($into);
			$countValue = count($values);
			if($countInto !== $countValue) {
				return 1;
			}
			else{
				$type = null;
				for($i = 0; $i < $countValue; $i++) {
					$into[$i] = "`".$into[$i]."`";
					$type .= $this->determineType($values[$i]);
				}

				//Add the into fields
				$this->query.='('. implode($into, ', ') . ') VALUES (';
				//Add the values to the insert (?, ?)
				while($countValue !== 0){
					($countValue !== 1) ? $this->query .= '?, ' : $this->query .= '?)';
					$countValue--;
				}
				//Prepare Statement
				$result = $this->mysqli->prepare($this->query);
				$args = array();
         		$args[] = $type;
         		foreach ($values as $prop => $val) {
            		$args[] = &$values[$prop];
         		}
				call_user_func_array(array($result, 'bind_param'), $args);
				return $result;
			}

		}

	}
	protected function determineType($item)
   	{
      switch (gettype($item)) {
        case 'string':
            return 's';
            break;
	    case 'integer':
            return 'i';
            break;
         case 'blob':
            return 'b';
            break;
         case 'double':
            return 'd';
            break;     
        }
   	}

    public function LastID(){
   		$id = $this->mysqli->insert_id;
   		return $id;
   }
    public function __destruct(){
		$this->mysqli->close();
   }
}

?>