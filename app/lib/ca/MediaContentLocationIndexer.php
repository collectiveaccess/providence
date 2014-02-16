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
	static $s_index_check;
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
	static function hasIndexing($pm_table, $pn_row_id) {
		MediaContentLocationIndexer::_init();
		if (!($pn_table = MediaContentLocationIndexer::_getTableNum($pm_table))) { return null; }
		$qr_res = MediaContentLocationIndexer::$s_index_check->execute($pn_table, $pn_row_id);
		
		if($qr_res->nextRow()) {
			return true;
		}
		return false;
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
			MediaContentLocationIndexer::$s_index_check = MediaContentLocationIndexer::$s_db->prepare("SELECT row_id FROM ca_media_content_locations WHERE table_num = ? AND row_id = ? LIMIT 1");
		}
	}
	# ------------------------------------------------
	/**
	 *
	 */
	static function SearchWithinMedia($ps_query, $pm_table, $pn_row_id, $ps_field) {
		$o_dm = Datamodel::load();
		$o_config = Configuration::load();
		$o_search_config = Configuration::load($o_config->get('search_config'));
		$vs_indexing_regex = $o_search_config->get('indexing_tokenizer_regex');
		$va_words = preg_split("![{$vs_indexing_regex}]!", $ps_query);
		
		$va_results = array(
			'matches' => 0,
			'results' => array(),
			'locations' => array(),
			'query' => $ps_query
		);
		
		if (!($t_instance = is_numeric($pm_table) ? $o_dm->getInstanceByTableNum($pm_table) : $o_dm->getInstanceByTableName($pm_table))) {
			throw new Exception(_t("Invalid table %1", $pm_table));
		}
		if (!$t_instance->load($pn_row_id)) {
			throw new Exception(_t("Invalid row %2 for table %1", $pm_table, $pn_row_id));
		}
		if (!$t_instance->hasField($ps_field)) {
			throw new Exception(_t("Invalid field %2 for table %1", $pm_table, $ps_field));
		}
		$va_media_info = $t_instance->getMediaInfo($ps_field);
		$vn_page_width = $va_media_info['INPUT']['WIDTH'];
		$vn_page_height = $va_media_info['INPUT']['HEIGHT'];

		$vn_page_image_width = $va_media_info['large']['WIDTH'];
		$vn_page_image_height = $va_media_info['large']['HEIGHT'];
		
		$va_hit_acc = array();
		foreach($va_words as $vn_word_num => $vs_word) {
			
			$va_hits = MediaContentLocationIndexer::find($t_instance->tableName(), $pn_row_id, $vs_word);
		
			$va_hit_acc_matched = array();
			if (is_array($va_hits) && sizeof($va_hits)) {
				$va_pages = array();
				foreach($va_hits as $va_hit) {
				
					$x1_percent = $va_hit['x1']/$vn_page_width;
					$x2_percent = $va_hit['x2']/$vn_page_width;
					$y1_percent = ($vn_page_height-$va_hit['y2']) / $vn_page_height;
					$y2_percent = ($vn_page_height-$va_hit['y1']) / $vn_page_height;
						
					// Is this hit part of a phrase match?
					if ($vn_word_num > 0) {
						if(!is_array($va_hit_acc[$va_hit['p']]) || !sizeof(is_array($va_hit_acc[$va_hit['p']]))) { continue(2); } 	// if page is empty after the first word is processed then we can skip checking the rest of the words
						
						$vn_i = -1;
						foreach($va_hit_acc[$va_hit['p']] as $vn_i => $va_existing_loc) {
							if ($va_existing_loc['c'] >= ($vn_word_num + 1)) { continue; }
							
							// Check if word is to the right of and on the same line as the previous one
							if (
								((abs($x1_percent - $va_existing_loc['x2'])) > 0.08)
								||
								((abs($y1_percent - $va_existing_loc['y1'])) > 0.005)
							) {
								// Check if word to to the bottom and right of the previous one
								if (
									(abs(($y2_percent - ($va_existing_loc['y2'] + ($va_existing_loc['y2'] - $va_existing_loc['y1'])))) < 0.003)
									&& 
									($va_existing_loc['x1'] > $x1_percent)
								) {
									if (isset($va_hit_acc[$va_hit['p']][$vn_i]['isNewlined']) && $va_hit_acc[$va_hit['p']][$vn_i]['isNewlined']) { continue; }
									$va_hit_acc[$va_hit['p']][$vn_i]['c'] = $vn_word_num + 1;
									$va_hit_acc[$va_hit['p']][$vn_i]['isNewlined'] = true;
									// word is part of phrase but on new line so create new hit for it
									array_splice($va_hit_acc[$va_hit['p']], $vn_i, 0, array(array('word' => "newline $vs_word", 'x1' => $x1_percent, 'y1' => $y1_percent, 'x2' => $x2_percent, 'y2' => $y2_percent, 'c' => $vn_word_num + 1, 'previousLine' => &$va_hit_acc[$va_hit['p']][$vn_i])));
									continue;
								} else {
									// Word isn't near the previous one so skip the hit
									continue;
								}
							}
							
							// extend selection to encompass this word in the same line as previous
							$va_hit_acc[$va_hit['p']][$vn_i]['x2'] = $x2_percent;
							$va_hit_acc[$va_hit['p']][$vn_i]['y2'] = $y2_percent;
							$va_hit_acc[$va_hit['p']][$vn_i]['c'] = $vn_word_num + 1;
							$va_hit_acc[$va_hit['p']][$vn_i]['word'] .= "extend $vs_word";
							if ($va_hit_acc[$va_hit['p']][$vn_i]['previousLine']) { $va_hit_acc[$va_hit['p']][$vn_i]['previousLine']['c'] = $vn_word_num + 1; }
							
							break;
						}
						
					} else {
						$va_hit_acc[$va_hit['p']][] = array('word' => "add $vs_word", 'x1' => $x1_percent, 'y1' => $y1_percent, 'x2' => $x2_percent, 'y2' => $y2_percent, 'c' => $vn_word_num + 1);
					}
					
				}
			}
			
			// Remove all previous hits that didn't line up with the current word
			foreach($va_hit_acc as $vn_p => $va_hits_per_page) {
				foreach($va_hits_per_page as $vn_i => $va_hit) {
					if ($va_hit['c'] < ($vn_word_num + 1)) { 
						unset($va_hit_acc[$vn_p][$vn_i]);
					}
				}
			}
		}
	
		// Copy hits into final locations list with coordinates translated into pixel values
		foreach($va_hit_acc as $vn_p => $va_hits_per_page) {
			foreach($va_hits_per_page as $vn_i => $va_hit) {
				if ($va_hit['c'] < sizeof($va_words)) { continue; }
				$va_results['results'][] = $vn_p;
				$x1r = ($va_hit['x1'] * $vn_page_image_width) + 2;
				$x2r = ($va_hit['x2'] * $vn_page_image_width) + 12;
				
				$y1r = $va_hit['y1'] * $vn_page_image_height;
				$y2r = $va_hit['y2'] * $vn_page_image_height;
				
				$va_results['locations'][$vn_p][] = array('c' => $va_hit['c'], 'word' => $va_hit['word'], 'x1' => $x1r, 'y1' => $y1r, 'x2' => $x2r, 'y2' => $y2r);
			}
		}
		$va_results['matches'] = sizeof($va_results['results']);
		
		return $va_results;
	}
	# ------------------------------------------------
}
?>