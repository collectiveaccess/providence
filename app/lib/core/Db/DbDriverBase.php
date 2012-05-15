<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/DbDriverBase.php :
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
}
?>