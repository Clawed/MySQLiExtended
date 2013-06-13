<?php

	/*
	
		## MySQLi Extended
		## Copyright Clawed 2013
		## Version 0.0.1
	
	*/
	
	class MySQLiExtended
	{
		private $details;
		private $mysqli;
		private $paramValues = array();
		private $paramTypes = "";
		
		public function __construct($hostname, $username, $password, $database)
		{
			$this->details = array($hostname, $username, $password, $database);
		}
		
		public function Connect()
		{
			$this->mysqli = new MySQLi($this->details[0], $this->details[1], $this->details[2], $this->details[3]);
			
			if($this->mysqli->connect_error)
			{
				$this->Error("Failed to connect to MySQLi: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
			}
		}
		
		public function AddParam($type, &$value)
		{
			$this->paramValues[] = $value;
			$this->paramTypes .= $type;
		}
		
		public function GetParams()
		{
			return $this->ReferenceValuesParams(array_merge(array($this->paramTypes), $this->paramValues));
		}
		
		public function ReferenceValuesParams($array)
		{
			if(strnatcmp(phpversion(), "5.3") >= 0)
			{
				$refs = array();
				
				foreach($array as $key => $value)
				{
					$refs[$key] = &$array[$key];
				}
				
				return $refs;
			}
			
			return $array;
		}
		
		public function DumpParams()
		{
			$this->paramValues = array();
			$this->paramTypes = "";
		}
		
		public function BuildQuery($type, $table, $bits1 = array(), $bits2 = array(), $bits3 = array(), $orderby = "", $limit = 0)
		{
			foreach($bits1 as $var => $value)
			{
				$bits1[$var] = $this->mysqli->real_escape_string($value);
			}
			foreach($bits2 as $var => $value)
			{
				$bits2[$var] = $this->mysqli->real_escape_string($value);
			}
			foreach($bits3 as $var => $value)
			{
				$bits3[$var] = $this->mysqli->real_escape_string($value);
			}
			
			$sql = "";
			
			switch(strtolower($type))
			{
				case "insert":
					$sql .= "INSERT INTO `" . $table . "`";
					$keys = "";
					$marks = "";
					$i = 1;
					
					foreach(array_keys($bits1) as $key)
					{
						$keys .= " `" . $key . "`" . ($i != count($bits1) ? "," : " ");
						$marks .= " ?" . ($i != count($bits1) ? "," : " ");
						$i++;
					}
					
					$sql .= "(" . $keys . ") VALUES (" . $marks . ");";
					$this->bits1 = $bits1;
				break;
				
				case "select":
					// Last
				break;
				
				case "update":
					$sql = "UPDATE `" . $table . "` SET";
					$i = 1;
					
					foreach(array_keys($bits1) as $key)
					{
						$sql .= " `" . $key . "` = ?" . ($i != count($bits1) ? "," : " ");
						$i++;
					}
					
					if(count($bits2) > 0)
					{
						$sql .= "WHERE";
						$i = 1;
						
						foreach(array_keys($bits2) as $key)
						{
							$sql .= " `" . $key . "` = ?" . ($i != count($bits2) ? " AND" : " ");
							$i++;
						}
					}
					
					if($limit > 0)
					{
						$sql .= "LIMIT " . $limit;
					}
				break;
				
				case "delete":
					$sql .= "DELETE FROM `" . $table . "`";
					
					if(count($bits1) > 0)
					{
						$sql .= " WHERE";
						$i = 1;
						
						foreach(array_keys($bits1) as $key)
						{
							$sql .= " `" . $key . "` = ?" . ($i != count($bits1) ? " AND" : " ");
							$i++;
						}
					}
					
					if($limit > 0)
					{
						$sql .= "LIMIT " . $limit;
					}
				break;
				
				default:
					$this->Error("Wrong selection type.");
				break;
			}
			
			return $sql;
		}
		
		public function Insert($table, $data)
		{
			$stmt = $this->mysqli->stmt_init();
			
			if($stmt->prepare($this->BuildQuery("insert", $table, $data)))
			{
				foreach(array_values($data) as $value)
				{
					if(is_int($value))
					{
						$this->AddParam("i", $value);
					}
					elseif(is_string($value))
					{
						$this->AddParam("s", $value);
					}
				}
				
				call_user_func_array(array($stmt, "bind_param"), $this->GetParams());
				$this->DumpParams();
				$stmt->execute();
				$stmt->close();
			}
			else
			{
				$this->Error($this->mysqli->error);
			}
		}
		
		public function Select()
		{
			// Last
		}
		
		public function Update($table, $sets, $wheres, $limit)
		{
			$stmt = $this->mysqli->stmt_init();
			
			if($stmt->prepare($this->BuildQuery("update", $table, $sets, $wheres, array(), "", $limit)))
			{
				$merged = array_merge($sets, $wheres);
				
				foreach(array_values($merged) as $value)
				{
					if(is_int($value))
					{
						$this->AddParam("i", $value);
					}
					elseif(is_string($value))
					{
						$this->AddParam("s", $value);
					}
				}
				
				call_user_func_array(array($stmt, "bind_param"), $this->GetParams());
				$this->DumpParams();
				$stmt->execute();
				$stmt->close();
			}
			else
			{
				$this->Error($this->mysqli->error);
			}
		}
		
		public function Delete($table, $wheres, $limit)
		{
			$stmt = $this->mysqli->stmt_init();
			
			if($stmt->prepare($this->BuildQuery("delete", $table, $wheres, array(), array(), "", $limit)))
			{
				foreach(array_values($wheres) as $value)
				{
					if(is_int($value))
					{
						$this->AddParam("i", $value);
					}
					elseif(is_string($value))
					{
						$this->AddParam("s", $value);
					}
				}
				
				call_user_func_array(array($stmt, "bind_param"), $this->GetParams());
				$this->DumpParams();
				$stmt->execute();
				$stmt->close();
			}
			else
			{
				$this->Error($this->mysqli->error);
			}
		}
		
		public function Disconnect()
		{
			$this->mysqli->close();
		}
		
		public function Error($text)
		{
			die($text);
		}
	}
	
	$db = new MySQLiExtended("localhost", "root", "kyle123", "pegg");
	$db->Connect();
	
	//$db->Insert("users", array("username" => "Clawed", "email" => "Clawed@blader.com"));
	//$db->Update("users", array("email" => "Clawed@ragezone.com"), array("username" => "Clawed"), 1);
	//$db->Delete("users", array("username" => "Clawed"), 1);
	
	$db->Disconnect();

?>