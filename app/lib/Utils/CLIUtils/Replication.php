<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Replication.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

trait CLIUtilsReplication { 
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_data($po_opts=null) {
		require_once(__CA_LIB_DIR__.'/Sync/Replicator.php');

		$o_replicator = new Replicator();
		$o_replicator->replicate();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataParamList() {
		return array();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataShortHelp() {
		return _t("Replicate data from one CollectiveAccess system to another.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function replicate_dataHelp() {
		return _t("Replicates data in one CollectiveAccess instance based upon data in another instance, subject to configuration in replication.conf.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_records_for_consortium_source($po_opts=null) {
		global $g_system, $g_systems;
		
		if (!($source = $po_opts->getOption('source'))) {
			CLIUtils::addError(_t("You must specify a source"));
			return false;
		}
		
		if(!is_array($g_systems) || !isset($g_systems[$source])) {
			print "Invalid system\n";
			exit;
		}
		
		$system_info = $g_systems[$source];
		
		// create entity record
		$o_replication_config = Configuration::load(__CA_CONF_DIR__."/replication.conf");
		if(!($entity_type = $o_replication_config->get('consortium_member_entity_type')) || !($entity_type_id = caGetListItemID('entity_types', $entity_type))) {
			print "Invalid entity type {$entity_type}\n";
			exit;
		}
		
		if(ca_entities::findAsInstance(['idno' => $system_info['app_name'], 'type_id' => $entity_type])) {
			print "Found existing entity\n";
		} else {
			$t_entity = new ca_entities();
			$t_entity->set([
				'idno' => $system_info['app_name'],
				'type_id' => $entity_type,
				'access' => 1
			]);
			if(!$t_entity->insert()) {
				print "Could not create new consortium member entity with type {$entity_type}: ".join("; ", $t_entity->getErrors())."\n";
				exit;
			}
			if(!$t_entity->addLabel(['displayname' => $system_info['app_name_display']], ca_locales::getDefaultCataloguingLocaleID(), 0, true)) {
				print "Could not create label for new consortium member entity with type {$entity_type}: ".join("; ", $t_entity->getErrors())."\n";
				exit;
			}
		}
		
		// create object_source
		$t_list = new ca_lists(['list_code' => 'object_sources']);
		if(!$t_list->isLoaded()) { 
			print "No list for object sources is defined\n";
		} else {
			if(ca_list_items::findAsInstance(['idno' => $system_info['app_name'], 'list_id' => $t_list->getPrimaryKey()])) {
				print "Found existing object source\n";
			} else {
				$t_item = new ca_list_items();
				$t_item->set([
					'idno' => $system_info['app_name'],
					'type_id' => 'concept',
					'list_id' => $t_list->getPrimaryKey(),
					'access' => 1
				]);
				if(!$t_item->insert()) {
					print "Could not create object source entry : ".join("; ", $t_list->getErrors())."\n";
				}
				if(!$t_item->addLabel(['name_singular' => $system_info['app_name_display'], 'name_plural' => $system_info['app_name_display']], ca_locales::getDefaultCataloguingLocaleID(), 0, true)) {
					print "Could not create label for object source entry : ".join("; ", $t_list->getErrors())."\n";
				}
			}
		}
		
		// create collection_source
		$t_list = new ca_lists(['list_code' => 'collection_sources']);
		if(!$t_list->isLoaded()) { 
			print "No list for collection sources is defined\n";
		} else {
			if(ca_list_items::findAsInstance(['idno' => $system_info['app_name'], 'list_id' => $t_list->getPrimaryKey()])) {
				print "Found existing collection source\n";
			} else {
				$t_item = new ca_list_items();
				$t_item->set([
					'idno' => $system_info['app_name'],
					'type_id' => 'concept',
					'list_id' => $t_list->getPrimaryKey(),
					'access' => 1
				]);
				if(!$t_item->insert()) {
					print "Could not create collection source entry : ".join("; ", $t_list->getErrors())."\n";
				}
				if(!$t_item->addLabel(['name_singular' => $system_info['app_name_display'], 'name_plural' => $system_info['app_name_display']], ca_locales::getDefaultCataloguingLocaleID(), 0, true)) {
					print "Could not create label for collection source entry : ".join("; ", $t_list->getErrors())."\n";
				}
			}
		}
		
		// 
		print "Set up entries for {$system_info['app_name']}\n";
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_records_for_consortium_sourceParamList() {
		return [
			"source|s=s" => _t('Source system')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_records_for_consortium_sourceUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_records_for_consortium_sourceShortHelp() {
		return _t("Force replication of last change to specific record");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_records_for_consortium_sourceHelp() {
		return _t("To come.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function align_guids_for_consortium_source($po_opts=null) {
		global $g_system, $g_systems;
		
		// TODO: rewrite to use services rather than cross-database queries
		
		$hostname = $po_opts->getOption('hostname');
		if (!($source = $po_opts->getOption('source'))) {
			CLIUtils::addError(_t("You must specify a source"));
			return false;
		}
		
		if(!is_array($g_systems) || !isset($g_systems[$source])) {
			print "Invalid system\n";
			exit;
		}
		
		$reference_info = $g_systems[$hostname];
		$target_info = $g_systems[$source];
		
		$db = new Db();
		// Align base tables
		$tables = ['ca_lists', 'ca_list_items', 'ca_relationship_types'];     // 'ca_entities', 
		  
		$reference_sys = $reference_info['app_name'];
		$target_sys = $target_info['app_name'];
		
		if(!$reference_sys) {
			print "Could not find reference system\n";
			exit;
		}
		if(!$target_sys) {
			print "Could not find target system\n";
			exit;
		}
		
		print "Rewriting guids for {$target_sys} to align with {$reference_sys}\n";
	
		print "--- Processing {$target_sys} ---\n\n";
		
		foreach($tables as $table) {
			$t = Datamodel::getInstanceByTableName($table, true);
			$tn= $t->tableNum();
			$pk = $t->primaryKey();
			if (!($idno_fld = $t->getProperty('ID_NUMBERING_ID_FIELD'))) { continue; }
			$ids = $table::find('*', ['returnAs' => 'searchResult']);
			
			$label_table = $t->getLabelTableName();
			$label_table_num = Datamodel::getTableNum($label_table);
			$label_display_field = $t->getLabelDisplayField();
			
			print "got ".$ids->numHits()."\n";
			
			while($ids->nextHit()) {
				$id = $ids->get("{$table}.{$pk}");
				$list_id = $ids->get("{$table}.list_id");
		
				// convert list_id
				$target_list_id = null;
				 if(($xxx = $db->query("SELECT list_id, list_code FROM {$target_sys}.ca_lists WHERE list_code = ?", [$list_code = caGetListCode($list_id)])) && $xxx->nextRow()) {
					$target_list_id = $xxx->get('list_id');
				 } else {
					print("Could not get target list_id for $list_code [$list_id]\n");
					continue;
				 }
	   
		
				// rewrite primary guid
				$guid = $table::getGUIDByPrimaryKey($id); 
				if (!($idno = $table::getIdnoForID($id))) { print "NO IDNO FOR $id\n"; continue; }
		
				$r = $db->query("SELECT {$pk} FROM {$target_sys}.{$table} WHERE {$idno_fld} = ?".(($table == 'ca_list_items') ? " AND list_id = {$target_list_id}" : ""), [$idno]);
		
				if($r->nextRow()) {
					print "[$table::".$r->get($pk)."] $idno => $guid\n";
					try {
						$db->query("UPDATE {$target_sys}.ca_guids SET guid = ? WHERE table_num = ? AND row_id = ?", [$guid, $tn, (int)$r->get($pk)]);
					} catch (Exception $e) {
						print "[ERROR] ".$e->getMessage()."\n";
					}
				} elseif($table == 'ca_list_items') {
					$t_list_item = ca_list_items::findAsInstance(['item_id' => $id]);
					$r = $db->query("SELECT {$target_sys}.{$table}.{$pk} FROM {$target_sys}.{$table} INNER JOIN {$target_sys}.ca_list_item_labels AS l ON l.item_id = {$target_sys}.{$table}.item_id WHERE l.name_singular = ? AND list_id = {$target_list_id}", [$t_list_item->get('ca_list_items.preferred_labels.name_singular')]);
			
					if($r->nextRow()) {
						$idno = $t_list_item->get('ca_list_items.idno');
						print "[BY LABEL $table::".$r->get($pk)."] $idno => $guid\n";
						try {
							$db->query("UPDATE {$target_sys}.ca_guids SET guid = ? WHERE table_num = ? AND row_id = ?", [$guid, $tn, (int)$r->get($pk)]);
						} catch (Exception $e) {
							print "[ERROR] ".$e->getMessage()."\n";
						}
					} else {
						$label = $t_list_item->get('ca_list_items.preferred_labels.name_singular');
						$tmp = preg_split("/[ ]*[,]+[ ]*/", $label);
						$label_inverted = trim(array_pop($tmp).(sizeof($tmp) ? ' '.join(' ', $tmp) : ''));
					//	print "TRY INVERSION [$label] => [$label_inverted]\n";
						$r = $db->query($z="SELECT {$target_sys}.{$table}.{$pk} FROM {$target_sys}.{$table} INNER JOIN {$target_sys}.ca_list_item_labels AS l ON l.item_id = {$target_sys}.{$table}.item_id WHERE l.name_singular = ? AND list_id = {$target_list_id}", [$label_inverted]);
				
						if($r->nextRow()) {
							$idno = $t_list_item->get('ca_list_items.idno');
							print "[BY LABEL INVERTED $table::".$r->get($pk)."] $idno => $guid\n";
							try {
								$db->query("UPDATE {$target_sys}.ca_guids SET guid = ? WHERE table_num = ? AND row_id = ?", [$guid, $tn, (int)$r->get($pk)]);
							} catch (Exception $e) {
								print "[ERROR] ".$e->getMessage()."\n";
							}
						} else {
							print "----\n";
						}
					}
				} else {
					print "[NO MATCH FOR $idno\n";
				}
		
				// rewrite label guids
				$labels = $t->getLabels(null, __CA_LABEL_TYPE_ANY__, true, ['row_id' => $id]);
				foreach($labels as $x => $labels_by_locale) {
					foreach(array_reverse($labels_by_locale) as $locale_id => $labels_for_locale) {
						foreach($labels_for_locale as $label) {
							$r = $db->query("SELECT {$label_table}.label_id FROM {$target_sys}.{$label_table} INNER JOIN {$table} ON {$table}.{$pk} = {$label_table}.{$pk} WHERE {$table}.deleted = 0 AND {$label_display_field} = ? AND {$label_table}.locale_id = ?", [$label[$label_display_field], $locale_id]);
							if($r->nextRow()) {
								$guid = $label_table::getGUIDByPrimaryKey($label['label_id']); 
								print "[".$r->get('label_id')."] => {$guid}\n";

								try {
									$db->query("UPDATE {$target_sys}.ca_guids SET guid = ? WHERE table_num = ? AND row_id = ?", [$guid, $label_table_num, (int)$r->get('label_id')]);
								} catch (Exception $e) {
									print "[ERROR] ".$e->getMessage()."\n";
								}
							}
						}
					}
				}
			}
		}
		
        // Rewrite locales
        $qr = $db->query("SELECT * FROM {$target_sys}.ca_locales");
        while($qr->nextRow()) {
            $row = $qr->getRow();
            print_r($row);
            
            $qx = $db->query("SELECT * FROM ca_locales WHERE language = ? AND country = ?", [$row['language'], $row['country']]);
            if($qx->nextRow()) {
                $locale_id = $qx->get('locale_id');
                $qg = $db->query("SELECT * FROM ca_guids WHERE table_num = 37 and row_id = ?", [$locale_id]);
                if($qg->nextRow()) {
                    $db->query("UPDATE {$target_sys}.ca_guids SET guid = ? WHERE table_num = 37 AND row_id = ?", [$qg->get('guid'), $row['locale_id']]);
                }
            }
            
        }
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function align_guids_for_consortium_sourceParamList() {
		return [
			"source|s=s" => _t('Source system'),
			"target|t=s" => _t('Target system'),s
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function align_guids_for_consortium_sourceUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function align_guids_for_consortium_sourceShortHelp() {
		return _t("Force replication of last change to specific record");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function align_guids_for_consortium_sourceHelp() {
		return _t("To come.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_change($po_opts=null) {
		require_once(__CA_LIB_DIR__.'/Sync/Replicator.php');

		if (!($source = $po_opts->getOption('source'))) {
			CLIUtils::addError(_t("You must specify a source"));
			return false;
		}
		$target = $po_opts->getOption('target');
		
		$guid = $po_opts->getOption('guid');	// TODO
		
		$table = $po_opts->getOption('table');
		$search = $po_opts->getOption('search');
		
		$guids = [];
		if($table && $search) {
			if(!($o_search = caGetSearchInstance($table))) {
				CLIUtils::addError(_t("Could not set up search for %1", $table));
				return false;
			}
			if($qr = $o_search->search($search)) {
				while($qr->nextHit()) {
					$guids[] = $qr->get("{$table}._guid");
				}
			} else {
				CLIUtils::addError(_t("Search failed"));
				return false;
			}
		} elseif($guid) {
			$guids = [$guid];
		} else {
			CLIUtils::addError(_t("You must specify a search or guid to replicate"));
			return false;
		}

		$o_replicator = new Replicator();
		
		foreach($guids as $guid) {
			print "Replicating {$guid}\n";
			$o_replicator->forceSyncOfLatest($source, $guid, ['targets' => [$target]]);
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeParamList() {
		return [
			"source|s=s" => _t('Source system'),
			"target|t=s" => _t('Target system'),
			"guid|g=s" => _t('GUID of record to replicate'),
			"table|b=s" => _t('Table of records to replicate'),
			"search|f=s" => _t('Search for records to replicate')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeUtilityClass() {
		return _t('Replication');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeShortHelp() {
		return _t("Force replication of last change to specific record");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function force_update_with_latest_changeHelp() {
		return _t("To come.");
	}
	# -------------------------------------------------------
}
