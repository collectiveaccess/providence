<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Db/PDOStatement_pgsql.php
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
 * Wrapper for PDOStatement objects.
 * 
 * Requires the PDO driver to return the number of rows in a SELECT result in
 * PDOStatemment::rowCount()
 */

class PDOStatement_pgsql{
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
		if(is_object($po_stmt)){
			$this->opo_stmt = $po_stmt;
			$this->opa_res = array();
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
	 * Next call to PDOStatement_pgsql::getRow() returns the row at position $pn_newval
     *
     * @param int new cursor position 
	 * @return bool success state
	 */
	public function seek($pn_newval){
		if( ($pn_newval < $this->rowCount()) &&
			($pn_newval >= 0)){
			if($pn_newval >= count($opa_res)){ 
				$this->opn_cursor = count($opa_res);
				while($this->opn_cursor < $pn_newval){
						$this->getRow();
				}
			}
			else{
				$this->opn_cursor = $pn_newval;
			}
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
			if($this->opn_cursor == count($this->opa_res)){
				$this->opa_res[] = $this->opo_stmt->fetch(PDO::FETCH_ASSOC);
			}
			return $this->opa_res[$this->opn_cursor++];
		}
		else{
			return false;
		}
	}
	
	public function getAllRows()
	{
		$this->seek($this->rowCount() - 1);
		return $opa_res;
	}

	public function rowCount()
	{
		return $this->opo_stmt->rowCount();
	}

}
?>
