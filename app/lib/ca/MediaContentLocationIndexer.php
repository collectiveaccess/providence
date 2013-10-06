<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MediaContentLocationIndexer.php :
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/** 
 * Base media processing plugin
 */

include_once(__CA_LIB_DIR__."/core/Db.php");

class MediaContentLocationIndexer  {
	# ------------------------------------------------
	static $s_table_num_cache = array();
	static $s_db;
	static $s_index_insert;
	static $s_index_delete;
	static $s_index_find;
	static $s_data = array();
	# ------------------------------------------------
	/**
	 *
	 */
	static public function index($pm_table, $pn_row_id, $ps_content, $pn_page, $pn_x1, $pn_y1, $pn_x2, $pn_y2) {
		MediaContentLocationIndexer::_init();
		if (!($pn_table = MediaContentLocationIndexer::_getTableNum($pm_table))) { return null; }
		MediaContentLocationIndexer::$s_data[(int)$pn_table][(int)$pn_row_id][(string)trim($ps_content)][] = array(
			'p' => $pn_page, 'x1' => $pn_x1, 'y1' => $pn_y1, 'x2' => $pn_x2, 'y2' => $pn_y2
		);
	}
	# ------------------------------------------------
	/**
	 *
	 */
	static public function write() {
		MediaContentLocationIndexer::_init();
		
		foreach(MediaContentLocationIndexer::$s_data as $vn_table_num => $va_rows) {
			foreach($va_rows as $vn_row_id => $va_content) {
				foreach($va_content as $vs_content => $va_locs) {
					MediaContentLocationIndexer::$s_index_insert->execute((int)$vn_table_num, (int)$vn_row_id, (string)caEncodeUTF8Deep(trim($vs_content)), serialize($va_locs));	
				}
			}
		}
		
		MediaContentLocationIndexer::$s_data = array();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	static public function clear($pm_table, $pn_row_id) {
		MediaContentLocationIndexer::_init();
		MediaContentLocationIndexer::$s_data = array();
		if (!($pn_table = MediaContentLocationIndexer::_getTableNum($pm_table))) { return null; }
		return MediaContentLocationIndexer::$s_index_delete->execute((int)$pn_table, (int)$pn_row_id);
	}
	# ------------------------------------------------
	/**
	 *
	 */
	static public function find($pm_table, $pn_row_id, $ps_content) {
		MediaContentLocationIndexer::_init();
		if (!($pn_table = MediaContentLocationIndexer::_getTableNum($pm_table))) { return null; }
		$qr_res = MediaContentLocationIndexer::$s_index_find->execute($pn_table, $pn_row_id, trim($ps_content));
		
		if($qr_res->nextRow()) {
			return unserialize($qr_res->get('loc'));
		}
		return array();
	}
	# ------------------------------------------------
	/**
	 *
	 */
	static public function _getTableNum($pm_table) {
		if(!is_numeric($pm_table)) { 
			if (!isset(MediaContentLocationIndexer::$s_table_num_cache[$pm_table])) {
				$o_dm = Datamodel::load();
				MediaContentLocationIndexer::$s_table_num_cache[$pm_table] = $o_dm->getTableNum($pm_table);
			}
			$pm_table = MediaContentLocationIndexer::$s_table_num_cache[$pm_table];
		}
		
		return ((int)$pm_table > 0) ? (int)$pm_table : null;
	}
	
	# ------------------------------------------------
	/**
	 *
	 */
	static public function _init() {
		if(!MediaContentLocationIndexer::$s_db) {
			MediaContentLocationIndexer::$s_db = new Db();
			MediaContentLocationIndexer::$s_index_insert = MediaContentLocationIndexer::$s_db->prepare("INSERT INTO ca_media_content_locations (table_num, row_id, content, loc) VALUES (?, ?, ?, ?)");
			MediaContentLocationIndexer::$s_index_delete = MediaContentLocationIndexer::$s_db->prepare("DELETE FROM ca_media_content_locations WHERE table_num = ? AND row_id = ?");
			
			MediaContentLocationIndexer::$s_index_find = MediaContentLocationIndexer::$s_db->prepare("SELECT * FROM ca_media_content_locations WHERE table_num = ? AND row_id = ? AND content like ?");
		}
	}
	# ------------------------------------------------
}
?>