<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/pdo_mysql.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * MySQL driver for Db abstraction class
 *
 * You should always use the Db class as interface to the database.
 */
class Db_pdo_mysql extends DbDriverBase {
	/**
	 * MySQL database resource
	 *
	 * @var PDO
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
	public function __construct() {
		//print "Construct db driver\n";
	}

	/**
	 * Establishes a connection to the database
	 *
	 * @param mixed $po_caller representation of the caller, usually a Db() object
	 * @param array $pa_options array containing options like host, username, password
	 * @return bool success state
	 */
	public function connect($po_caller, $pa_options) {
		$vs_db_connection_key = $pa_options["host"].'/'.$pa_options["database"];

		// reuse connection
		if (
			!($vb_unique_connection = caGetOption('uniqueConnection', $pa_options, false))
			&&
			MemoryCache::contains($vs_db_connection_key, 'PdoConnectionCache')
		) {
			$this->opr_db = MemoryCache::fetch($vs_db_connection_key, 'PdoConnectionCache');
			return true;
		}
		
		if (!class_exists("PDO")) {
			die(_t("Your PHP installation lacks PDO MySQL support. Please add it and retry..."));
		}
		
		try {
			$this->opr_db = new PDO('mysql:host='.$pa_options["host"].';dbname='.$pa_options["database"], $pa_options["username"], $pa_options["password"], array(PDO::ATTR_PERSISTENT => caGetOption("persistentConnections", $pa_options, true)));
			$this->opr_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->opr_db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		} catch (Exception $e) {
			$po_caller->postError(200, $e->getMessage(), "Db->pdo_mysql->connect()");
			return false;
		}
		
		$this->opr_db->exec('SET NAMES \'utf8\'');
		$this->opr_db->exec('SET character_set_results = NULL');	
	
		if (!$vb_unique_connection) {
			MemoryCache::save($vs_db_connection_key, $this->opr_db, 'PdoConnectionCache');
		}
		return true;
	}

	/**
	 * Closes the connection if it exists
	 *
	 * @return bool success state
	 */
	public function disconnect() {
		$this->opr_db = null;
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
	public function prepare($po_caller, $ps_sql) {
		$this->ops_sql = $ps_sql;
		
		$vs_md5 = md5($ps_sql);

		/*// is prepared statement cached?
		if(MemoryCache::contains($vs_md5, 'PdoStatementCache')) {
			return MemoryCache::fetch($vs_md5, 'PdoStatementCache');
		}

		if(MemoryCache::itemCountForNamespace('PdoStatementCache') >= 2048) {
			MemoryCache::flush('PdoStatementCache');
		}*/

		$o_pdo_stmt = $this->opr_db->prepare($ps_sql);
		$o_statement = new DbStatement($this, $this->ops_sql, array('native_statement' => $o_pdo_stmt));

		//MemoryCache::save($vs_md5, $o_statement, 'PdoStatementCache');
		return $o_statement;
	}

	/**
	 * Executes a SQL statement
	 *
	 * @param mixed $po_caller object representation of the calling class, usually Db()
	 * @param PDOStatement $opo_statement
	 * @param string $ps_sql SQL statement
	 * @param array $pa_values array of placeholder replacements
	 * @return bool|DbResult
	 */
	public function execute($po_caller, $opo_statement, $ps_sql, $pa_values) {
		if (!$ps_sql) {
			$po_caller->postError(240, _t("Query is empty"), "Db->pdo_mysql->execute()");
			return false;
		}

		if(!($opo_statement instanceof PDOStatement)) {
			$po_caller->postError(250, _t("Invalid prepared statement"), "Db->pdo_mysql->execute()");
		}
	
		if (Db::$monitor) {
			$t = new Timer();
		}
		try {
			$opo_statement->execute((is_array($pa_values) && sizeof($pa_values)) ? array_values($pa_values) : null);
		} catch(PDOException $e) {
			$po_caller->postError($po_caller->nativeToDbError($this->opr_db->errorCode()), $e->getMessage().((__CA_ENABLE_DEBUG_OUTPUT__) ? "\n<pre>".caPrintStacktrace()."\n{$ps_sql}</pre>" : ""), "Db->pdo_mysql->execute()");
			return false;
		}
		
		if (Db::$monitor) {
			Db::$monitor->logQuery($ps_sql, $pa_values, $t->getTime(4), $opo_statement->rowCount());
		}
		return new DbResult($this, $opo_statement);
	}

	/**
	 * Fetches the ID generated by the last MySQL INSERT statement
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @return int the ID generated by the last MySQL INSERT statement
	 */
	public function getLastInsertID($po_caller) {
		return $this->opr_db->lastInsertId();
	}

	/**
	 * How many rows have been affected by your query?
	 *
	 * @param mixed $po_caller object representation of calling class, usually Db
	 * @return int number of rows
	 */
	public function affectedRows($po_caller) {
		return $this->opr_db->rowCount();
	}

	/**
	 * @see Db::escape()
	 * @param string
	 * @return string
	 */
	public function escape($ps_text) {
		// BaseModel doesn't expect the string quoted but PDO does add quotes,
		// so in order to not end up with ''string'' in queries, we strip the
		// PDO quotes here to mirror the mysql / mysqli behavior
		$vs_text = $this->opr_db->quote($ps_text);
		return mb_substr($vs_text,1,-1);
	}

	/**
	 * @see Db::beginTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	public function beginTransaction($po_caller) {
		if (!$this->opr_db->beginTransaction()) {
			$po_caller->postError(250, "Could not start transaction", "Db->pdo_mysql->beginTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see Db::commitTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	public function commitTransaction($po_caller) {
		if (!$this->opr_db->commit()) {
			$po_caller->postError(250, "Could not commit transaction", "Db->pdo_mysql->commitTransaction()");
			return false;
		}
		return true;
	}

	/**
	 * @see Db::rollbackTransaction()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return bool success state
	 */
	public function rollbackTransaction($po_caller) {
		if (!$this->opr_db->rollBack()) {
			$po_caller->postError(250, "Could not rollback transaction", "Db->pdo_mysql->rollbackTransaction()");
			return false;
		}
		return true;
	}
	
	/**
	 * @see DbResult::getAllFieldValues()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param PDOStatement $pr_res mysql resource
	 * @param mixed $pm_field the field or an array of fields
	 * @return array an array of field values (if $pm_field is a single field name) or an array if field names each of which is an array of values (if $pm_field is an array of field names)
	 */
	function getAllFieldValues($po_caller, $pr_res, $pm_field) {
		$va_vals = array();
		
		if (is_array($pm_field)) {
			$va_rows = $pr_res->fetchAll(PDO::FETCH_ASSOC);
			foreach($va_rows as $va_row) {
				foreach($pm_field as $vs_field) {
					if(isset($va_row[$vs_field])) {
						$va_vals[$vs_field][] = $va_row[$vs_field];
					}
				}
			}
		} else {
			$va_rows = $pr_res->fetchAll(PDO::FETCH_ASSOC);
			foreach($va_rows as $va_row) {
				if(isset($va_row[$pm_field])) {
					$va_vals[] = $va_row[$pm_field];
				}
			}
		}
		return $va_vals;
	}

	/**
	 * @see DbResult::nextRow()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param PDOStatement $pr_res mysql resource
	 * @return array array representation of the next row
	 */
	public function nextRow($po_caller, $po_stmt) {
		if (!($po_stmt instanceof PDOStatement)) { return null; }
		$va_row = $po_stmt->fetch(PDO::FETCH_ASSOC);
		if (!is_array($va_row)) { return null; }

		return $va_row;
	}

	/**
	 * @see DbResult::seek()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param PDOStatement $pr_res mysql resource
	 * @param int $pn_offset line number to seek
	 * @return array array representation of the next row
	 */
	public function seek($po_caller, &$pr_res, $pn_offset) {
		// seek is not supported by pdo
		return false;
	}

	/**
	 * @see DbResult::numRows()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param PDOStatement $pr_res mysql resource
	 * @return int number of rows
	 */
	public function numRows($po_caller, $pr_res) {
		return $pr_res->rowCount();
	}

	/**
	 * @see DbResult::free()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param PDOStatement $pr_res mysql resource
	 * @return bool success state
	 */
	public function free($po_caller, $pr_res) {
		if($pr_res instanceof PDOStatement) {
			$pr_res->closeCursor();
		}
		return true;
	}

	/**
	 * @see Db::supports()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @param string $ps_key feature to look for
	 * @return bool|int
	 */
	public function supports($po_caller, $ps_key) {
		return $this->opa_features[$ps_key];
	}

	/**
	 * @see Db::getTables()
	 * @param mixed $po_caller object representation of the calling class, usually Db
	 * @return array field list, false on error
	 */
	public function &getTables($po_caller) {
		try {
			$r_show = $this->opr_db->query("SHOW TABLES");
			$va_tables = array();
			$va_results = $r_show->fetchAll(PDO::FETCH_NUM);
			foreach($va_results as $va_result) {
				$va_tables[] = $va_result[0];
			}

			return $va_tables;
		} catch(PDOException $e) {
			$po_caller->postError(280, $e->getMessage(), "Db->pdo_mysql->getTables()");
			return false;
		}
	}

	/**
	 * Get database connection handle
	 *
	 * @return resource 
	 */
	public function getHandle() {
		return $this->opr_db;
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		// Disconnecting here can affect other classes that need
		// to clean up by writing to the database so we disabled 
		// disconnect-on-destruct
		//$this->disconnect();
	}
}
?>