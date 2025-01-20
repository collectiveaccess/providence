<?php
/** ---------------------------------------------------------------------
 * app/lib/Exit/ExitManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * @subpackage Exit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace Exit;

class ExitManager {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $format = 'XML';
	
	/**
	 *
	 */
	private static $s_data_buffer_size = 10;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(?string $format='XML') {
		$this->setFormat($format);
	}
	# -------------------------------------------------------
	/**
	 * Returns list of tables to be exported
	 *
	 */
	public function getExportTableNames(?array $options=null) : array {
		$tables = caGetPrimaryTables(true, [
			'ca_relationship_types'
		], ['returnAllTables' => true]);
		
		return $tables;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function setFormat(string $format) : bool {
		$format = strtoupper($format);
		if(!in_array($format, ['XML', 'CSV'], true)) { return false; }
		$this->format = $format;
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getFormat() : string {
		return $this->format;
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 */
	public function export(string $directory, ?array $options=null) : bool {
		$tables = $this->getExportTableNames();
		
		foreach($tables as $table) {
			$this->exportTable($table, $directory, $options);
		}
		
		// Export locales & users
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 */
	public function exportTable(string $table, string $directory, ?array $options=null) : bool {
		// Get rows
		$qr = $table::findAsSearchResult('*');
		$n = $qr->numHits();
		if($n === 0) { return true; }
		print "[$table] {$n} rows\n";
		
		if(!($t = \Datamodel::getInstanceByTableName($table, true))) {
			throw new ApplicationException(_t('Invalid table: %1', $table));
		}
		
		$pk = $t->primaryKey();
		$intrinsics = array_filter($t->getFields(), function($v) use ($pk) {
			if($v === "hier_{$pk}") { return false; }
			return !in_array($v, [
				'hier_left', 'hier_right', 
				'access_inherit_from_parent', 'acl_inherit_from_ca_collections', 'acl_inherit_from_parent', 
				'idno_sort', 'idno_sort_num', 'media_metadata', 'media_content',
				'deleted'
			]);
		});
		$intrinsic_info = $t->getFieldsArray();
		foreach($intrinsic_info as $i => $d) {
			if(!in_array($i, $intrinsics, true)) {
				unset($intrinsic_info[$i]);
			}
		}
		
		
		$md = \ca_metadata_elements::getElementsAsList(true, $table, null, true, true, true);
	
		// @TODO: generate data dictionary
		
		// Marshall data X rows at a time
		$data = [];
		while($qr->nextHit()) {
			// Intrinsics
			$acc = $this->_getIntrinsics($table, $intrinsic_info, $qr);
			
			// Labels
			if(!$t->isRelationship()) {
				$acc['preferred_labels'] = $this->_getLabels($table, true, $qr);
				$acc['nonpreferred_labels'] = $this->_getLabels($table, false, $qr);
			}
			
			// Attributes
			if(is_array($md)) {
				$acc = array_merge($acc, $this->_getAttributes($table, $md, $qr));
			}
			
			$data[] = $acc;
			
			if(sizeof($data) >= self::$s_data_buffer_size) {
				// TODO: write to disk in specified format
				print_R($data);
				$data = [];
				break;
			}
		}
		
		// Format and write to disk
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getIntrinsics(string $table, array $intrinsic_info, \SearchResult $qr) : array {
		$acc = [];
		foreach($intrinsic_info as $f => $info) {
			switch($info['FIELD_TYPE']) {
				case FT_MEDIA:
					$path = $qr->get("{$table}.{$f}.original.path");
					$path = str_replace(__CA_BASE_DIR__, '', $path);
					$acc[$f] = $path;
					break;
				default:
					$acc[$f] = $qr->get("{$table}.{$f}");
					break;
			}
		}
		
		return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getLabels(string $table, bool $preferred, \SearchResult $qr) : array {
		$key = $preferred ? "preferred_labels" : "nonpreferred_labels";
		$l_acc = [];
		$pk = \Datamodel::primaryKey($table);
		if(is_array($labels = $qr->get("{$table}.{$key}", ['returnWithStructure' => true]))) {
			foreach($labels as $l) {
				$l_acc = array_merge($l_acc, $l);
			}
			foreach($l_acc as $i => $l) {
				unset($l_acc[$i]['name_sort']);
				unset($l_acc[$i]['item_type_id']);
				unset($l_acc[$i][$pk]);
			}
		}
		
		return $l_acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getAttributes(string $table, array $attributes, \SearchResult $qr) : array {
		$acc = [];
		foreach($attributes as $mdcode => $e) {
			$d = $qr->get("{$table}.{$mdcode}", ['returnWithStructure' => true]);
			
			$d_acc = [];
			if(is_array($d)) {
				foreach($d as $locale_id => $values) {
					if($e['datatype'] === 0) {
						// container
						$d_acc[] = [
							'locale_id' => $locale_id,
							'values' => array_values($values)
						];
					} else {
						$d_acc[] =[
							'locale_id' => $locale_id,
							'values' => array_map(function($v) use ($mdcode) { 
								return $v[$mdcode];
							}, array_values($values))
						];
					}
				}
			}
			$acc[$mdcode] = $d_acc;
		}
		return $acc;
	}
	# -------------------------------------------------------
}
