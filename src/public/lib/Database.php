<?
class Database
{
	public $db;
	private $queryInfo;

	public $table;

	public function __construct($table){
		$this->db = new PDO("mysql:dbname=".$_ENV["MYSQL_DATABASE"].";host=".$_ENV["MYSQL_HOST"],$_ENV["MYSQL_USER"], $_ENV["MYSQL_PASSWORD"]);
        
        $this->table = $table == null ? "test" : $table;

    }

	public function getFromId($id){
		$st = $this->db->prepare("SELECT * FROM `$this->table` WHERE `id` = $id");
		$st->execute();
		return $st->fetch(PDO::FETCH_ASSOC);
	}

	public function get(){
		$st = $this->db->prepare("SELECT * FROM `$this->table`");
		$st->execute();
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}
	public function update($data){
		$params = "SET ".$this->dataToDBValue($data);

		$this->queryInfo = ["method"=>"UPDATE", "params"=>$params];

		return $this;
	}

	public function select($columns){
		$this->queryInfo["method"] = "SELECT $columns FROM";

		return $this;
	}

	public function join($foreign_key, $table_two){
		$params = "JOIN `$table_two` ON `$table_two`.`id` = `$this->table`.`$foreign_key`";
		
		$this->queryInfo["params"] = $params;

		return $this;
	}

	public function delete($id){
		$st = $this->db->prepare("DELETE FROM `$this->table` WHERE `id` = $id");
		$st->execute();
		return ["status"=>true];
	}


	public function where($data){
		$params = $this->dataToDBValueWhere($data);

		if(isset($this->queryInfo["params"])) $this->queryInfo["params"] .= " WHERE ".$params;
		else $this->queryInfo["params"] = " WHERE ".$params;

		return $this;
	}

	public function run($getOne){
		$table = $this->table;
		$params = $this->queryInfo["params"];
		$method = $this->queryInfo["method"];
		$st = $this->db->prepare("$method `$table` $params");
		$st->execute();

		if($getOne){
			return $st->fetch(PDO::FETCH_ASSOC);
		}
		return $st->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insert($data){
		$parametrs = $this->dataToInsertValue($data);
		$columns = $parametrs['columns'];
		$params = $parametrs['params'];

		$st = $this->db->prepare("INSERT INTO `$this->table` $columns $params");
		$st->execute();
	}

	public function insertGet($data){
		$this->insert($data);
		return $this->select("*")->where($data)->run(true);
	}

	public function insertGetId($data){
		$this->insert($data);
		return $this->db->lastInsertId();
	}

	private function dataToInsertValue($data){
		$params = " VALUES (";
		$columns = '(';
		$lastKeyArray = array_key_last($data);

		foreach ($data as $key => $value) {
			if($key == $lastKeyArray){
				$params .= "'$value')"; 
				$columns .= "$key)";
			}else {
				$params .= "'$value', ";
				$columns .= "$key, ";
			}
		}
		return ["columns"=>$columns, "params"=>$params];
	}

	private function dataToDBValue($data){
		$params = "";

		$lastKeyArray = array_key_last($data);

		foreach ($data as $key => $value) {
			$value = is_string($value) ? "'$value'" : $value;
			if($key == $lastKeyArray) $params .= "$key = $value ";
			else $params .= "$key = $value, ";
		}
		return $params;
	} 
	private function dataToDBValueWhere($data){
		$params = "";

		$lastKeyArray = array_key_last($data);

		foreach ($data as $key => $value) {
			$value = is_string($value) ? "'$value'" : $value;
			if($key == $lastKeyArray) $params .= "$key = $value ";
			else $params .= "$key = $value AND ";
		}
		return $params;
	} 
}