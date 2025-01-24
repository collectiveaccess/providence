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
		if(!in_array($format, ['XML'], true)) { return false; }
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
		
		if(!($t = \Datamodel::getInstanceByTableName($table, true))) {
			throw new ApplicationException(_t('Invalid table: %1', $table));
		}
		
		$show_progress = caGetOption('showProgress', $options, false);
		
		// Generate table header
		$header = [
			'table' => $table,
			'name' => $table_display = \Datamodel::getTableProperty($table, 'NAME_PLURAL'),
			'count' => $n,
			'types' => $t->getTypeList(),
			'exportDate' => date('c')
		];
		if ($show_progress) { print \CLIProgressBar::start($n, _t('[%1] Starting', $table_display)); }
		
		// Generate data dictionary
		$dictionary = [];
		if(!($is_relationship = $t->isRelationship())) { 
			// @TODO: generate dictionary for sub-fields
			$dictionary = [
				'preferred_labels' => [
					'name' => 'Preferred labels',
					'description' => '',
					'type' => 'labels',
					'canRepeat' => true
				],
				'nonpreferred_labels' => [
					'name' => 'Non-preferred labels',
					'description' => '',
					'type' => 'labels',
					'canRepeat' => true
				]
			];
		}
			
		$pk = $t->primaryKey();
		$intrinsics = array_filter($t->getFields(), function($v) use ($pk) {
			if($v === "hier_{$pk}") { return false; }
			return !in_array($v, [
				'hier_left', 'hier_right', 
				'access_inherit_from_parent', 'acl_inherit_from_ca_collections', 'acl_inherit_from_parent', 
				'idno_sort', 'idno_sort_num', 'media_metadata', 'media_content',
				'deleted', 'submission_user_id', 'submission_group_id', 'submission_status_id',
				'submission_via_form', 'submission_session_id'
			]);
		});
		$intrinsic_info = $t->getFieldsArray();
		foreach($intrinsic_info as $f => $d) {
			if(!in_array($f, $intrinsics, true)) {
				unset($intrinsic_info[$f]);
				continue;
			}
			if ($f === 'locale_id') { 
				$f = 'locale'; 
				$d['FIELD_TYPE'] = FT_TEXT;
			}
			$dictionary[$f] = [
				'name' => $d['LABEL'],
				'description' => $d['DESCRIPTION'],
				'type' => $this->_intrinsicTypeToDictionaryType($d['FIELD_TYPE']),
				'canRepeat' => false
			];
			
			if($d['LIST']) {
				$dictionary[$f]['list_id'] = caGetListID($d['LIST']);
				$dictionary[$f]['list_code'] = $d['LIST'];
			}

			if($d['LIST_CODE']) {
				$dictionary[$f]['list_id'] = caGetListID($d['LIST_CODE']);
				$dictionary[$f]['list_code'] = $d['LIST_CODE'];
			}
			
			if(is_array($rel = \Datamodel::getManyToOneRelations($table, $f)) && sizeof($rel)) {
				$dictionary[$f]['related_to_table'] = $rel['one_table'];
				$dictionary[$f]['related_to_field'] = $rel['one_table_field'];
			}
		}
		
		
		if(is_array($md = \ca_metadata_elements::getElementsAsList(true, $table, null, true, true, true))) {
			$dictionary = array_merge($this->_genAttributeDictionary($md));
		}
		
		// Marshall data X rows at a time
		$data = [];
		
		$format = $this->getFormatWriter($this->getFormat(), $directory, $table, $options);
		$format->setHeader($header);
		$format->setDictionary($dictionary);
		while($qr->nextHit()) {
			$id = $qr->getPrimaryKey();
			if ($show_progress) { print \CLIProgressBar::next(1, _t('[%1] Processing %2', $table_display, $id)); }
			
			// Intrinsics
			$acc = $this->_getIntrinsics($table, $intrinsic_info, $qr, ['isRelationship' => $is_relationship]);
			
			// Labels
			if(!$is_relationship) {
				$acc['preferred_labels'] = $this->_getLabels($table, true, $qr);
				$acc['nonpreferred_labels'] = $this->_getLabels($table, false, $qr);
			}
			
			// Attributes
			if(is_array($md)) {
				$acc = array_merge($acc, $this->_getAttributes($table, $md, $qr));
			}
			
			$acc['_guid'] = $qr->get("{$table}._guid");
			
			$data[] = $acc;
			
			if(sizeof($data) >= self::$s_data_buffer_size) {
				$format->process($data, ['primaryKey' => $t->primaryKey()]);
				$data = [];
			}
		}
		if(sizeof($data) > 0) {
			$format->process($data);
		}
		if ($show_process) { print \CLIProgressBar::finish(); }
		$format->write();
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getIntrinsics(string $table, array $intrinsic_info, \SearchResult $qr, ?array $options=null) : array {
		$acc = [];
		
		$is_relationship = caGetOption('isRelationship', $options, false);
		foreach($intrinsic_info as $f => $info) {
			switch($info['FIELD_TYPE']) {
				case FT_MEDIA:
					$path = $qr->get("{$table}.{$f}.original.path");
					$path = str_replace(__CA_BASE_DIR__, '', $path);
					$acc[$f] = $path;
					break;
				default:
					$acc[$f] = $qr->get("{$table}.{$f}");
					
					if($f === 'locale_id') {
						$acc['locale'] = \ca_locales::IDToCode($acc[$f]);
						unset($acc[$f]);
					} elseif(isset($info['LIST_CODE'])) {
						$acc[$f] = [
							[
								'_id' => $acc[$f],
								'_idno' => caGetListItemIdno($acc[$f])
							]
						];
					} elseif(isset($info['LIST'])) {
						$id = caGetListItemIDForValue($info['LIST'], $acc[$f]);
						$acc[$f] = [
							[
								'_id' => $id,
								'_idno' => caGetListItemIdno($id)
							]
						];
					} elseif($is_relationship && ($f === 'type_id')) {
						if($t_rel = \ca_relationship_types::findAsInstance($acc[$f])) {
							$acc[$f] = [
								[
									'_id' => $acc[$f],
									'_idno' => $t_rel->get('ca_relationship_types.type_code')
								]
							];
						}
					} 
					break;
			}
		}
		
		return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getLabels(string $table, bool $preferred, \SearchResult $qr, ?array $options=null) : array {
		$key = $preferred ? "preferred_labels" : "nonpreferred_labels";
		$l_acc = [];
		$pk = \Datamodel::primaryKey($table);
		$t = $qr->getInstance();
		$t_label = $t->getLabelTableInstance();
		if(is_array($labels = $qr->get("{$table}.{$key}", ['returnWithStructure' => true]))) {
			foreach($labels as $l) {
				$l_acc = array_merge($l_acc, $l);
			}
			foreach($l_acc as $i => $l) {
				unset($l_acc[$i]['name_sort']);
				unset($l_acc[$i]['item_type_id']);
				unset($l_acc[$i]['source_info']);
				unset($l_acc[$i]['is_preferred']);
				unset($l_acc[$i][$pk]);
				
				if($l_acc[$i]['locale_id']) { 
					$l_acc[$i]['locale'] = \ca_locales::IDToCode($l_acc[$i]['locale_id']); 
					unset($l_acc[$i]['locale_id']);
				} 
				if($l_acc[$i]['type_id']) {
					$vx = [
						'_id' => $l_acc[$i]['type_id'],
						'_idno' => caGetListItemIdno($l_acc[$i]['type_id'])
					];
					$l_acc[$i]['type_id'] = $vx;
				}
				if($l_acc[$i]['access']) {
					$id = caGetListItemIDForValue($t_label->getFieldInfo('access', 'LIST'), $l_acc[$i]['access']);
					
					$vx = [
						'_id' => $id,
						'_idno' => caGetListItemIdno($id)
					];
					$l_acc[$i]['access'] = $vx;
				}
			}
		}
		
		return $l_acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _getAttributes(string $table, array $attributes, \SearchResult $qr, ?array $options=null) : array {
		$acc = [];
		foreach($attributes as $mdcode => $e) {
			$d = $qr->get("{$table}.{$mdcode}", ['returnWithStructure' => true]);
			
			// @TODO: add source info
			$d_acc = [];
			if(is_array($d)) {
				foreach($d as $locale_id => $values) {
					if((int)$e['datatype'] === __CA_ATTRIBUTE_VALUE_LIST__) {
						foreach($values as $vx) {
							$vx = [
								'_id' => $vx[$mdcode],
								'_idno' => caGetListItemIdno($vx[$mdcode]),
								'_source' => $vx['__source__'] ?? null
							];
							$d_acc[] = array_merge([
								'_datatype' => $e['datatype'],
								'locale' => \ca_locales::IDToCode($locale_id)
							], $vx);
						}
					} elseif((int)$e['datatype'] === __CA_ATTRIBUTE_VALUE_CONTAINER__) {
						foreach($values as $vx) {
							foreach($vx as $sf => $sv) {
								if(preg_match("!_sort_$!", $sf)) { unset($vx[$sf]); continue; }
								$sdt = \ca_metadata_elements::getElementDatatype($sf);
								$is_authority = \ca_metadata_elements::isAuthorityDatatype($sf);
								if($sdt == __CA_ATTRIBUTE_VALUE_LIST__) {
									$vx[$sf] = [
										'_id' => $sv,
										'_idno' => caGetListItemIdno($vx[$sf]),
										'__source__' => $vx['__source__']
									];
								} elseif($is_authority && ($t = \AuthorityAttributeValue::elementTypeToInstance($sdt))) {
									$labels = $t->getPreferredDisplayLabelsForIDs([$vx[$sf]]);
									$vx[$sf] = [
										'_id' => $vx[$sf],
										'_idno' => array_shift($labels),
										'__source__' => $vx['__source__']
									];
								}
							}
						
							$d_acc[] = array_merge([
								'_datatype' => $e['datatype'],
								'locale' => \ca_locales::IDToCode($locale_id),
							], $vx);
						}
					} else {
						$is_authority = \ca_metadata_elements::isAuthorityDatatype($mdcode);
						foreach($values as $vx) {
							foreach($vx as $sf => $sv) {
								if(preg_match("!_sort_$!", $sf)) { unset($vx[$sf]); }
								if($sf === '__source__') { continue; }
								
								if($is_authority && ($t = \AuthorityAttributeValue::elementTypeToInstance($e['datatype']))) {
									$labels = $t->getPreferredDisplayLabelsForIDs([$sv]);
									$vx[$sf] = [
										'_id' => $sv,
										'_idno' => array_shift($labels),
										'__source__' => $vx['__source__']
									];
									$d_acc[] = array_merge([
										'_datatype' => $e['datatype'],
										'locale' => \ca_locales::IDToCode($locale_id)
									], $vx[$sf]);
								} else {									
									$d_acc[] = [
										'_datatype' => $e['datatype'],
										'locale' => \ca_locales::IDToCode($locale_id),
										'__source__' => $vx['__source__'],
										$sf => $vx[$sf]
									];
								}
							}
						}
					}
				}
			}
			$acc[$mdcode] = $d_acc;
		}
		return $acc;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function getFormatWriter(string $format, string $directory, string $file, ?array $options) : \Exit\Formats\BaseExitFormat {
		$format = strtoupper($format);
		
		$p = "\\Exit\Formats\\{$format}";
		return new $p($directory, $file, $options);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _intrinsicTypeToDictionaryType(int $type) : ?string {
		switch($type) {
			case FT_NUMBER:
				return 'number';
			case FT_TEXT:
				return 'text';			
			case FT_TIMESTAMP:
				return 'timestamp';			
			case FT_DATETIME:
				return 'unixtime';				
			case FT_HISTORIC_DATETIME:
				return 'historic_datetime';				
			case FT_DATERANGE:
				return 'unixtimerange';			
			case FT_HISTORIC_DATERANGE:
				return 'historic_datetime_range';				
			case FT_BIT:
				return 'bit';				
			case FT_FILE:
				return 'file';				
			case FT_MEDIA:
				return 'media';				
			case FT_PASSWORD:
				return 'text';				
			case FT_VARS:
				return 'json';				
			case FT_TIMECODE:
				return 'timecode';				
			case FT_DATE:
				return 'unixtime';				
			case FT_HISTORIC_DATE:
				return 'historic_datetime';				
			case FT_TIME:
				return 'time';			
			case FT_TIMERANGE:
				return 'timerange';				
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _attributeTypeToDictionaryType(int $type) : ?string {
		switch($type) {
			case __CA_ATTRIBUTE_VALUE_TEXT__:
				return 'text';
			case __CA_ATTRIBUTE_VALUE_CONTAINER__:
				return 'container';			
			case __CA_ATTRIBUTE_VALUE_CURRENCY__:
				return 'currency';			
			case __CA_ATTRIBUTE_VALUE_DATERANGE__:
				return 'historic_datetime_range';			
			case __CA_ATTRIBUTE_VALUE_FILE__:
				return 'file';			
			case __CA_ATTRIBUTE_VALUE_FLOORPLAN__:
				return 'json';	
			case __CA_ATTRIBUTE_VALUE_GEOCODE__:
				return 'geocode';	
			case __CA_ATTRIBUTE_VALUE_GEONAMES__:
				return 'geonames';
			case __CA_ATTRIBUTE_VALUE_INFORMATIONSERVICE__:
				return 'json';	
			case __CA_ATTRIBUTE_VALUE_LCSH__:
				return 'lcsh';	
			case __CA_ATTRIBUTE_VALUE_INTEGER__:
				return 'number';
			case __CA_ATTRIBUTE_VALUE_MEDIA__:
				return 'media';	
			case __CA_ATTRIBUTE_VALUE_NUMERIC__:
				return 'number';	
			case __CA_ATTRIBUTE_VALUE_TIMECODE__:
				return 'timecode';	
			case __CA_ATTRIBUTE_VALUE_LIST__:
				return 'listitem';
			case __CA_ATTRIBUTE_VALUE_WEIGHT__:
				return 'weight';	
			case __CA_ATTRIBUTE_VALUE_LENGTH__:
				return 'length';	
			case __CA_ATTRIBUTE_VALUE_URL__:
				return 'url';
			case __CA_ATTRIBUTE_VALUE_OBJECTREPRESENTATIONS__:		
			case __CA_ATTRIBUTE_VALUE_ENTITIES__:
			case __CA_ATTRIBUTE_VALUE_PLACES__:	
			case __CA_ATTRIBUTE_VALUE_OCCURRENCES__:	
			case __CA_ATTRIBUTE_VALUE_COLLECTIONS__:				
			case __CA_ATTRIBUTE_VALUE_STORAGELOCATIONS__:			
			case __CA_ATTRIBUTE_VALUE_LOANS__:		
			case __CA_ATTRIBUTE_VALUE_MOVEMENTS__:		
			case __CA_ATTRIBUTE_VALUE_OBJECTS__:	
			case __CA_ATTRIBUTE_VALUE_OBJECTLOTS__:	
				return 'reference';		
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _genAttributeDictionary(array $md) : array {
		$dictionary = [];
		foreach($md as $f => $d) {
			$is_authority = \ca_metadata_elements::isAuthorityDatatype($f);
			
			$dictionary[$f] = [
				'name' => $d['display_label'],
				'description' => \ca_metadata_elements::getElementDescription($f),
				'type' => $this->_attributeTypeToDictionaryType($d['datatype']),
				'canRepeat' => true
			];
			if ($d['datatype'] == __CA_ATTRIBUTE_VALUE_LIST__) {
				$dictionary[$f]['list_id'] = $d['list_id'];
				$dictionary[$f]['list_code'] = caGetListCode($d['list_id']);
			}
			
			if($is_authority && ($t = \AuthorityAttributeValue::elementTypeToInstance($d['datatype']))) {
				$dictionary[$f]['reference_to'] = $t->tableName();
			}
			
			if(
				($d['datatype'] == __CA_ATTRIBUTE_VALUE_CONTAINER__)
				&&
				(is_array($sub_elements = \ca_metadata_elements::getElementsForSet($d['element_id'])) && sizeof($sub_elements))
			) {
				$dictionary[$f]['subElements'] = [];
				foreach($sub_elements as $se) {
					if($se['datatype'] == __CA_ATTRIBUTE_VALUE_CONTAINER__) { continue; }
					
					$dictionary[$f]['subElements'][$se['element_code']] = [
						'name' => $se['display_label'],
						'description' => \ca_metadata_elements::getElementDescription($se['element_code'] ),
						'type' => $this->_attributeTypeToDictionaryType($se['datatype'])
					];
					if ($se['datatype'] == __CA_ATTRIBUTE_VALUE_LIST__) {
						$dictionary[$f]['subElements'][$se['element_code']]['list_id'] = $se['list_id'];
						$dictionary[$f]['subElements'][$se['element_code']]['list_code'] = caGetListCode($se['list_id']);
					}
					
					if(\ca_metadata_elements::isAuthorityDatatype($se['element_code']) && ($t = \AuthorityAttributeValue::elementTypeToInstance($se['datatype']))) {
						$dictionary[$f]['subElements'][$se['element_code']]['reference_to'] = $t->tableName();
					}
				}
			}
		}
		return $dictionary;
	}
	# -------------------------------------------------------
}

