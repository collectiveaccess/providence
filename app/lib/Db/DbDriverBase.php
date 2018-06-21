<?php
/** ---------------------------------------------------------------------
 * app/lib/Db/DbDriverBase.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2006-2008 Whirl-i-Gig
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

/**
 * Provides common functionality for all Db drivers who should extend this class.
 */
class DbDriverBase {

	/**
	 * Default constructor
	 */
	function __construct() {

	}

	/**
	 * Escapes certain characters in the string and puts the whole string in quotes.
	 *
	 * @access private
	 * @param string $ps_text
	 * @return string
	 */
	function quote($ps_text) {
		return "'".$this->escape($ps_text)."'";
	}

	/**
	 * Escapes certain characters in the parameter and puts it in quotes if it is a string.
	 * If the parameter is not a string, it returns a representation suitable for SQL use.
	 *
	 * @param mixed $ps_text
	 * @return mixed
	 */
	function autoQuote($pm_value) {
		if (is_int($pm_value) || is_double($pm_value)) {
			return $pm_value;
		} elseif (is_bool($pm_value)) {
			return $pm_value ? 1 : 0;
		} elseif (is_null($pm_value)) {
			return 'NULL';
		} else {
			return $this->quote($pm_value);
		}
	}

	/**
	 * Wrapper around the PHP function addslashes() which escapes certain characters.
	 *
	 * @param string $ps_text
	 * @return string
	 */
	function escape($ps_text) {
		return addslashes($ps_text);
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
}

class DatabaseException extends Exception {
	private $opn_error_number = null;
	private $ops_error_context = null;
	
	public function __construct($ps_error_message, $pn_error_number, $ps_error_context=null) {
		parent::__construct($ps_error_message);
		$this->opn_error_number = $pn_error_number;
		$this->ops_error_context = $ps_error_context;
	}
	
	public function getNumber() {
		return $this->opn_error_number;
	}
	
	public function getContext() {
		return $this->ops_error_context;
	}
}