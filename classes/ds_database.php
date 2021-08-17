<?PHP // Ş-UTF8, 06.06.2020
// Mehmet Alper Şen

if (!class_exists('ds_database')) {
	class ds_database {
		public $error = false;
		public $error_msg = null;

		private $tools = null;
		private $dbtype = null;
		private $convert = null;
		private $conn = null;
		private $conn_params = null;
		private $connected = false;

		private $logs_errors = false;
		private $logs_dir = "";

		private $params = array();
		private $saved_params = array();

		private $last_sql = null;
		private $last_sql_string = null;

		private $option_convert_last_sql = false;
		//['],[<],[>],[;],[/],[?],[=],[&],[#],[%],[{],[}],[|],[@],[\],[union],[exec],[select],[insert], [update],[delete],[drop],[sp],[xp],["]
		private $option_blocked_words = array(false, array("union","concat", "alter", "create", "delete", "exec", "drop"));
		private $option_unicode_support = true;

		// $options = array(0=unicode_support)
		public function __construct($options=array()) {
			if (isset($options['tools'])) { $this->tools = $options['tools']; }
			if (isset($options['logs-errors']) AND is_bool($options['logs-errors'])) { $this->logs_errors = $options['logs-errors']; }
			if (isset($options['logs-dir']) AND !empty($options['logs-dir'])) { $this->logs_dir = $options['logs-dir']; }

			// Eng: When class created, this operation will be progress.
			// Tur: Sınıf oluşturulduğunda, bu işlemler başlatılacak.
			$this->conn_params = array(
				"server" => @$options['server'],
				"username" => @$options['username'],
				"password" => @$options['password'],
				"dbname" => @$options['dbname'],
				"dbtype" => @$options['dbtype']);

			$this->dbtype = @$options['dbtype'];
			if (isset($options['unicode'])) { $this->option_unicode_support = $options['unicode']; }
			$this->connect();
		}

		public function __destruct() {
			// Eng: If class closed or terminate, this operation will be proggress.
			// Tur: Eğer class kapatılırsa veya imha edilirse, öncesinde bu işlem çalışıtılacak.
			$this->close();
		}

	    public function get_version() {
	    	return array("1", "0", "relase", "build 6", "20200606");
	    }

	    public function set_options($option, $value) {
	    	if ($option == "unicode_support") { $this->option_unicode_support = $value; }
	    	elseif ($option == "conv_last_sql") { $this->option_convert_last_sql = $value; }
	    	elseif ($option == "blocked_words") { $this->option_blocked_words = $value; }
	    	elseif ($options == "logs-errors" AND is_bool($value)) { $this->logs_errors = $value; }
	    	elseif ($options == "logs-dir") { $this->logs_dir = $value; }
	    }

		public function connect() {
			// Eng: Open a new connection to the SQL server
			// Tur: SQL sunucusuna yeni bir bağlantı açar.
			$this->error = false;
			$this->error_msg = null;
			$this->connected = false;

			if ($this->dbtype == 'mysqli') {
				$this->conn = @new mysqli($this->conn_params["server"], $this->conn_params["username"], $this->conn_params["password"], $this->conn_params["dbname"]);

				if ($this->conn->connect_errno) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database Connection Error: ( '.$this->conn->connect_errno.' ) '.$this->conn->connect_error);
				}
				else {
					$this->connected = true;
					if ($this->option_unicode_support) {
						$this->conn->set_charset("utf8");
					}
				}
			}
			elseif ($this->dbtype == 'mysql') {
				$this->conn = @mysql_connect($this->conn_params["server"], $this->conn_params["username"], $this->conn_params["password"]);
				if (!$this->conn) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database Connection Error: ( '.mysql_errno($this->conn).' ) '.mysql_error($this->conn));
				}
				else {
					$select_db = @mysql_select_db($this->conn_params["dbname"], $this->conn);
					if (!$select_db) {
						$this->error = true;
						$this->error_msg = $this->show_msg('Database ( '.$this->conn_params["dbname"].' ) not found.');
					}
					else {
						$this->connected = true;
						if ($this->option_unicode_support) {
							@mysql_query("SET NAMES 'utf8'", $this->conn);
							@mysql_query("SET CHARACTER SET utf8", $this->conn);
							@mysql_query("SET COLLATION_CONNECTION = 'utf8_unicode_ci'", $this->conn);
						}
					}
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				try {
					$connection_string = "mysql:host=".$this->conn_params["server"].";dbname=".$this->conn_params["dbname"];
					$array_options = array();
					$array_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
					//$array_options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;

					if ($this->option_unicode_support) {
						if (version_compare(phpversion(), '5.3.6', '>=')) {
							 $connection_string .= ";charset=utf8";
						}
						else if (version_compare(phpversion(), '5.3.5', '<=')) {
							$array_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
							$array_options[PDO::ATTR_EMULATE_PREPARES] = false;
						}
					}

					$this->conn = @new PDO($connection_string, $this->conn_params["username"], $this->conn_params["password"], $array_options);
					$this->connected = true;
				}
				catch (PDOException $e) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database Connection Error: '.$e->getMessage());
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}
		}

		public function select_db($dbname) {
			// Eng: Selects the default database for database queries
			// Tur: Sorguların hangi veritabanı üzerinde yürütüleceğini belirtir.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				$select_db = @$this->conn->select_db($dbname);

				if (!$select_db) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database ( '.$dbname.' ) not found.');
				}
				else {
					$this->conn_params["dbname"] = $dbname;
				}
			}
			elseif ($this->dbtype == 'mysql') {
				$select_db = @mysql_select_db($dbname, $this->conn);

				if (!$select_db) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database ( '.$dbname.' ) not found.');
				}
				else {
					$this->conn_params["dbname"] = $dbname;
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$this->error_msg = $this->show_msg('This extension not supported by pdo library, we are trying unofficial method.');

				try {
					$this->conn->query('USE '.$dbname);
					$this->conn_params["dbname"] = $dbname;
				}
				catch(PDOException $e) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database ( '.$dbname.' ) not found.');
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}
		}

		private function logs($options=array()) {
			if ($this->logs_errors == true) {
				/*
				$output = array(
					"date" => date('d.m.Y H:i:s'),
					"sql" => @$options["sql"]),
					"params" => @$options["params"],
					"error" => @$options["error"],
					"details" => @$options["details"]
				);
				$output = json_encode($output);
				*/

				$params = @$options["params"];
				if (is_array($params)) {
					$params = json_encode($params);
				}

				$output = '<div>';
				$output .= '<b>Tarih</b>: '.date('d.m.Y H:i:s').'<br>';
				$output .= '<b>Sql</b>: <span style="color: red">'.@$options["sql"].'</span><br>';
				$output .= '<b>Params</b>: <span style="color: blue">'.$params.'</span><br>';
				$output .= @$options["details"].'<br>';
				$output .= '</div><br><br><br>';

				$filedir = $this->logs_dir."_sqlerrors/";
				if (!is_dir($filedir)) {
					mkdir($filedir, 0755);
				}

				$filename = $filedir.date('Y-m-d').".html";
				$filecontent = $output;
				$format = "a";
				$waitforlock = false;
				$filestatus = true;

				if (is_writable($filename) OR !file_exists($filename)) {
					if ($fl = fopen($filename, $format)) {
						$file_lock_status = false;
						if (flock($fl, LOCK_EX | LOCK_NB)) { $file_lock_status = true; }

						if ($waitforlock AND $file_lock_status == false) {
							$while_timeout = 15;
							$while_counter = 0;
							while ($file_lock_status = false) {
								$while_counter += 1;
								$file_lock_status = flock($fl, LOCK_EX | LOCK_NB);
								if ($file_lock_status) { Break; }
								elseif ($while_counter >= $while_timeout) { Break; }
								else { sleep(1); }
							}
						}

						if ($file_lock_status) {
							if (!fwrite($fl, $filecontent) === false) {
								flock($fl, LOCK_UN); 
								fclose($fl);
								return true;
							}
						}
					}
				}
			}
			return false;
		}

		public function query($sql) {
			// Eng: Performs a query on the database
			// Tur: Veritabanı üzerinde bir sorgu yürütülmesini sağlar.
			$this->error = false;
			$this->error_msg = null;
			$logparams = array();

			if (isset($sql) AND !empty($sql)) {
				$this->last_sql = $sql;
				if ($this->option_blocked_words[0] == true) {
					$check = $this->blocked_words_control();
					if ($check[0] == false) {
						$this->error = true;
						$this->error_msg = $this->show_msg($check[1]);
						$this->logs(array(
							"error" => $check[1], 
							"details" => $this->error_msg,
							"sql" => $sql,
							"params" => $logparams
						));
						return $this->error_msg;
					}
				}
				if ($this->option_convert_last_sql == true) {
					$this->create_sql_string();
				}

				$saved_params_count = count($this->saved_params);
				if ($saved_params_count > 0) {
					for ($i=0; $i<$saved_params_count; $i++) {
						$this->params[] = $this->saved_params[$i];
					}
				}

				if ($this->dbtype == 'mysqli') {
					$sql_new = $sql;
					$params_count = count($this->params);
					if ($params_count > 0) {
						$logparams = $this->params;
						$tmp_exp = explode(":", $sql_new);
						$tmp_exp_count = count($tmp_exp);
						$tmp_value = "";
						$tmp_types = "";
						$tmp_new_list = array();
						$tmp_new_list[0] = "";
						$tmp_count_values = 1;

						for ($i=0; $i<$tmp_exp_count; $i++) {
							$tmp_str = $tmp_exp[$i];
							$str_len = strlen($tmp_str);
							$tmp_value = "";
							for( $x = 0; $x <= $str_len; $x++ ) {
								$ctrl_char = substr( $tmp_str, $x, 1 );
								if ($ctrl_char != "," AND $ctrl_char != " " AND $ctrl_char != ")") {
									$tmp_value .= $ctrl_char;
								}
								else {
									Break;
								}
							}

							for ($x=0; $x<$params_count; $x++) {
								if ($this->params[$x][0] == $tmp_value) {
									$tmp_types .= $this->params[$x][2];
									$tmp_new_list[] = $this->params[$x][1];
									$sql_new = str_replace(":".$this->params[$x][0], "?", $sql_new);
									Break;
								}
							}
						}
						$tmp_new_list[0] = $tmp_types;

						$refs = array();
						foreach($tmp_new_list as $key => $value) {
							$refs[$key] = &$tmp_new_list[$key];
						}

						$query = $this->conn->prepare($sql_new);
						if ($query === false) {
							$this->error = true;
							$this->error_msg = $this->show_msg('Query Error: ( '.$this->conn->errno.' ) '.$this->conn->error);
							$this->logs(array(
								"error" => $this->conn->errno.' - '.$this->conn->error, 
								"details" => $this->error_msg,
								"sql" => $sql,
								"params" => $logparams
							));
						}
						else {
							call_user_func_array(array($query, 'bind_param'), $tmp_new_list);
							$query->execute();
							$this->params = array();
							return $query->get_result();
						}
					}
					else {
						$query = @$this->conn->query($sql_new);
						$this->params = array();

						if (!$query) {
							$this->error = true;
							$this->error_msg = $this->show_msg('Query Error: ( '.$this->conn->errno.' ) '.$this->conn->error);
							$this->logs(array(
								"error" => $this->conn->errno.' - '.$this->conn->error, 
								"details" => $this->error_msg,
								"sql" => $sql,
								"params" => $logparams
							));
						}
						else {
							return $query;
						}
					}
				}
				elseif ($this->dbtype == 'mysql') {
					$sql_new = $sql;
					$params_count = count($this->params);
					if ($params_count > 0) {
						$logparams = $this->params;
						for ($i=0; $i<$params_count; $i++) {
							$tmp_value = $this->params[$i][1];
							if ($this->params[$i][2] == "i") { $tmp_value = $this->tools->bigintval($tmp_value); }			/* Integer */
							elseif ($this->params[$i][2] == "d") { $tmp_value = floatval($tmp_value); }		/* Double */
							else { $tmp_value = $this->real_escape_string($tmp_value); }					/* Other: String */
							$sql_new = str_replace(":".$this->params[$i][0], "'".$tmp_value."'", $sql_new);
						}
					}

					$query = @mysql_query($sql_new, $this->conn);
					$this->params = array();

					if (!$query) {
						$this->error = true;
						$this->error_msg = $this->show_msg('Query Error: '.mysql_error($this->conn));
						$this->logs(array(
							"error" => mysql_error($this->conn), 
							"details" => $this->error_msg,
							"sql" => $sql,
							"params" => $logparams
						));
					}
					else { return $query; }
				}
				elseif ($this->dbtype == 'pdo_mysql') {
					$sql_new = $sql;
					$params_count = count($this->params);
					if ($params_count > 0) {
						$logparams = $this->params;
						$query = @$this->conn->prepare($sql_new);

						$status = true;
						try {
							for ($i=0; $i<$params_count; $i++) {
								$tmp_value = $this->params[$i][1];
								if ($this->params[$i][2] == "i") { $tmp_value = $this->tools->bigintval($tmp_value); }			/* Integer */
								elseif ($this->params[$i][2] == "d") { $tmp_value = floatval($tmp_value); }		/* Double */

								if ($this->params[$i][2] == "i") { $tmp_type = PDO::PARAM_INT; }				/* Integer */
								elseif ($this->params[$i][2] == "b") { $tmp_type = PDO::PARAM_LOB; } 			/* Blob */
								else { $tmp_type = PDO::PARAM_STR; }											/* Other: String */

								$query->bindValue(':'.$this->params[$i][0], $tmp_value, $tmp_type);
							}

							@$query->execute();
							//$query->closeCursor();
						}
						catch (PDOException $e) {
							$status = false;
							$this->error = true;
							$this->error_msg = $this->show_msg('Query Error: '.$e->getMessage());
							$this->logs(array(
								"error" => $e->getMessage(), 
								"details" => $this->error_msg,
								"sql" => $sql,
								"params" => $logparams
							));
						}

						$this->params = array();
						if ($status) { return $query; }
						else { return false; }
					}
					else {
						$status = true;
						$query = @$this->conn->prepare($sql_new);
						try {
							@$query->execute();
							//$query = @$this->conn->query($sql_new);
							//$query->closeCursor();
						}
						catch (PDOException $e) {
							$status = false;
							$this->error = true;
							$this->error_msg = $this->show_msg('Query Error: '.$e->getMessage());
							$this->logs(array(
								"error" => $e->getMessage(), 
								"details" => $this->error_msg,
								"sql" => $sql,
								"params" => $logparams
							));
						}

						if ($status) {
							$this->params = array();
							return $query;
						}
						else { return false; }
					}
				}
				else {
					$this->error = true;
					$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
					$this->logs(array(
						"error" => 'Database Type ( '.$this->dbtype.' ) not supported.', 
						"details" => $this->error_msg,
						"sql" => $sql,
						"params" => $logparams
					));
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('SQL Query is Empty.');
				$this->logs(array(
					"error" => 'SQL Query is Empty.', 
					"details" => $this->error_msg,
					"sql" => $sql,
					"params" => $logparams
				));
			}
		}

		public function num_rows($query) {
			// Eng: Gets the number of rows in a result
			// Tur: Sorgu sonucunda elde edilen kayıt sayısını döndürür.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return -1;
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->num_rows;
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_num_rows($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->rowCount();
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return -1;
			}
		}

		public function num_fields($query) {
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return -1;
			}
			else {
				return $this->field_count($query);
			}
		}
		
		public function field_count($query) {
			// Eng: Returns the number of fields from specified result set.
			// Tur: Sorgu sonucunda elde edilen kayıt üzerindeki stun sayısını döndürür.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return -1;
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->field_count;
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_num_fields($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->columnCount();
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return -1;
			}
		}

		public function fetch_assoc($query) {
			// Eng:  Fetch a result row as an associative array (Eg: $row['id'],$row['name']).
			// Tur: Sorgu sonuçlarının stun adları ile isimlendirilmiş dizi formatında (Örn: $row['id'],$row['name']) çağrılmasına olanak tanır.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return array();
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->fetch_assoc();
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_fetch_assoc($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->fetch(PDO::FETCH_ASSOC);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return array();
			}
		}

		public function fetch_row($query) {
			// Eng: Get a result row as an enumerated array (Eg: $row[0],$row[1]).
			// Tur: Sorgu sonuçlarının sıralı dizi formatında (Örn: $row[0],$row[1]) çağrılmasına olanak tanır.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return array();
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->fetch_row();
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_fetch_row($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->fetch(PDO::FETCH_NUM);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return array();
			}
		}

		public function fetch_array($query) {
			// Eng: Fetch a result row as an associative, a numeric array, or both (Eg: $row[0],$row['id']).
			// Tur: Sorgu sonuçlarının sıralı dizi veya stun adları ile isimlendirilmiş dizi formatında (Örn: $row[0],$row['id']) çağrılmasına olanak tanır.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return array();
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->fetch_array();
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_fetch_array($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->fetch(PDO::FETCH_BOTH);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return array();
			}
		}

		public function fetch_object($query) {
			// Eng: It will return the current row result set as an object where the attributes of the object represent the names of the fields found within the result set.
			// Tur: Sorgu sonuçlarının stun adları ile isimlendirilmiş obje formatında (Örn: $row->id,$row->name) çağrılmasına olanak tanır.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return new stdClass();
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->fetch_object();
			}
			elseif ($this->dbtype == 'mysql') {
				if (version_compare(phpversion(), '5.5.0', '>=')) {
					$this->error_msg = $this->show_msg('Warning: This extension (mysql_fetch_object) is deprecated as of PHP 5.5.0, and will be removed in the future.');
				}
				return @mysql_fetch_object($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->fetch(PDO::FETCH_OBJ);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return new stdClass();
			}
		}

		public function fetch_all($query) {
			// Eng: 
			// Tur: Sonuç kümesinin tüm satırlarını içeren bir dizi döndürür
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return array();
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$query->fetch_all();
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_fetch_all($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->fetchAll(PDO::FETCH_ASSOC);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return array();
			}
		}

		public function escape_string($str) {
			return $this->real_escape_string($str);
		}

		public function real_escape_string($str) {
			// Eng: Change the format of the characters and ensure the appropriate query format which could adversely affect SQL queries.
			// tur: Sql sorgularını olumsuz etkileyebilecek karakterlerin, sorguya uygun formata getirilmesini sağlar.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				return @$this->conn->real_escape_string($str);
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_real_escape_string($str, $this->conn);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				// There is no real_escape_string method for pdo, so we will just mimic real_escape_string function without db support
				if(is_array($str)) { return array_map(__METHOD__, $str); }

				if(!empty($str) && is_string($str)) { 
					return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $str); 
				}

				return $str;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return $str;
			}
		}

		public function autocommit($boolean) {
			// Eng: While transaction continuing, this will start/stop auto commit to the changes. We recommend to set it false, before the transaction begins.
			// Tur: Transaction sırasında işlemlerin otomatik onaylanması kontrolünü açar/kapatır. Transaction işleminden önce false duruma getirilmesi tavsiye edilir.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				@$this->conn->autocommit($boolean);
			}
			elseif ($this->dbtype == 'mysql') {
				if ($boolean) { $autocommit = 1; } else { $autocommit = 0; }
				@mysql_query("SET AUTOCOMMIT=".$autocommit, $this->conn);
				unset($autocommit);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				if ($boolean) { $autocommit = 1; } else { $autocommit = 0; }
				@$this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT,$autocommit);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}
		}

		public function begin_transaction() {
			// Eng: Start the transaction operation.
			// Tur: Transaction işlemini başlatır.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				$begin_transaction = @$this->conn->begin_transaction('MYSQLI_TRANS_START_READ_WRITE');

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Strating transaction failed.');
				}
			}
			elseif ($this->dbtype == 'mysql') {
				$begin_transaction = @mysql_query("START TRANSACTION", $this->conn);

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Strating transaction failed.');
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$begin_transaction = @$this->conn->beginTransaction();

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Strating transaction failed.');
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}

			if ($this->error == true) {
				$this->logs(array(
					"error" => '0 - begin_transaction error', 
					"details" => $this->error_msg,
					"sql" => "START TRANSACTION",
					"params" => array()
				));
			}
		}

		public function commit() {
			// Eng: Complate the transaction operation, commit all the sql operation after transaction begins.
			// Tur: Transaction işlemini tamamlar, transaction başladıktan sonra ki sql işlemlerini geçerli kılar.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				$begin_transaction = @$this->conn->commit();

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction commit failed.');
				}
			}
			elseif ($this->dbtype == 'mysql') {
				$begin_transaction = @mysql_query("COMMIT", $this->conn);

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction commit failed.');
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$begin_transaction = @$this->conn->commit();

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction commit failed.');
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}

			if ($this->error == true) {
				$this->logs(array(
					"error" => '0 - commit error', 
					"details" => $this->error_msg,
					"sql" => "COMMIT",
					"params" => array()
				));
			}
		}

		public function rollback() {
			// Eng: Cancel the transaction, rollback all the sql operation after transaction begins.
			// Tur: Transaction işlemini iptal eder, transaction başladıktan sonra ki sql işlemlerini geri alır.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				$begin_transaction = @$this->conn->rollback();

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction rollback failed.');
				}
			}
			elseif ($this->dbtype == 'mysql') {
				$begin_transaction = @mysql_query("ROLLBACK", $this->conn);

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction rollback failed.');
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$begin_transaction = @$this->conn->rollBack();

				if (!$begin_transaction) {
					$this->error = true;
					$this->error_msg = $this->show_msg('Transaction rollback failed.');
				}
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}

			if ($this->error == true) {
				$this->logs(array(
					"error" => '0 - rollback error', 
					"details" => $this->error_msg,
					"sql" => "ROLLBACK",
					"params" => array()
				));
			}
		}

		public function insert_id() {
			// Eng: Returns the AUTO INCREMENT number by the last INSERT operation.
			// Tur: Son Insert işleminde oluşturulan AUTO INCREMENT değerini döndürür.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				return @$this->conn->insert_id;
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_insert_id($this->conn);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				if (method_exists('PDO', 'lastInsertId')) {
					$last_id = @$this->conn->lastInsertId();
				}
				else { // Örnek amaçlıdır
					$s = $this->query("SELECT LAST_INSERT_ID() as last_id");
					$k = $this->fetch_assoc($s);
					$last_id = intval($k['last_id']);
				}
				return $last_id;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}
		}

		public function affected_rows($query) {
			// Eng: Returns the number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE query
			// Tur: INSERT, UPDATE, REPLACE veya DELETE işlemlerinden sonra, etkilenen kayıt sayısını döndürür.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return -1;
			}
			elseif ($this->dbtype == 'mysqli') {
				return @$this->conn->affected_rows;
			}
			elseif ($this->dbtype == 'mysql') {
				return @mysql_affected_rows($this->conn);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return @$query->rowCount();
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return -1;
			}
		}

		public function client_encoding() {
			return $this->character_set_name();
		}
		
		public function character_set_name() {
			// Eng: Returns the current character set for the database connection.
			// Tur: Veritabanı bağlantısının aktif karakter kodlamasını döndürür.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				//$character_set_name = @$this->conn->client_encoding(); // Deprecated PHP 5.3, Removed PHP 5.4
				$character_set_name = @$this->conn->character_set_name();
				return $character_set_name;
			}
			elseif ($this->dbtype == 'mysql') {
				$character_set_name = @mysql_client_encoding($this->conn);
				return $character_set_name;
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				// This will be re-checked next update.
				$this->error = true;
				$this->error_msg = $this->show_msg('This extension not supported by pdo library');
				return false;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return false;
			}
		}

		public function data_seek($query, $pointer) {
			// Eng: Seeks to an arbitrary result pointer in the statement result set.
			// Tur: Mevcut sorgunun belirli bir kayda geçmesini veya başa dönmesini sağlar.
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return false;
			}
			elseif ($this->dbtype == 'mysqli') {
				$data_seek = @$query->data_seek($pointer);
				return $data_seek;
			}
			elseif ($this->dbtype == 'mysql') {
				if (version_compare(phpversion(), '5.5.0', '>=')) {
					$this->error_msg = $this->show_msg('Warning: This extension (mysql_data_seek) is deprecated as of PHP 5.5.0, and will be removed in the future.');
				}
				$data_seek = @mysql_data_seek($query, $pointer);
				return $data_seek;
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				// This will be re-checked next update.
				$this->error = true;
				$this->error_msg = $this->show_msg('This extension not supported by pdo_mysql library');
				return false;

				// This example not working on mysql.
				//return $query->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $pointer);
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return false;
			}
		}

		public function ping() {
			// Eng: Checks whether or not the connection to the server is working. If it has gone down, an automatic reconnection is attempted.
			// Tur: Sunucuyla bağlantının çalışıp çalışmadığına bakar. Eğer bağlantı kesilmişse, yeniden bağlanmaya çalışır.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				return @$this->conn->ping();
			}
			elseif ($this->dbtype == 'mysql') {
				if (version_compare(phpversion(), '7.0.0', '<')) {
					if (version_compare(phpversion(), '5.5.0', '>=')) {
						$this->error_msg = $this->show_msg('Warning: This extension (mysql_ping) was deprecated in PHP 5.5.0, and it was removed in PHP 7.0.0.');
					}
					return @mysql_ping($this->conn);
				}
				else {
					$this->error = true;
					$this->error_msg = $this->show_msg('This extension (mysql_ping) was deprecated in PHP 5.5.0, and it was removed in PHP 7.0.0.');
					return false;
				}
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$this->error_msg = $this->show_msg('This extension not supported by pdo library, we are trying unofficial method.');

		        try { $this->conn->query('SELECT 1'); }
		        catch (PDOException $e) { $this->connect(); }
		        return true;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return false;
			}
		}

		public function close() {
			// Eng: Close the SQL server connection.
			// Tur: SQL sunucusuna bağlantıyı kapatır.
			$this->error = false;
			$this->error_msg = null;

			if ($this->dbtype == 'mysqli') {
				@$this->conn->close();
			}
			elseif ($this->dbtype == 'mysql') {
				@mysql_close($this->conn);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				$this->conn = null;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
			}
		}

		public function prepare($sql) {
			$this->last_sql = $sql;
		}

		public function execute($sql) {
			// Copy for ->query() function.
			if (isset($sql) AND !empty($sql)) { return $this->query($sql); }
			else { return $this->query($this->last_sql); }
		}

		public function exec($sql) {
			// Copy for ->query() function.
			if (isset($sql) AND !empty($sql)) { return $this->query($sql); }
			else { return $this->query($this->last_sql); }
		}

		public function clear_params() {
			// Eng: Clear params.
			// Tur: Parametreleri temizler.
			$this->error = false;
			$this->error_msg = null;

			$this->params = array();
			$this->saved_params = array();
		}

		public function show_params() {
			// Eng: Print params to an array for checking.
			// Tur: Parametreleri incelemek için array formatına döker.
			$this->error = false;
			$this->error_msg = null;

			return array("params" => $this->params, "saved-params" => $this->saved_params);
		}

		public function params($param, $value, $type="s") {
			// Eng: Add a param for SQL query.
			// Tur: SQL sorgusu için bir parametre oluşturur.
			$this->error = false;
			$this->error_msg = null;

			// $type = s:string, i:integer, d:double, b:blob
			$this->params[] = array($param, $value, $type);
		}

		public function saved_params($param, $value, $type="s") {
			// Eng: Add a param for SQL query.
			// Tur: SQL sorgusu için bir parametre oluşturur.
			$this->error = false;
			$this->error_msg = null;

			// $type = s:string, i:integer, d:double, b:blob
			$this->saved_params[] = array($param, $value, $type);
		}

		public function columnCount($query) {
			$this->error = false;
			$this->error_msg = null;

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return -1;
			}
			elseif ($this->dbtype == 'mysqli') {
				return $query->field_count;
			}
			elseif ($this->dbtype == 'mysql') {
				return mysql_num_fields($query);
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				return $query->columnCount();
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return -1;
			}
		}

		public function fetch_fields($query) {
			$this->error = false;
			$this->error_msg = null;

			$total_col = $this->columnCount($query);

			$col_list = array();

			if (is_bool($query)) {
				$outputmsg = '$db->query sonucu veri yerine boolean değeri dönmüş, lütfen SQL sorgunuzu kontrol ediniz.';
				$this->error = true;
				$this->error_msg = $this->show_msg($outputmsg);
				return array();
			}
			elseif ($this->dbtype == 'mysqli') {
				for ($i=0; $i<$total_col; $i++) {
					$tmp_arr = array();
					$tmp_arr["name"] = $query->fetch_fields()[$i]->name;
					$tmp_arr["table"] = $query->fetch_fields()[$i]->table;
					$tmp_arr["max_length"] = $query->fetch_fields()[$i]->max_length;
					$tmp_arr["length"] = $query->fetch_fields()[$i]->length;
					$tmp_arr["type"] = $query->fetch_fields()[$i]->type;
					$tmp_arr["flags"] = $query->fetch_fields()[$i]->flags; //bit
					$col_list[] = $tmp_arr;
				}
				return $col_list;
			}
			elseif ($this->dbtype == 'mysql') {
				for ($i=0; $i<$total_col; $i++) {
					$tmp_arr = array();
					$tmp_arr["name"] = mysql_fetch_field($query, $i)->name;
					$tmp_arr["table"] = mysql_fetch_field($query, $i)->table;
					$tmp_arr["max_length"] = mysql_fetch_field($query, $i)->max_length;
					$tmp_arr["length"] = null;
					$tmp_arr["type"] = mysql_fetch_field($query, $i)->type;
					$tmp_arr["flags"] = array(
						"blob" => mysql_fetch_field($query, $i)->blob,
						"multiple_key" => mysql_fetch_field($query, $i)->multiple_key,
						"not_null" => mysql_fetch_field($query, $i)->not_null,
						"numeric" => mysql_fetch_field($query, $i)->numeric,
						"primary_key" => mysql_fetch_field($query, $i)->primary_key,
						"unique_key" => mysql_fetch_field($query, $i)->unique_key,
						"unsigned" => mysql_fetch_field($query, $i)->unsigned,
						"zerofill" => mysql_fetch_field($query, $i)->zerofill
					);
					$col_list[] = $tmp_arr;
				}
				return $col_list;
			}
			elseif ($this->dbtype == 'pdo_mysql') {
				for ($i=0; $i<$total_col; $i++) {
					$tmp_arr = array();
					$tmp_arr["name"] = $query->getColumnMeta($i)["name"];
					$tmp_arr["table"] = $query->getColumnMeta($i)["table"];
					$tmp_arr["max_length"] = null;
					$tmp_arr["length"] = $query->getColumnMeta($i)["len"];
					$tmp_arr["type"] = $query->getColumnMeta($i)["native_type"];
					$tmp_arr["flags"] = $query->getColumnMeta($i)["flags"];
					//$tmp_arr["precision"] = $query->getColumnMeta($i)["precision"];
					//$tmp_arr["pdo_type"] = $query->getColumnMeta($i)["pdo_type"];
					$col_list[] = $tmp_arr;
				}
				return $col_list;
			}
			else {
				$this->error = true;
				$this->error_msg = $this->show_msg('Database Type ( '.$this->dbtype.' ) not supported.');
				return array();
			}
		}
		
		private function create_sql_string() {
			$output = $this->last_sql;

			$tmp_params = $this->params;
			$saved_params_count = count($this->saved_params);
			if ($saved_params_count > 0) {
				for ($i=0; $i<$saved_params_count; $i++) {
					$tmp_params[] = $this->saved_params[$i];
				}
			}

			$tmp_params_count = count($tmp_params);
			for ($i=0; $i<$tmp_params_count; $i++) {
				$tmp_value = "";
				if (is_null($tmp_params[$i][1])) { $tmp_value = 'NULL'; }
				else if ($tmp_params[$i][1] === true) { $tmp_value = 'true'; }
				else if ($tmp_params[$i][1] === false) { $tmp_value = 'false'; }
				else if (is_numeric($tmp_params[$i][1])) { $tmp_value = $tmp_params[$i][1]; }
				else { $tmp_value = "'".$this->real_escape_string($tmp_params[$i][1])."'"; }

				$output = preg_replace("/:".$tmp_params[$i][0]."/", $tmp_value, $output);
			}

			$this->last_sql_string = $output;
		}
		
		public function get_last_sql() {
			$this->error = false;
			$this->error_msg = null;

			if ($this->option_convert_last_sql == true) {
				return $this->last_sql_string;
			}
			else {
				return $this->last_sql;
			}
		}
		
		private function blocked_words_control() {
			$any_problem = false;
			$active_word = "";
			$check_problem = -1;
			$total_words = Count($this->option_blocked_words[1]);

			for ($i=0; $i<$total_words; $i++) {
				$active_word = $this->option_blocked_words[1][$i];
				$check_problem = strpos($this->last_sql, $active_word);
				if ($check_problem !== false) {
					$any_problem = true;
				}
			}

			unset($active_word);
			unset($total_words);
			unset($check_problem);

			if ($any_problem == true) {
				unset($any_problem);
				return false;
			}

			unset($any_problem);
			return true;
		}

		private function show_msg($msg="", $type="error") {
			$debug = debug_backtrace();
			$debug_count = count($debug);

			if ($type == "info") { $output = '<div class="alert alert-success"><strong>Bilgi!</strong>: '; }
			else { $output = '<div class="alert alert-danger"><strong>Hata!</strong>: '; }

			if ($msg != "") { $output .= $msg; }
			for ($i=0; $i < $debug_count; $i++) {
				$output .= '<div>';
				for ($x=0; $x < ($i+1); $x++) { $output .= '----'; }
				$output .= '&#9654; <strong>Dosya</strong>: '.$debug[$i]['file'];
				$output .= ', <strong>Satır</strong>: '.$debug[$i]['line'];
				$output .= '</div>';
			}
			$output .= '</div>';
			return $output;
		}
	}
}
?>