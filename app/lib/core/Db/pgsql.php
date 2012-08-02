<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/pgsql.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2011 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/core/Db/PDOStatement_pgsql.php");

/**
 * Cache for prepared statements
 */
$g_mysql_statement_cache = array();


global $g_db_driver;

$g_db_driver = "pgsql";

/**
 * PostGreSQL driver for Db abstraction class
 *
 * You should always use the Db class as interface to the database.
 */

class Db_pgsql extends DbDriverBase {
	/**
	 * PostGreSQL PDO database object
	 *
	 * @access private
	 */
	var $opo_db;

	/**
	 * PDOStatement_pgsql object containing last successful result
	 *
	 * @access private
	 */
	var $opo_lres;

	/** List of features supported by this driver
	 *
	 * @access private
	 */
	var $opa_features = array(
		'limit'         => true,
		'numrows'       => true,
		'pconnect'      => true,
		'prepare'       => true,
		'ssl'           => false,
		'transactions'  => true,
		'max_nested_transactions' => 0
	);

	/**
	 * Constructor
	 *
	 * @see DbDriverBase::DbDriverBase()
	 */
	function __construct() {
	}

	/**
	 * Function called by caSerializeForDatabase() in utilityHelper.php in order to correctly
	 * process byte array content
	 *
	 * @param $ps_data data to be processed
	 * @return string the result. Unchanged if valid UTF-8
	 **/
	public static function serializeForDatabase($ps_data){
		if(!mb_check_encoding($ps_data, "UTF-8")){  // Either gzipped data or other binary. Goes into a bytea.
			$vs_hexs = "E'\\\\x";
			for($i = 0 ; $i < strlen($ps_data) ; ++$i){ // strlen and [] assumes non multibyte strings
				$vn_val = ord($ps_data[$i]);
				$vs_hexs .= ($vn_val & 0xf0) ? dechex($vn_val) : "0".dechex($vn_val);
			}
			return $vs_hexs . "'";
		}
		else{
			return $ps_data;
		}
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
		
		if (is_object($g_connect)) { $this->opo_db = $g_connect; return true;}
		
		if (!class_exists(PDO))
		{
			die(_t("Your PHP installation lacks PDO support. Please add it and retry..."));
			exit;
		}
		if (!in_array("pgsql", PDO::getAvailableDrivers())) {
			die(_t("Your PHP installation lacks PDO-PostgreSQL support. Please add it and retry..."));
			exit;
		}
		
		$vs_pdodsn = "pgsql:host={$pa_options["host"]};dbname={$pa_options["database"]};user={$pa_options["username"]};password={$pa_options["password"]}";
		$this->opo_db = new PDO($vs_pdodsn);

		if (!$this->opo_db) {
			$po_caller->postError(200, "Unnable to connect to database. Check database settings in setup.php", "Db->pgsql->connect()");
			return false;
		}

		$g_connect = $this->opo_db;
		return true;
	}

	/**
	 * Closes the connection if it exists
	 *
	 * @return bool success state
	 */
	function disconnect() {
		return true;
	}

	/**
	 * Gets error text from PDO object
	 *
	 * @return string driver specific error message
	 */
	function errorinfo() {
		$vs_error = $this->opo_db->errorInfo();
		return $vs_error[2];
	}

	/**
	 * Gets error code from PDO object
	 *
	 * @return int driver specific error code
	 */
	function errorcode() {
		$vs_error = $this->opo_db->errorInfo();
		return $vs_error[1];
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
		
		// are there any placeholders at all?
		if (strpos($ps_sql, '?') === false) {
			return new DbStatement($this, $ps_sql, array('placeholder_map' => array()));
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
		
		if (sizeof($g_mysql_statement_cache) >= 2048) { // uses the same cache as the mysql driver
			array_shift($g_mysql_statement_cache); 
		}	// limit statement cache to 2048 entries, otherwise we'll eat up memory in long running processes

		
		$g_mysql_statement_cache[$vs_md5] = $va_placeholder_map;
		return new DbStatement($this, $ps_sql, array('placeholder_map' => $va_placeholder_map));
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
			$opo_statement->postError(240, _t("Query is empty"), "Db->pgsql->execute()");
			return false;
		}

		$vs_sql = $ps_sql;

		$va_placeholder_map = $opo_statement->getOption('placeholder_map');
		$vn_needed_values = sizeof($va_placeholder_map);
		if ($vn_needed_values != sizeof($pa_values)) {
			print "<pre>".caPrintStacktrace()."</pre>" . "\n\n";
			$opo_statement->postError(285, _t("Number of values passed (%1) does not equal number of values required (%2)", sizeof($pa_values), $vn_needed_values),"Db->pgsql->execute()");
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
		if (!($r_res = ($this->opo_db->query($vs_sql)))) {
			print "<pre>".caPrintStacktrace()."</pre>\n";
			print $vs_sql;
			print $this->errorinfo();
			$opo_statement->postError($this->nativeToDbError($this->errorcode()), $this->errorinfo(), "Db->pgsql->execute()");
			return false;
		}
		if (Db::$monitor) {
			Db::$monitor->logQuery($ps_sql, $pa_values, $t->getTime(4), is_bool($r_res) ? null : $r_res->rowCount());
		}
		$this->opo_lres = new PDOStatement_pgsql($r_res);
		return new DbResult($this, $this->opo_lres);
	}

	/**
	 * Fetches the ID generated by the last SQL INSERT statement.
	 * Assumes that the first nextval() function of the table is associated with the right insertion id
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_sql the SQL INSERT statement
	 * @return int the ID generated by the last INSERT statement
	 */
	function getLastInsertID($po_caller, $ps_sql) {
		if(preg_match("/insert[\s]+into[\s]+([0-9A-Za-z_.]+)/i", $ps_sql, $va_matches)){
			$vs_table = $va_matches[1];
			$vo_res = $this->opo_db->query("SELECT c.oid
                                				FROM pg_catalog.pg_class c
                                				WHERE c.relname = '$vs_table'
                                    			AND pg_catalog.pg_table_is_visible(c.oid)");
			$va_row = $vo_res->fetchAll(PDO::FETCH_ASSOC);
			$vn_oid = $va_row[0]['oid'];


			$vs_query =    "SELECT a.attname,
            			            (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
                	       			     FROM pg_catalog.pg_attrdef d
                    	       			 WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as default
                					FROM pg_catalog.pg_attribute a
                					WHERE a.attrelid = '$vn_oid' AND a.attnum > 0 AND NOT a.attisdropped
                					ORDER BY a.attnum";
			if(!is_object($vo_res = $this->opo_db->query($vs_query))){
				print_r($va_matches);print "\n" . caPrintStacktrace(); 
			}

			while($va_row = $vo_res->fetch(PDO::FETCH_ASSOC)){
    			$vs_expr = "{$vs_table}_{$va_row['attname']}_seq";
    			if($va_row['default'] == "nextval('{$vs_expr}'::regclass)"){
        			$vo_eres = $this->opo_db->query("SELECT currval('{$vs_expr}')");
        			$va_eres = $vo_eres->fetchAll(PDO::FETCH_ASSOC);
        			return $va_eres[0]['currval']; // Last inserted id in table
    			}
			}
		// Reached if table has no automatic incrementing column
		}
		return 0; // Emulate mysql_last_id()
	}

	/**
	 * How many rows have been affected by your query?
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @return int number of rows
	 */
	function affectedRows($po_caller) {
		return $this->opo_lres->rowCount();
	}

	/**
	 * Creates a temporary table
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_table_name string representation of the table name
	 * @param array $pa_field_list array containing the field names
	 * @param string $ps_type ignored
	 * @return mixed DbResult object
	 */
	function createTemporaryTable($po_caller, $ps_table_name, $pa_field_list, $ps_type="") {
		if (!$ps_table_name) {
			$po_caller->postError(230, _t("No table name specified"), "Db->pgsql->createTemporaryTable()");
		}
		if (!is_array($pa_field_list) || sizeof($pa_field_list) == 0) {
			$po_caller->postError(231, _t("No fields specified"), "Db->pgsql->createTemporaryTable()");
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
				$vs_field .= "null ";
			} else {
				$vs_field .= "not null ";
			}
			if ($va_field["default"]) {
				$vs_field .= "default {$va_field["defaultval"]}";
			}

			$va_fields[] = $vs_field;
		}

		$vs_sql .= "(".join(",\n", $va_fields).")";
		if (!($vb_res = $this->opo_db->query($vs_sql))) {
			$po_caller->postError($this->nativeToDbError($this->errorcode()), $this->errorinfo(), "Db->pgsql->createTemporaryTable()");
		}
		return new DbResult($this, new PDOStatement_pgsql($vb_res));
	}

	/**
	 * Drops a temporary table
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @param string $ps_table_name string representation of the table name
	 * @return mixed DbResult object
	 */
	function dropTemporaryTable($po_caller, $ps_table_name) {
		if (!($vb_res = @$this->opo_db->query("DROP TABLE ".$ps_table_name))) {
			$po_caller->postError($this->nativeToDbError($this->errorcode()), $this->errorinfo(), "Db->pgsql->dropTemporaryTable()");
		}
		return $vb_res;
	}

	/**
	 * @see Db::escape()
	 * @param string
	 * @return string
	 */
	function escape($ps_text){
    	return substr($this->opo_db->quote($ps_text), 1, -1);
	}
	/**
	 * @see Db::quote()
	 * @param string
	 * @return string
	 */
	function quote($ps_text){
			// checks whether input is binary data prepared for bytea insertion
			if(preg_match("/E'\\\\\\\\x[A-Fa-f0-9]/", $ps_text)){
    			return $ps_text;
			}
    	return $this->opo_db->quote($ps_text);
	}
	/**
	 * @see Db::beginTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	function beginTransaction($po_caller) {
		if (!@$this->opo_db->beginTransaction()) {
			$po_caller->postError(250, $this->errorinfo(), "Db->pgsql->beginTransaction()");
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
		if (!@$this->opo_db->commit()) {
			$po_caller->postError(250, $this->errorinfo(), "Db->pgsql->commitTransaction()");
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
		if (!@$this->opo_db->rollBack()) {
			$po_caller->postError(250, $this->errorinfo(), "Db->pgsql->rollbackTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see DbResult::nextRow()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $po_res PDOStatement Wrapper object
	 * @return array array representation of the next row
	 */
	function nextRow($po_caller, $po_res) {
		$va_row = $po_res->getRow();
		if(!is_array($va_row)){
			return $va_row;
		}
		foreach($va_row as &$vm_data){
			if(is_resource($vm_data)){ // If the PDO driver returns a bytea field as a stream resource 
				$vm_data = stream_get_contents($vm_data);
			}
		}
		return $va_row;
	}

	/**
	 * @see DbResult::seek()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $po_res PDOStatement_pgsql object
	 * @param int $pn_offset line number to seek
	 * @return array array representation of the next row
	 */
	function seek($po_caller, $po_res, $pn_offset) {
		return $po_res->seek($pn_offset);
	}

	/**
	 * @see DbResult::numRows()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $po_res PDOStatement_pgsql object
	 * @return int number of rows
	 */
	function numRows($po_caller, $po_res) {
		return $po_res->rowCount();
	}

	/**
	 * @see DbResult::free()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param mixed $po_res PDOStatement_pgsql object
	 * @return bool success state
	 */
	function free($po_caller, $po_res) {
		return true;
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
		if ($r_show = $this->opo_db->query("SELECT tablename FROM pg_tables WHERE schemaname='public'")) {
			$va_tables = array();
			while($va_row = $r_show->fetch(PDO::FETCH_NUM)){
				$va_tables[] = $va_row[0];
			}
			return $va_tables;
		} else {
			$po_caller->postError(280, $this->errorinfo(), "Db->pgsql->getTables()");
			return false;
		}
	}

	function getFieldNamesFromTable($po_caller, $ps_table){
		$qr_res = $this->opo_db->query("
			SELECT a.attname FROM pg_catalog.pg_attribute a
			WHERE a.attrelid in (SELECT c.oid FROM pg_catalog.pg_class c WHERE c.relname = '$ps_table')
				AND a.attnum > 0");
		$va_fields = array();
		foreach($qr_res->fetchAll(PDO::FETCH_ASSOC) as $va_field){
			$va_fields[] = $va_field['attname'];
		}
		return $va_fields;
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
			$vs_fieldname_sql = " AND a.attname ~ '^(".$this->escape($ps_fieldname).")$'";
		}
		$r_show = $this->opo_db->query("SELECT c.oid
										FROM pg_catalog.pg_class c
     									LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
										WHERE c.relname = '$ps_table'
  											AND pg_catalog.pg_table_is_visible(c.oid)");
		if($r_show){
			$va_row = $r_show->fetchAll(PDO::FETCH_ASSOC);
		}
		else{
			$po_caller->postError(280, $this->errorinfo(), "Db->pgsql->getTables()");
			return false;
		}
		if(is_array($va_row)){
			if(isset($va_row[0]['oid'])){
				$vn_oid = $va_row[0]['oid'];
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}

		$vs_query =    "SELECT a.attname,
					  		pg_catalog.format_type(a.atttypid, a.atttypmod),
  								(SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
   									FROM pg_catalog.pg_attrdef d
   									WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef),
  							a.attnotnull, a.attnum,
  							a.attstorage, pg_catalog.col_description(a.attrelid, a.attnum)
						FROM pg_catalog.pg_attribute a
						WHERE a.attrelid = '$vn_oid' AND a.attnum > 0 AND NOT a.attisdropped $vs_fieldname_sql
						ORDER BY a.attnum";
		if ($r_show = $this->opo_db->query($vs_query)) {
			$va_tables = array();
			while($va_row = $r_show->fetch(PDO::FETCH_ASSOC)) {
				$va_options = array();
				// TODO: implement this (is it necessary?)
				/*switch($va_row[3]) {
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

				}*/
				$vs_index = "";

				$va_db_datatype = $this->nativeToDbDataType($va_row['format_type']);
				if(is_string($va_row['?column?'])){
					if(preg_match("/^[\d]+/",$va_row['?column?'])){
						$vm_default = $va_row['?column?'];
					}
					else{
						$vm_default = false;
					}
					// Ugly. Assume IDENTITY is the first incrementer.
					if(preg_match('/nextval\(\''.$ps_table.'_'.$va_row['attname'].'_seq\'::regclass\)/',$va_row['?column?'])){
						$va_options[] = "identity";
					}
				}
				$va_tables[] = array(
					"fieldname" 		=> $va_row['attname'],
					"native_type" 		=> $va_row['format_type'],
					"type"				=> $va_db_datatype["type"],
					"max_length"		=> $va_db_datatype["length"],
					"max_value"			=> $va_db_datatype["maximum"],
					"min_value"			=> $va_db_datatype["minimum"],
					"null" 				=> !($va_row['attnotnull']),
					"index" 			=> $vs_index, // NOT IMPLEMENTED TODO
					"default" 			=> $vm_default,
					"options" 			=> $va_options
				);
			}

			return $va_tables;
		} else {
			$po_caller->postError(280, $this->errorinfo(), "Db->pgsql->getFieldFromTable()");
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
	 * TODO: Not implemented
	 * @see Db::getIndices()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_table string representation of the table
	 * @return array
	 */
	public function getIndices($po_caller, $ps_table) {
			return false;
/*		if ($r_show = $this->opo_db->query("SHOW KEYS FROM ".$ps_table)) {
			$va_keys = array();

			$vn_i = 1;
			while($va_row = $r_show->fetch(PDO::FETCH_ASSOC)){
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
			$po_caller->postError(280, $this->errorinfo(), "Db->pgsql->getKeys()");
			return false;
		}*/
	}

	/**
	 * Converts native datatypes to db datatypes
	 *
	 * @param string string representation of the datatype
	 * @return array array with more information about the type, specific to postgresql
	 */
	function nativeToDbDataType($ps_native_datatype_spec) {
		if (preg_match("/^([A-Za-z]+)[\(]{0,1}([\d,]*)[\)]{0,1}[ ]*([A-Za-z]*)[\(]{0,1}([\d,]*)[\)]{0,1}/", $ps_native_datatype_spec, $va_matches)){
			if(($va_matches[1] == "character") && ($va_matches[3] == "varying")){
				$vs_native_type = 'varchar';
				$vs_length = $va_matches[4];
			}
			else{
				$vs_native_type = $va_matches[1];
				$vs_length = $va_matches[2];
			}
			$vb_unsigned = false; // no unsigned types in pgsql
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
				case 'integer':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 32)/2)), "maximum" => $vb_unsigned ? pow(2,32) - 1: (pow(2,32)/2) - 1);
					break;
				case 'smallint':
					return array("type" => "int", "minimum" => $vb_unsigned ? 0 : -1 * ((pow(2, 16)/2)), "maximum" => $vb_unsigned ? pow(2,16) - 1: (pow(2,16)/2) - 1);
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
				case 'text':
					return array("type" => "text", "length" => pow(2,16) - 1);
					break;
				case 'bytea':
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
				return "smallint";
				break;
			case "char":
				return "char";
				break;
			case "varchar":
				return "varchar";
				break;
			case "text":
				return "text";
				break;
			case "blob":
				return "bytea";
				break;
			default:
				return null;
				break;
		}
	}

	/**
	 * TODO: Not implemented yet
	 * Conversion of error numbers
	 *
	 * @param int native error number
	 * @return int db error number
	 */
	function nativeToDbError($pn_error_number) {
		switch($pn_error_number) {
	/*		case 1004:	// Can't create file
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
*/
			default:
				return 250;
				break;
		}
	}

	/**
	 * Destructor
	 */
	function __destruct() {
	}
}
?>
