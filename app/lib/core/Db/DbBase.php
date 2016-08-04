<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/DbBase.php :
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

require_once(__CA_LIB_DIR__.'/core/BaseObject.php');
/**
 * Provides common error handling methods
 */
class DbBase {
	/**
	 * Should we die on error?
	 *
	 * @access private
	 */
	var $opb_die_on_error = true;

	/**
	 * List of errors
	 *
	 * @access private
	 */
	var $errors = array();

	/**
	 * Defines if the application dies if an error occurrs. Default is true.
	 *
	 * @param bool $pb_die_on_error
	 */
	function dieOnError($pb_die_on_error=true) {
		$this->opb_die_on_error = $pb_die_on_error;
	}

	/**
	 * Do we die on error?
	 *
	 * @return bool
	 */
	function getDieOnError() {
		return $this->opb_die_on_error;
	}

	/**
	 * How many errors occurred?
	 *
	 * @return int
	 */
	function numErrors() {
		return sizeof($this->errors);
	}

	/**
	 * Fetches the errors. Returns an array of Error objects.
	 *
	 * @return array
	 */
	function errors() {
		return $this->errors;
	}

	/**
	 * Fetches the error. Returns an array containing the error descriptions.
	 *
	 * @return array
	 */
	function getErrors() {
		$va_error_descs = array();
		if (sizeof($this->errors)) {
			foreach ($this->errors as $o_e) {
				array_push($va_error_descs,$o_e->getErrorDescription());
			}
		}
		return $va_error_descs;
	}

	/**
	 * Clears all errors.
	 *
	 * @return bool
	 */
	function clearErrors() {
		$this->errors = array();
		return true;
	}

	/**
	 * Adds a new error
	 *
	 * @param int $pn_num
	 * @param string $ps_message
	 * @param string $ps_context
	 */
	function postError($pn_num, $ps_message, $ps_context, $ps_source='') {
		$o_error = new ApplicationError();
		$o_error->setErrorOutput($this->opb_die_on_error);
		$o_error->setHaltOnError($this->opb_die_on_error);
		$o_error->setError($pn_num, $ps_message, $ps_context, $ps_source);
		array_push($this->errors, $o_error);
		return true;
	}
	# ---------------------------------------------------------------------------
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
}
