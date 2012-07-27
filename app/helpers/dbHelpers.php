<?php
/** ---------------------------------------------------------------------
 * app/helpers/dbHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   
	# ---------------------------------------
	/**
	 * The first field of each row of the result of $ps_select_sql is checked against $ps_dest_table 
	 * If a match is found, then update the destination row with the result. Otherwise insert a new row.
	 * Takes additional arguments to the SELECT statement like Db::query()
	 *
	 * @param object $po_db Db object
	 * @param string $ps_dest_table Destination table
	 * @param string $ps_select_sql SQL SELECT statement
	 * @param string (multiple) placeholder values 
	 */
	function caSQLInsertIgnore($po_db, $ps_dest_table, $ps_select_sql){
		$va_args = func_get_args();
		array_shift($va_args);
		array_shift($va_args);
		array_shift($va_args);
		if(empty($va_args)){
			$vo_res = $po_db->query($ps_select_sql);
		}
		else{
			$vo_res = $po_db->query($ps_select_sql, $va_args);
		}
		$va_select_res = $vo_res->getAllRows();
		if(empty($va_select_res[0])){
			return;
		}
		$va_fields = array_keys($va_select_res[0]);
		foreach($va_select_res as $va_select_row){
			$vs_select_sql = "SELECT * FROM {$ps_dest_table} WHERE {$va_fields[0]} = {$va_select_row[$va_fields[0]]}";
			$vo_res = $po_db->query($vs_select_sql);
			$vo_res->nextRow();
			$va_row = $vo_res->getRow();
			if(!empty($va_row)){
				$va_updates = array();
				foreach($va_fields as $vs_field){
					$va_updates[] = "{$vs_field} = {$va_row[$vs_field]}";
				}
				$vs_sql = "UPDATE {$ps_dest_table} 
					SET ".join(", ", $va_updates)."
					WHERE {$va_fields[0]} = {$va_row[$va_fields[0]]}";
			}
			else{
				$vs_sql = "INSERT INTO {$ps_dest_table} (".join(", ", $va_fields).") 
					VALUES (".join(", ", $va_select_row).")";
			}
			$po_db->query($vs_sql);
		}
	}
	# ---------------------------------------
?>
