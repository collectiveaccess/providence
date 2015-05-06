<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2013 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/Db/DbBase.php");
require_once(__CA_LIB_DIR__."/core/Db/DbStatement.php");
require_once(__CA_LIB_DIR__."/core/Db/DbResult.php");

require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");

/**
 * Provides an abstracted interface to SQL databases.
 */
class Db extends DbBase {

	/**
	 * Instance of the database driver
	 *
	 * @access private
	 */
	private $opo_db;
	/**
	 * Number of transactions begun
	 *
	 * @access private
	 */
	private $opn_transaction_count;	// number of transactions begun

	/**
	 * Configuration object
	 *
	 * @access private
	 */
	private $config;

	/**
	 * Datamodel object
	 *
	 * @access private
	 */
	private $datamodel;

	/**
	 * The id generated from the previous SQL INSERT operation
	 *
	 * @access private
	 */
	private $opn_last_insert_id = null;

	/**
	 * Standard field types (db drivers map native types to these types)
	 *
	 * @access private
	 */
	private $opa_field_types = array(
		"int",
		"float",
		"bit",
		"char",
		"varchar",
		"text",
		"blob"
	);
	
	/** 
	  * ApplicationMonitor to use for query logging
	  * @var ApplicationMonitor
	  */
	static $monitor = null;

	/**
	 * Constructor
	 *
	 * Sets up the properties of the Db object and opens a connection to the database.
	 *
	 * @param string $ps_config_file_path Not used
	 * @param array $pa_options Database options like username, pw, host, etc - if ommitted, it is fetched from configuration file
	 * @param bool $pb_die_on_error optional, default is true
	 */
	public function Db($ps_config_file_path="", $pa_options=null, $pb_die_on_error=true) {
		$this->config = Configuration::load();
		$this->datamodel = Datamodel::load();

		$this->opn_transaction_count = 0;

		$va_options = (is_array($pa_options)) ? $pa_options : array();
		
		if (!isset($va_options['username'])) {
			$va_options = array_merge($va_options, array(
				"username" => 	$this->config->get("db_user"),
				"password" => 	$this->config->get("db_password"),
				"host" =>	 	$this->config->get("db_host"),
				"database" =>	$this->config->get("db_database"),
				"type" =>		$this->config->get("db_type")
			));
		}
		$this->dieOnError($pb_die_on_error);
		$this->connect($va_options);
	}

	/**
	 * Opens a connection to the database.
	 *
	 * @access private
	 * @param array $pa_options
	 * @return bool success state
	 */
	public function connect($pa_options=null) {
		if (!$pa_options) {
			$pa_options = array(
				"username" => 	$this->config->get("db_user"),
				"password" => 	$this->config->get("db_password"),
				"host" =>	 	$this->config->get("db_host"),
				"database" =>	$this->config->get("db_database"),
				"type" =>		$this->config->get("db_type")
			);
		}
		$vs_dbtype = $pa_options["type"];
		$vs_dbclass = "Db_$vs_dbtype";
		if (!is_a($this->opo_db, $vs_dbclass)) {
			@require_once(__CA_LIB_DIR__."/core/Db/".$vs_dbtype.".php");
			if (class_exists($vs_dbclass)) {
				if (($this->opo_db = new $vs_dbclass())) {
					if ($this->opo_db->connect($this, $pa_options)) {
						return true;
					} else {
						// connection failed
						$this->opo_db = null;
						return false;
					}
				} else {
					// couldn't load driver
					$this->postError(295, _t("Could not instantiate driver"), "Db->connect()");
					return false;
				}
			} else {
				// driver does not exist
				$this->postError(295, _t("Driver does not exist"), "Db->connect()");
				return false;
			}
		} else {
			// connection is already is place
			return true;
		}
	}

	/**
	 * Fetches the Configuration object.
	 *
	 * @return Configuration
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 * Fetches the underlying database connection handle
	 *
	 * @return DbDriverBase
	 */
	public function getHandle() {
		return $this->opo_db->getHandle();
	}
	
	/**
	 * Set ApplicationMonitor object to log queries
	 *
	 * @param ApplicationMonitor $po_monitor ApplicationMonitor instance to use for logging
	 */
	static public function setMonitor($po_monitor) {
		Db::$monitor = $po_monitor;
	}

	/**
	 * Tests if a connection to the database exists.
	 *
	 * @param bool $pb_post_error Post errors or not?
	 * @param string $ps_error_context
	 * @return bool
	 */
	public function connected($pb_post_error=false, $ps_error_context=null) {
		if(!is_object($this->opo_db)) {
			if ($pb_post_error) {
				$this->postError(205, _t("Not connected to database"), $ps_error_context);
			}
			return false;
		}
		return true;
	}

	/**
	 * Prepares a SQL statement
	 *
	 * @param string $ps_sql SQL statement, can contain placeholders with attached values for SQL injection avoidance
	 * @return DbStatement the prepared statement; false on failure
	 */
	public function prepare($ps_sql) {
		if(!$this->connected(true, "Db->prepare()")) { return false; }
		$this->clearErrors();
		
		if (false){ 
			print "<hr>$ps_sql<br/><pre>";
			$va_trace = debug_backtrace();
			$vs_debug = '';
			foreach($va_trace as $va_line) {
				$vs_debug .= $va_line['class'].'/'.$va_line['function'].' ['.$va_line['line'].'];';
			}
			print "$vs_debug</pre><hr>";
		}
		return $this->opo_db->prepare($this, $ps_sql);
	}

	/**
	 * Executes a SQL statement. The SQL statement may contain question-mark ("?") placeholders whose values can be specified by parameters passed in addition to the SQL query itself.
	 * You can pass placeholder values in one of two ways:
	 * 	1. As the second parameter to query() you pass an array; the values will be substituted in order for the placeholders
	 *  2. Starting with the second parameter you can pass as many parameters as you like which will be substituted in order for the placeholders.
	 *
	 * In either case, if the number of values passed does not equal the number of placeholders, an error will be returned.
	 *
	 * To illustrate how this works, these two calls to query() are equivalent:
	 *
	 * $o_db->query("SELECT * FROM ca_users WHERE first_name = ? AND user_id > ?", array("Klaus", 20));
	 * $o_db->query("SELECT * FROM ca_users WHERE first_name = ? AND user_id > ?", "Klaus", 20);
	 *
	 * Note that the PHP type of the values your pass matter. Non-numeric values will be quoted, while numeric values will not. You should
	 * case values to their intended type before passing them to a query. Eg. (int)$cost_of_painting
	 *
	 * @param string $ps_sql SQL statement, can contain placeholders with attached values for SQL injection avoidance
	 * @param - first place holder value, or an array of placeholder values; if it is an array then the array is used for ALL placeholder values in order. If it is a scalar value then it will be used for the first placeholder, and subsequent parameters used for other placeholders in order.
	 * @return DbResult the resultset; false on failure
	 */
	public function query($ps_sql) {
		if(!$this->connected(true, "Db->query()")) { return false; }
		$this->clearErrors();

		$va_args = func_get_args();
		array_shift($va_args);		// get rid of first argument (sql statement)

		if (!$o_stmt = $this->prepare($ps_sql)) {
			return false;
		}

		$o_stmt->dieOnError($this->getDieOnError());

		// If second parameter is array use that as query params for placeholders, otherwise use successive params to fill placeholders
		if (!($o_res = $o_stmt->executeWithParamsAsArray(is_array($va_args[0]) ? $va_args[0] : $va_args))) {
			// copy errors from statement object to Db object
			$this->errors = $o_stmt->errors();
		} else {
			$this->opn_last_insert_id = $o_stmt->getLastInsertID();
		}

		return $o_res;
	}

	/**
	 * Fetches the id generated from the previous SQL INSERT operation.
	 *
	 * @return int id; false if not connected to database
	 */
	public function getLastInsertID() {
		if(!$this->connected(true, "Db->getLastInsertID()")) { return false; }
		return $this->opn_last_insert_id;
	}

	/**
	 * Fetches number of affected rows in previous SQL operation.
	 *
	 * @return int number of rows; false on failure
	 */
	public function affectedRows() {
		if(!$this->connected(true, "Db->affectedRows()")) { return false; }
		if (!$this->opo_db->supports($this, "numrows")) {
			$this->postError(213, _t("Driver does not support affectedRows() call"), "Db->affectedRows()");
			return false;
		}
		return $this->opo_db->affectedRows($this);
	}

	/**
	 * Escape special characters in a string for use in a SQL statement,
	 * taking into account the current charset of the connection.
	 *
	 * @param string $ps_text
	 * @return string text with escaped characters; false on failure
	 */
	public function escape($ps_text) {
		if(!$this->connected(true, "Db->escape()")) { return false; }
		return $this->opo_db->escape($ps_text);
	}

	/**
	 * Begins a transaction.
	 * Returns false if you're not connected to a database or if your driver doesn't support this method.
	 *
	 * @return bool
	 */
	public function beginTransaction() {
		if(!$this->connected(true, "Db->beginTransaction()")) { return false; }

		if (!$this->opo_db->supports($this, "transactions")) {
			$this->postError(210, _t("Transactions are not supported"), "Db->beginTransaction()");
			return false;
		}
		if ($this->opn_transaction_count >= $this->supports("max_nested_transactions")) {
			$this->postError(211, _t("Transaction count cannot exceed maximum of %1", $this->supports("max_nested_transactions")), "Db->beginTransaction()");
			return false;
		}
		$this->opn_transaction_count++;
		return $this->opo_db->beginTransaction($this);
	}

	/**
	 * Commits a transaction.
	 * Returns false if you're not connected to a database,
	 * if your driver doesn't support this method or
	 * if there is no transaction to commit.
	 *
	 * @return bool
	 */
	public function commitTransaction() {
		if(!$this->connected(true, "Db->commitTransaction()")) { return false; }

		if (!$this->opo_db->supports($this, "transactions")) {
			$this->postError(210, _t("Transactions are not supported"), "Db->commitTransaction()");
			return false;
		}
		if ($this->opn_transaction_count <= 0) {
			$this->postError(220, _t("Not currently in a transaction"), "Db->commitTransaction()");
			$this->opn_transaction_count = 0;
			return false;
		}
		$this->opn_transaction_count--;
		return $this->opo_db->commitTransaction($this);
	}

	/**
	 * Rollback a transaction
	 * Returns false if you're not connected to a database,
	 * if your driver doesn't support this method or
	 * if there is no transaction to commit.
	 *
	 * @return bool
	 */
	public function rollbackTransaction() {
		if(!$this->connected(true, "Db->rollbackTransaction()")) { return false; }

		if (!$this->opo_db->supports($this, "transactions")) {
			$this->postError(210, _t("Transactions are not supported"), "Db->rollbackTransaction()");
			return false;
		}
		if ($this->opn_transaction_count <= 0) {
			$this->postError(220, _t("Not currently in a transaction"), "Db->rollbackTransaction()");
			$this->opn_transaction_count = 0;
			return false;
		}
		$this->opn_transaction_count--;
		return $this->opo_db->rollbackTransaction($this);
	}

	/**
	 * Fetch the number of active transactions
	 *
	 * @return int
	 */
	public function getTransactionCount() {
		return $this->opn_transaction_count;
	}

	/**
	 * Check if (or how good) your database supports a certain feature.
	 * Returns false if you're not connected to a database.
	 *
	 * @param $ps_key possible values: 'limit', 'numrows', 'pconnect', 'prepare', 'ssl', 'transactions', 'max_nested_transactions'
	 * @return bool|int
	 */
	public function supports($ps_key) {
		if(!$this->connected(true, "Db->supports()")) { return false; }

		return $this->opo_db->supports($this, $ps_key);
	}

	/**
	 * Returns an array with the names of all tables in the database.
	 * Returns false if you're not connected to a database.
	 *
	 * @return array
	 */
	public function getTables() {
		if(!$this->connected(true, "Db->getTables()")) { return false; }
		return $this->opo_db->getTables($this);
	}

	/**
	 * Returns an array with the names of all fields for a given table.
	 * Returns false if you're not connected to a database.
	 *
	 * @param string $ps_table name of the table
	 * @param string|null $ps_fieldname optional fieldname
	 * @return array
	 */
	public function getFieldsFromTable($ps_table, $ps_fieldname=null) {
		if(!$this->connected(true, "Db->getFieldsFromTable()")) { return false; }

		$vs_fieldname_sql = "";
		if ($ps_fieldname) {
			$vs_fieldname_sql = " LIKE '".$this->escape($ps_fieldname)."'";
		}

		$qr_cols = $this->query("SHOW COLUMNS FROM ".$ps_table." ".$vs_fieldname_sql);

		$va_fields = array();
		while($qr_cols->nextRow()) {
			$va_row = $qr_cols->getRow();

			$va_options = array();
			if ($va_row['Extra'] == "auto_increment") {
				$va_options[] = "identity";
			} else {
				if (isset($va_row['Extra']) && (strlen($va_row['Extra']) > 0)) {
					$va_options[] = $va_row['Extra'];
				}
			}

			switch($va_row['Key']) {
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

			$va_db_datatype = $this->nativeToDbDataType($va_row['Type']);
			$va_fields[] = array(
				"fieldname" 		=> $va_row['Field'],
				"native_type" 		=> $va_row['Type'],
				"type"				=> $va_db_datatype["type"],
				"max_length"		=> $va_db_datatype["length"],
				"max_value"			=> $va_db_datatype["maximum"],
				"min_value"			=> $va_db_datatype["minimum"],
				"null" 				=> ($va_row['Null'] == "YES") ? true : false,
				"index" 			=> $vs_index,
				"default" 			=> ($va_row['Default'] == "NULL") ? null : ($va_row['Default'] !== "" ? $va_row['Default'] : null),
				"options" 			=> $va_options
			);
		}

		return $va_fields;
	}

	/**
	 * Returns an associative array with some information about a certain field.
	 * Returns false if you're not connected to a database.
	 *
	 * @param string $ps_table name of the table
	 * @param string $ps_fieldname name of the field
	 * @return array
	 */
	public function getFieldInfo($ps_table, $ps_fieldname) {
		if(!$this->connected(true, "Db->getFieldInfo()")) { return false; }
		return $this->getFieldInfo($ps_table, $ps_fieldname);
	}

	/**
	 * Returns an object representation of the given table.
	 *
	 * @param string $ps_table name of the table
	 * @return mixed
	 */
	public function getTableInstance($ps_table) {
		return $this->datamodel->getInstanceByTableName($ps_table);
	}

	/**
	 * Returns an associative array with some information about the keys of the given table.
	 * Returns false if you're not connected to a database.
	 *
	 * @param string $ps_table name of the table
	 * @return array
	 */
	public function getIndices($ps_table) {
		if(!$this->connected(true, "Db->getIndices()")) { return false; }

		$qr_keys = $this->query("SHOW KEYS FROM ".$ps_table);
		$va_keys = array();

		$vn_i = 1;
		while($qr_keys->nextRow()) {
			$va_row = $qr_keys->getRow();
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
	}

	/**
	 * Converts native datatypes to db datatypes
	 *
	 * @param string string representation of the datatype
	 * @return array array with more information about the type, specific to mysql
	 */
	public function nativeToDbDataType($ps_native_datatype_spec) {
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
	 * Conversion of error numbers
	 *
	 * @param int native error number
	 * @return int db error number
	 */
	public function nativeToDbError($pn_error_number) {
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
	public function __destruct() {
		//print "DESTRUCT Db\n";
		unset($this->opo_db);
	}
}
