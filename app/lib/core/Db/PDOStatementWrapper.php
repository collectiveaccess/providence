<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/PDOStatementWrapper.php
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
 * Wrapper for PDOStatement objects 
 * 
 * Requires the PDO driver to return the number of rows in a SELECT result in
 * PDOStatemment::rowCount()
 */

class PDOStatementWrapper{
	/**
	 * PDOStatement object
	 *
	 * @access private
	 */
	var $opo_stmt;

	/**
	 * Array containing all currently fetched rows
	 *
	 * @access private
	 */
	var $opa_res;

	/**
	 * Cursor position
	 *
	 * @access private
	 */
	var $opn_cursor;

	/**
	 * Constructor
	 *
	 * 
	 */
	public function __construct($po_stmt) {
		// TODO: make this a bit less resource intensive :)
		if(is_object($po_stmt)){
			$this->opo_stmt = $po_stmt;
			$this->opa_res = $po_stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->opn_cursor = 0;
		}
	}
	/**
	 * Returns cursor postition
	 *
	 * @return int cursor position
	 */
	public function getCursor(){
		return $this->opn_cursor;
	}

	/**
	 * Sets new postition in result set
	 * 
	 * Next call to PDOScrollable::getRow() returns the row at position $pn_newval
     *
     * @param int new cursor position 
	 * @return bool success state
	 */
	public function seek($pn_newval){
		if( ($pn_newval < $this->rowCount()) &&
			($pn_newval >= 0)){
			$this->opn_cursor = $pn_newval;
			return true;
		}	
		return false;
	}

	/**
	 * Gets next row in result set and advances the cursor
	 * 
	 * @return mixed array representation of the next row
	 */
	public function getRow(){
		if($this->opn_cursor < $this->rowCount()){
			$va_row = $this->opa_res[$this->opn_cursor];
			$this->opn_cursor++;
			return $va_row;
		}
		else{
			return false;
		}
			
	}
	
	public function getAllRows()
	{
		return $this->opa_res;
	}

	public function rowCount()
	{
		return count($this->opa_res);
	}

}
?>
