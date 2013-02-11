<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/mysql.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/core/Db/DbDriverBase.php");
require_once(__CA_LIB_DIR__."/core/Db/DbResult.php");
require_once(__CA_LIB_DIR__."/core/Db/DbStatement.php");

/**
 * Cache for prepared statements
 */
$g_mysql_statement_cache = array();

/**
 * Flag indicating if db user has FILE priv; null=undetermined
 */
$g_mysql_has_file_priv = null;

/**
 * Flag indicating if db user has FILE priv; null=undetermined
 */
$g_mysql_has_file_priv = null;

/**
 * MySQL driver for Db abstraction class
 *
 * You should always use the Db class as interface to the database.
 */
class Db_mysqli extends DbDriverBase {
	/**
	 * MySQL database resource
	 *
	 * @access private
	 */
	var $opr_db;

	/**
	 * SQL statement
	 *
	 * @access private
	 */
	var $ops_sql;

	/**
	 * List of features supported by this driver
	 *
	 * @access private
	 */
	var $opa_features = array(
		'limit'         => true,
		'numrows'       => true,
		'pconnect'      => true,
		'prepare'       => false,
		'ssl'           => false,
		'transactions'  => true,
		'max_nested_transactions' => 1
	);

	/**
	 * Constructor
	 *
	 * @see DbDriverBase::DbDriverBase()
	 */
	function __construct() {
		//print "Construct db driver\n";
	}

	/**
	 * Establishes a connection to the database
	 *
	 * @param mixed $po_caller representation of the caller, usually a Db() object
	 * @param array $pa_options array containing options like host, username, password
	 * @return bool success state
	 */
	function connect($po_caller, $pa_options) {
		global $g_connect;
		if (!is_array($g_connect)) { $g_connect = array(); }
		$vs_db_connection_key = $pa_options["host"].'/'.$pa_options["database"];
		
		if (isset($g_connect[$vs_db_connection_key]) && is_resource($g_connect[$vs_db_connection_key])) { $this->opr_db = $g_connect[$vs_db_connection_key]; return true;}
		
		if (!function_exists("mysqli_connect")) {
			die(_t("Your PHP installation lacks MySQL support. Please add it and retry..."));
			exit;
		}
		
		$this->opr_db = @mysqli_connect($pa_options["host"], $pa_options["username"], $pa_options["password"]);

		if (!$this->opr_db) {
			$po_caller->postError(200, mysqli_connect_error(), "Db->mysqli->connect()");
			return false;
		}

		if (!mysqli_select_db($this->opr_db, $pa_options["database"])) {
			$po_caller->postError(201, mysqli_error($this->opr_db), "Db->mysqli->connect()");
			return false;
		}
		mysqli_query($this->opr_db, 'SET NAMES \'utf8\'');
		mysqli_query($this->opr_db, 'SET character_set_results = NULL');	
		
		$g_connect[$vs_db_connection_key] = $this->opr_db;
		return true;
	}

	/**
	 * Closes the connection if it exists
	 *
	 * @return bool success state
	 */
	function disconnect() {
		//if (!is_resource($this->opr_db)) { return true; }
		//if (!@mysql_close($this->opr_db)) {
		//	return false;
		//}
		return true;
	}

	/**
	 * Prepares a SQL statement
	 * You may use placeholders (?) in your statement and attach the values
	 * as additional parameters to avoid SQL injection vulnerabilities
	 *
	 * @see Db::prepare()
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_sql query string
	 * @return DbStatement
	 */
	function prepare($po_caller, $ps_sql) {
		$this->ops_sql = $ps_sql;
		
		// are there any placeholders at all?
		if (strpos($ps_sql, '?') === false) {
			return new DbStatement($this, $this->ops_sql, array('placeholder_map' => array()));
		}
		
		global $g_mysql_statement_cache;
		
		$vs_md5 = md5($ps_sql);
		
		// is prepared statement cached?
		if(isset($g_mysql_statement_cache[$vs_md5])) {
			return new DbStatement($this, $ps_sql, array('placeholder_map' => $g_mysql_statement_cache[$vs_md5]));
		}
		

		// find placeholders
		$vn_i = 0;
		$vn_l = strlen($ps_sql);

		$va_placeholder_map = array();
		$vb_in_quote = '';
		$vb_is_escaped = false;
		
		while($vn_i < $vn_l) {
			$vs_c = $ps_sql{$vn_i};

			switch($vs_c) {
				case '"':
					if (!$vb_is_escaped) {
						if ($vb_in_quote == '"') {
							$vb_in_quote = '';
						} else {
							if (!$vb_in_quote) {
								$vb_in_quote = '"';
							}
						}
					}
					$vb_is_escaped = false;
					break;
				case "'":
					if (!$vb_is_escaped) {
						if ($vb_in_quote == "'") {
							$vb_in_quote = '';
						} else {
							if (!$vb_in_quote) {
								// handle escaped quote in '' format
								if ($ps_sql{$vn_i + 1} == "'") {
									$vn_i += 2;
									continue;
								} else {
									$vb_in_quote = "'";
								}
							}
						}
					}
					$vb_is_escaped = false;
					break;
				case '?':
					if ((!$vb_is_escaped) && (!$vb_in_quote)) {
						$va_placeholder_map[] = $vn_i;
					}
					$vb_is_escaped = false;
					break;
				case "\\":
					$vb_is_escaped = !$vb_is_escaped;
					break;
				default:
					$vb_is_escaped = false;
					break;
			}
			$vn_i++;
		}
		
		if (sizeof($g_mysql_statement_cache) >= 2048) { 
			array_shift($g_mysql_statement_cache); 
		}	// limit statement cache to 2048 entries, otherwise we'll eat up memory in long running processes

		
		$g_mysql_statement_cache[$vs_md5] = $va_placeholder_map;
		return new DbStatement($this, $this->ops_sql, array('placeholder_map' => $va_placeholder_map));
	}

	/**
	 * Executes a SQL statement
	 *
	 * @param mixed $po_caller object representation of the calling class, usually Db()
	 * @param DbStatement $opo_statement
	 * @param string $ps_sql SQL statement
	 * @param array $pa_values array of placeholder replacements
	 */
	function execute($po_caller, $opo_statement, $ps_sql, $pa_values) {
		if (!$ps_sql) {
			$opo_statement->postError(240, _t("Query is empty"), "Db->mysqli->execute()");
			return false;
		}

		$vs_sql = $ps_sql;

		$va_placeholder_map = $opo_statement->getOption('placeholder_map');
		$vn_needed_values = sizeof($va_placeholder_map);
		if ($vn_needed_values != sizeof($pa_values)) {
			$opo_statement->postError(285, _t("Number of values passed (%1) does not equal number of values required (%2)", sizeof($pa_values), $vn_needed_values),"Db->mysqli->execute()");
			return false;
		}

		for($vn_i = (sizeof($pa_values) - 1); $vn_i >= 0; $vn_i--) {
			if (is_array($pa_values[$vn_i])) {
				foreach($pa_values[$vn_i] as $vn_x => $vs_vx) {
					$pa_values[$vn_i][$vn_x] = $this->autoQuote($vs_vx);
				}
				$vs_sql = substr_replace($vs_sql, join(',', $pa_values[$vn_i]), $va_placeholder_map[$vn_i], 1 );
			} else {
				$vs_sql = substr_replace($vs_sql, $this->autoQuote($pa_values[$vn_i]), $va_placeholder_map[$vn_i], 1 );
			}
		}

		$va_limit_info = $opo_statement->getLimit();
		if (($va_limit_info["limit"] > 0) || ($va_limit_info["offset"] > 0)) {
			if (!preg_match("/LIMIT[ ]+[\d]+[,]{0,1}[\d]*$/i", $vs_sql)) { 	// check for LIMIT clause is raw SQL
				$vn_limit = $va_limit_info["limit"];
				if ($vn_limit == 0) { $vn_limit = 4000000000;}
				$vs_sql .= " LIMIT ".intval($va_limit_info["offset"]).",".intval($vn_limit);
			}
		}

		if (Db::$monitor) {
			$t = new Timer();
		}
		if (!($r_res = mysqli_query($this->opr_db, $vs_sql))) {
			//print "<pre>".caPrintStacktrace()."</pre>\n";
			$opo_statement->postError($this->nativeToDbError(mysqli_errno($this->opr_db)), mysqli_error($this->opr_db), "Db->mysqli->execute()");
			return false;
		}
		if (Db::$monitor) {
			Db::$monitor->logQuery($ps_sql, $pa_values, $t->getTime(4), is_bool($r_res) ? null : mysqli_num_rows($r_res));
		}

		return new DbResult($this, $r_res);
	}

	/**
	 * Fetches the ID generated by the last MySQL INSERT statement
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @return int the ID generated by the last MySQL INSERT statement
	 */
	function getLastInsertID($po_caller) {
		return @mysqli_insert_id($this->opr_db);
	}

	/**
	 * How many rows have been affected by your query?
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @return int number of rows
	 */
	function affectedRows($po_caller) {
		return @mysqli_affected_rows($this->opr_db);
	}

	/**
	 * Creates a temporary table
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_table_name string representation of the table name
	 * @param array $pa_field_list array containing the field names
	 * @param string $ps_type optional, defaults to innodb
	 * @return mixed mysql resource
	 */
	function createTemporaryTable($po_caller, $ps_table_name, $pa_field_list, $ps_type="") {
		if (!$ps_table_name) {
			$po_caller->postError(230, _t("No table name specified"), "Db->mysqli->createTemporaryTable()");
		}
		if (!is_array($pa_field_list) || sizeof($pa_field_list) == 0) {
			$po_caller->postError(231, _t("No fields specified"), "Db->mysqli->createTemporaryTable()");
		}


		$vs_sql  = "CREATE TEMPORARY TABLE ".$ps_table_name;

		$va_fields = array();
		foreach($pa_field_list as $va_field) {
			$vs_field = $va_field["name"]." ".$this->dbToNativeDataType($va_field["type"])." ";
			if ($va_field["length"] > 0) {
				$vs_field .= "(".$va_field["length"].") ";
			}

			if ($va_field["primary_key"]) {
				$vs_field .= "primary key ";
			}

			if ($va_field["null"]) {
				$vs_field .= "null";
			} else {
				$vs_field .= "not null";
			}

			$va_fields[] = $vs_field;
		}

		$vs_sql .= "(".join(",\n", $va_fields).")";

		switch($ps_type) {
			case 'memory':
				$vs_sql .= " ENGINE=memory";
				break;
			case 'myisam':
				$vs_sql .= " ENGINE=myisam";
				break;
			default:
				$vs_sql .= " ENGINE=innodb";
				break;
		}

		if (!($vb_res = @mysqli_query($this->opr_db, $vs_sql))) {
			$po_caller->postError($this->nativeToDbError(mysqli_errno($this->opr_db)), mysqli_error($this->opr_db), "Db->mysqli->createTemporaryTable()");
		}
		return $vb_res;
	}

	/**
	 * Drops a temporary table
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_table_name string representation of the table name
	 * @return mixed mysql resource
	 */
	function dropTemporaryTable($po_caller, $ps_table_name) {
		if (!($vb_res = @mysqli_query($this->opr_db, "DROP TABLE ".$ps_table_name))) {
			$po_caller->postError($this->nativeToDbError(mysqli_errno($this->opr_db)), mysqli_error($this->opr_db), "Db->mysqli->dropTemporaryTable()");
		}
		return $vb_res;
	}

	/**
	 * @see Db::escape()
	 * @param string
	 * @return string
	 */
	function escape($ps_text) {
		if ($this->opr_db) {
			return mysqli_real_escape_string($this->opr_db, $ps_text);
		} else {
			return mysqli_real_escape_string($ps_text);
		}
	}

	/**
	 * @see Db::beginTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	function beginTransaction($po_caller) {
		if (!@mysqli_query($this->opr_db, 'set autocommit=0')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->beginTransaction()");
			return false;
		}
		if (!@mysqli_query($this->opr_db, 'start transaction')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->beginTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see Db::commitTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	function commitTransaction($po_caller) {
		if (!@mysqli_query($this->opr_db, 'commit')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->commitTransaction()");
			return false;
		}
		if (!@mysqli_query($this->opr_db, 'set autocommit=1')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->commitTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see Db::rollbackTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	function rollbackTransaction($po_caller) {
		if (!@mysqli_query($this->opr_db, 'rollback')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->rollbackTransaction()");
			return false;
		}
		if (!@mysqli_query($this->opr_db, 'set autocommit=1')) {
			$po_caller->postError(250, mysqli_error($this->opr_db), "Db->mysqli->rollbackTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see DbResult::nextRow()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $pr_res mysql resource
	 * @return array array representation of the next row
	 */
	function nextRow($po_caller, $pr_res) {
		//$va_row = @mysql_fetch_row($pr_res);
		$va_row = @mysqli_fetch_assoc($pr_res);
		if (!is_array($va_row)) { return null; }

		//$vn_n = mysql_num_fields($pr_res);

		//for ($vn_i=0; $vn_i < $vn_n; $vn_i++) {
			//$o_fld = mysql_fetch_field($pr_res, $vn_i);
		//	$va_row[$o_fld->table . '.' . $o_fld->name] = $va_row[$o_fld->name] = $va_row[$vn_i];
		//}
		return $va_row;
	}

	/**
	 * @see DbResult::seek()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $pr_res mysql resource
	 * @param int $pn_offset line number to seek
	 * @return array array representation of the next row
	 */
	function seek($po_caller, $pr_res, $pn_offset) {
		if ($pn_offset < 0) { return false; }
		if ($pn_offset > (mysqli_num_rows($pr_res) - 1)) { return false; }
		if (!@mysqli_data_seek($pr_res, $pn_offset)) {
    		$po_caller->postError(260,_t("seek(%1) failed: result has %2 rows", $pn_offset, $this->numRows($pr_res)),"Db->mysqli->seek()");
			return false;
		};

		return true;
	}

	/**
	 * @see DbResult::numRows()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $pr_res mysql resource
	 * @return int number of rows
	 */
	function numRows($po_caller, $pr_res) {
		return @mysqli_num_rows($pr_res);
	}

	/**
	 * @see DbResult::free()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $pr_res mysql resource
	 * @return bool success state
	 */
	function free($po_caller, $pr_res) {
		if (is_resource($pr_res)) {
			return @mysqli_free_result($pr_res);
		}
		return false;
	}

	/**
	 * @see Db::supports()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_key feature to look for
	 * @return bool|int
	 */
	function supports($po_caller, $ps_key) {
		return $this->opa_features[$ps_key];
	}

	/**
	 * @see Db::getTables()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return array field list, false on error
	 */
	function &getTables($po_caller) {
		if ($r_show = mysqli_query($this->opr_db, "SHOW TABLES")) {
			$va_tables = array();
			while($va_row = mysqli_fetch_row($r_show)) {
				$va_tables[] = $va_row[0];
			}

			return $va_tables;
		} else {
			$po_caller->postError(280, mysqli_error($this->opr_db), "Db->mysqli->getTables()");
			return false;
		}
	}

	/**
	 * @see Db::getFieldsFromTable()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_table string representation of the table
	 * @param string $ps_fieldname optional fieldname
	 * @return array array containing lots of information
	 */
	function getFieldsFromTable($po_caller, $ps_table, $ps_fieldname=null) {
		$vs_fieldname_sql = "";
		if ($ps_fieldname) {
			$vs_fieldname_sql = " LIKE '".$this->escape($ps_fieldname)."'";
		}
		if ($r_show = mysqli_query($this->opr_db, "SHOW COLUMNS FROM ".$ps_table." ".$vs_fieldname_sql)) {
			$va_tables = array();
			while($va_row = mysqli_fetch_row($r_show)) {

				$va_options = array();
				if ($va_row[5] == "auto_increment") {
					$va_options[] = "identity";
				} else {
					if ($va_row[5]) {
						$va_options[] = $va_row[5];
					}
				}

				switch($va_row[3]) {
					case 'PRI':
						$vs_index = "primary";
						break;
					case 'MUL':
						$vs_index = "index";
						break;
					case 'UNI':
						$vs_index = "unique";
						break;
					default:
						$vs_index = "";
						break;

				}

				$va_db_datatype = $this->nativeToDbDataType($va_row[1]);
				$va_tables[] = array(
					"fieldname" 		=> $va_row[0],
					"native_type" 		=> $va_row[1],
					"type"				=> $va_db_datatype["type"],
					"max_length"		=> $va_db_datatype["length"],
					"max_value"			=> $va_db_datatype["maximum"],
					"min_value"			=> $va_db_datatype["minimum"],
					"null" 				=> ($va_row[2] == "YES") ? true : false,
					"index" 			=> $vs_index,
					"default" 			=> ($va_row[4] == "NULL") ? null : ($va_row[4] !== "" ? $va_row[4] : null),
					"options" 			=> $va_options
				);
			}

			return $va_tables;
		} else {
			$po_caller->postError(280, mysqli_error($this->opr_db), "Db->mysqli->getTables()");
			return false;
		}
	}

	/**
	 * @see Db::getFieldsInfo()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_table string representation of the table
	 * @param string $ps_fieldname fieldname
	 * @return array array containing lots of information
	 */
	function getFieldInfo($po_caller, $ps_table, $ps_fieldname) {
		$va_table_fields = $this->getFieldsFromTable($po_caller, $ps_table, $ps_fieldname);
		return $va_table_fields[0];
	}

	/**
	 * @see Db::getIndices()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_table string representation of the table
	 * @return array
	 */
	public function getIndices($po_caller, $ps_table) {
		if ($r_show = mysqli_query($this->opr_db, "SHOW KEYS FROM ".$ps_table)) {
			$va_keys = array();

			$vn_i = 1;
			while($va_row = mysqli_fetch_assoc($r_show)) {
				$vs_keyname = $va_row['Key_name'];

				if ($va_keys[$vs_keyname]) {
					$va_keys[$vs_keyname]['fields'][] = $va_row['Column_name'];
				} else {
					$va_keys[$vs_keyname] = $va_row;
					$va_keys[$vs_keyname]['fields'] = array($va_keys[$vs_keyname]['Column_name'] );
					$va_keys[$vs_keyname]['name'] = $vs_keyname;
					unset($va_keys[$vs_keyname]['Column_name'] );

					$va_keys[$vn_i] =& $va_keys[$vs_keyname];

					$vn_i++;
				}
			}

			return $va_keys;
		} else {
			$po_caller->postError(280, mysqli_error($this->opr_db), "Db->mysqli->getKeys()");
			return false;
		}
	}

	/**
	 * Returns list of engines present in the MySQL installation. The list in an array with
	 * keys set to engine names and values set to an array of information returned from MySQL
	 * about each engine. Note that while the MySQL SHOW ENGINES query return information
	 * about unsupported and disabled engines, getEngines() only returns information
	 * about engines that are available.
     *
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return array engine list, false on error.
	 */
	function getEngines($po_caller) {
		if ($r_show = mysqli_query("SHOW ENGINES", $this->opr_db)) {
			$va_engines = array();
			while($va_row = mysqli_fetch_assoc($r_show)) {
				if (!in_array($va_row['Support'], array('YES', 'DEFAULT'))) { continue; }
				$va_engines[$va_row['Engine']] = $va_row;
			}

			return $va_engines;
		} else {
			$po_caller->postError(280, mysqli_error($this->opr_db), "Db->mysqli->getEngines()");
			return false;
		}
	}

	/**
	 * Converts native datatypes to db datatypes
	 *
	 * @param string string representation of the datatype
	 * @return array array with more information about the type, specific to mysql
	 */
	function nativeToDbDataType($ps_native_datatype_spec) {
		if (preg_match("/^([A-Za-z]+)[\(]{0,1}([\d,]*)[\)]{0,1}[ ]*([A-Za-z]*)/", $ps_native_datatype_spec, $va_matches)) {
			$vs_native_type = $va_matches[1];
			$vs_length = $va_matches[2];
			$vb_unsigned = ($va_matches[3] == "unsigned") ? true : false;
			switch($vs_native_type) {
				case 'varchar':
					return array("type" => "varchar", "length" => $vs_length);
					break;
				case 'char':
					return array("type" => "char", "length" => $vs_length);
					break;
				case 'bigint':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 64)/2)), "maximum" => $vb_unsigned ? pow(2,64) - 1 : (pow(2,64)/2) - 1);
					break;
				case 'int':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 32)/2)), "maximum" => $vb_unsigned ? pow(2,32) - 1: (pow(2,32)/2) - 1);
					break;
				case 'mediumint':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 24)/2)), "maximum" => $vb_unsigned ? pow(2,24) - 1: (pow(2,24)/2) - 1);
					break;
				case 'smallint':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 16)/2)), "maximum" => $vb_unsigned ? pow(2,16) - 1: (pow(2,16)/2) - 1);
					break;
				case 'tinyint':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -128, "maximum" => $vb_unsigned ? 255 : 127);
					break;
				case 'decimal':
				case 'float':
				case 'numeric':
					$va_tmp = explode(",",$vs_length);
					if ($vb_unsigned) {
						$vn_max = (pow(10, $va_tmp[0]) - (1/pow(10, $va_tmp[1]))) - 1;
						$vn_min = 0;
					} else {
						$vn_max = ((pow(10, $va_tmp[0]) - (1/pow(10, $va_tmp[1]))) / 2) -1;
						$vn_min = -1 * ((pow(10, $va_tmp[0]) - (1/pow(10, $va_tmp[1]))) / 2);
					}
					return array("type" => "float", "minimum" => $vn_min, "maximum" => $vn_max);
					break;
				case 'tinytext':
					return array("type" => "varchar", "length" => 255);
					break;
				case 'text':
					return array("type" => "text", "length" => pow(2,16) - 1);
					break;
				case 'mediumtext':
					return array("type" => "text", "length" => pow(2,24) - 1);
					break;
				case 'longtext':
					return array("type" => "text", "length" => pow(2,32) - 1);
					break;
				case 'tinyblob':
					return array("type" => "blob", "length" => 255);
					break;
				case 'blob':
					return array("type" => "blob", "length" => pow(2,16) - 1);
					break;
				case 'mediumblob':
					return array("type" => "blob", "length" => pow(2,24) - 1);
					break;
				case 'longblob':
					return array("type" => "blob", "length" => pow(2,32) - 1);
					break;
				default:
					return null;
					break;
			}
		} else {
			return null;
		}
	}

	/**
	 * Converts db datatypes to native datatypes
	 *
	 * @param string string representation of the datatype
	 * @return string string representation of the native datatype
	 */
	function dbToNativeDataType($ps_db_datatype) {
		switch($ps_db_datatype) {
			case "int":
				return "int";
				break;
			case "float":
				return "decimal";
				break;
			case "bit":
				return "tinyint";
				break;
			case "char":
				return "char";
				break;
			case "varchar":
				return "varchar";
				break;
			case "text":
				return "longtext";
				break;
			case "blob":
				return "longblob";
				break;
			default:
				return null;
				break;
		}
	}

	/**
	 * Conversion of error numbers
	 *
	 * @param int native error number
	 * @return int db error number
	 */
	function nativeToDbError($pn_error_number) {
		switch($pn_error_number) {
			case 1004:	// Can't create file
			case 1005:	// Can't create table
			case 1006:	// Can't create database
				return 242;
				break;
			case 1007:	// Database already exists
				return 244;
				break;
			case 1050:	// Table already exists
			case 1061:	// Duplicate key
				return 245;
				break;
			case 1008:	// Can't drop database; database doesn't exist
			case 1049:	// Unknown database
				return 201;
				break;
			case 1051:	// Unknown table
			case 1146:	// Table doesn't exist
				return 282;
				break;
			case 1054:	// Unknown field
				return 283;
				break;
			case 1091:	// Can't DROP item; check that column/key exists
				return 284;
				break;
			case 1044:	// access denied for user to database
			case 1142:	// command denied to user for table
				return 207;
				break;
			case 1046:	// No database selected
				return 208;
				break;
			case 1048:	// Column cannot be null
				return 291;
				break;
			case 1216:	// Cannot add or update a child row: a foreign key constraint fails
			case 1217:	// annot delete or update a parent row: a foreign key constraint fails
				return 290;
				break;
			case 1136:	// Column count doesn't match value count
				return 288;
				break;
			case 1100:	// Table was not locked with LOCK TABLES
				return 265;
				break;
			case 1062:	// duplicate value for unique field
			case 1022:	// Can't write; duplicate key in table
				return 251;
				break;
			case 1065:
				// query empty
				return 240;
				break;
			case 1064:	// SQL syntax error
			default:
				return 250;
				break;
		}
	}

	/**
	 * Destructor
	 */
	function __destruct() {
		// Disconnecting here can affect other classes that need
		// to clean up by writing to the database so we disabled 
		// disconnect-on-destruct
		//$this->disconnect();
	}
}
?>