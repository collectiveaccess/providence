<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseLocationModel.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
 
  /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/ca/RepresentableBaseModel.php');
	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 
	class BaseObjectLocationModel extends RepresentableBaseModel {
		# -------------------------------------------------------
		/**
		 * Override BundlableLabelableBaseModelWithAttributes::changeType() to update
		 * current location "subclass" (ie. type) value when type change is used.
		 * This should be invoked by any model that can be used to indicate object
		 * storage location. This includes, for now at least, ca_loans, ca_movements, 
		 * ca_occurrences and ca_objects_x_storage_locations.
		 *
		 * @param mixed $pm_type The type_id or code to change the current type to
		 * @return bool True if change succeeded, false if error
		 */
		public function changeType($pm_type) {
			if (!$this->getPrimaryKey()) { return false; }					// row must be loaded
	
			if (!($vb_already_in_transaction = $this->inTransaction())) {
				$this->setTransaction($o_t = new Transaction($this->getDb()));
			}
			if ($vn_rc = parent::changeType($pm_type)) {
				$o_db = $this->getDb();
				$o_db->query("
					UPDATE ca_objects SET current_loc_subclass = ? 
					WHERE 
						current_loc_class = ? AND current_loc_id = ?
				", array($this->get('type_id'), $this->tableNum(), $this->getPrimaryKey()));
		
				if ($o_db->numErrors()) {
					$this->errors = $o_db->errors;
					if (!$vb_already_in_transaction) { $o_t->rollback(); }
					return false;
				}
			}
			
			if (!$vb_already_in_transaction) { $o_t->commit(); }
	
			return $vn_rc;
		}
		
		# ------------------------------------------------------
		/**
		 *
		 */
		public function addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $ps_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
			if ($vn_rc = parent::addRelationship($pm_rel_table_name_or_num, $pn_rel_id, $pm_type_id, $ps_effective_date, $ps_source_info, $ps_direction, $pn_rank, $pa_options)) {
				$this->_setCurrent($pm_rel_table_name_or_num, $pn_rel_id);
			}
			return $vn_rc;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id=null, $ps_effective_date=null, $pa_source_info=null, $ps_direction=null, $pn_rank=null, $pa_options=null) {
			if ($vn_rc = parent::editRelationship($pm_rel_table_name_or_num, $pn_relation_id, $pn_rel_id, $pm_type_id, $ps_effective_date, $pa_source_info, $ps_direction, $pn_rank, $pa_options)) {
				$this->_setCurrent($pm_rel_table_name_or_num, $pn_rel_id);
			}
			return $vn_rc;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function removeRelationship($pm_rel_table_name_or_num, $pn_relation_id) {
			$va_path = array_keys($this->getAppDatamodel()->getPath($this->tableName(), $pm_rel_table_name_or_num));
			
			$vn_rel_id = $t_rel_item = null;
			if ((sizeof($va_path) == 3) && ($t_rel = $this->getAppDatamodel()->getInstance($va_path[1]))) {
				$t_rel->setTransaction($this->getTransaction());
				$t_rel_item = $this->getAppDatamodel()->getInstance($va_path[2], true);
				if ($t_rel->load($pn_relation_id)) {
					$vn_rel_id = $t_rel->get($t_rel_item->primaryKey());
				}
			}
			if ($vn_rc = parent::removeRelationship($pm_rel_table_name_or_num, $pn_relation_id)) {
				if ($vn_rel_id && $t_rel_item) {
					$this->_setCurrent($t_rel_item->tableName(), $vn_rel_id);
				}
			}
			
			return $vn_rc;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _setCurrent($pm_rel_table_name_or_num, $pn_rel_id) {
			
			if(!($vs_rel_table = $this->getAppDatamodel()->getTableName($pm_rel_table_name_or_num))) { return null; }
			$vn_now = TimeExpressionParser::now();
			
			switch ($vs_table = $this->tableName()) {
				case 'ca_movements':
					//
					// Calcuate current flag for movement-object relationships
					//
					//		Each object can have only one "current" movement at any time
					//
					if (
						($vs_date_element = $this->getAppConfig()->get('movement_storage_location_date_element'))
						&&
						($vs_rel_table == 'ca_objects')
					) {
						// get all other movements for this object
			
						if (ca_objects::exists($pn_rel_id, ['idOnly' => true, 'transaction' => $this->getTransaction()])) {
							if ($qr_movements_for_object = ca_movements_x_objects::find(array('object_id' => $pn_rel_id), array('returnAs' => 'SearchResult', 'transaction' => $this->getTransaction()))) {
								$va_list = $va_rel_ids = array();
								while($qr_movements_for_object->nextHit()) {
									$vn_date = $qr_movements_for_object->get("ca_movements.{$vs_date_element}", array('sortable' => true));
									if ($vn_date > $vn_now) { continue; }  // omit future moves
									
									$va_list[$vn_date] = $vn_rel_id = $qr_movements_for_object->get('ca_movements_x_objects.relation_id');
									$va_rel_ids[] = $vn_rel_id;
								}
								ksort($va_list, SORT_NUMERIC);
								$vn_current = end((array_values($va_list)));
					
								if(sizeof($va_list)) { 
									$this->getDb()->query("UPDATE ca_movements_x_objects SET source_info = '' WHERE relation_id IN (?)", array($va_rel_ids));
								}
								if ($vn_current) {
									$this->getDb()->query("UPDATE ca_movements_x_objects SET source_info = 'current' WHERE relation_id = ?", array($vn_current));
								}
							}
						}
					}
					break;
				case 'ca_objects':
					//
					// Calcuate current flag for object-storage_location relationships
					//
					//		Each object can have only one "current" location at any time
					//
					if (
						($vs_rel_table == 'ca_storage_locations')
					) {
						// get all other locations for this object
						if (ca_storage_locations::exists($pn_rel_id, ['idOnly' => true, 'transaction' => $this->getTransaction()])) {
							if ($qr_locations_for_object = ca_objects_x_storage_locations::find(array('object_id' => $this->getPrimaryKey()), array('returnAs' => 'SearchResult', 'transaction' => $this->getTransaction()))) {
								$va_list = $va_rel_ids = array();
								while($qr_locations_for_object->nextHit()) {
									$vn_date = $qr_locations_for_object->get("ca_objects_x_storage_locations.sdatetime", array('sortable' => true));
									if ($vn_date > $vn_now) { continue; }  // omit future moves
									
									$va_list[$vn_date] = $vn_rel_id = $qr_locations_for_object->get('ca_objects_x_storage_locations.relation_id');
									$va_rel_ids[] = $vn_rel_id;
								}
								ksort($va_list, SORT_NUMERIC);
								$vn_current = end((array_values($va_list)));
								
								if(sizeof($va_list)) { 
									$this->getDb()->query("UPDATE ca_objects_x_storage_locations SET source_info = '' WHERE relation_id IN (?)", array($va_rel_ids));
								}
								if ($vn_current) {
									$this->getDb()->query("UPDATE ca_objects_x_storage_locations SET source_info = 'current' WHERE relation_id = ?", array($vn_current));
								}
							}
						}
					}
					break;
				case 'ca_storage_locations':
					//
					// Calcuate current flag for object-storage_location relationships
					//
					//		Each object can have only one "current" location at any time
					//
					if (
						($vs_rel_table == 'ca_objects')
					) {
						// get all other locations for this object
						if (ca_objects::exists($pn_rel_id, ['idOnly' => true, 'transaction' => $this->getTransaction()])) {
							if ($qr_locations_for_object = ca_objects_x_storage_locations::find(array('object_id' => $pn_rel_id), array('returnAs' => 'SearchResult', 'transaction' => $this->getTransaction()))) {
								$va_list = $va_rel_ids = array();
								while($qr_locations_for_object->nextHit()) {
									$vn_date = $qr_locations_for_object->get("ca_objects_x_storage_locations.sdatetime", array('sortable' => true));
									if ($vn_date > $vn_now) { continue; }  // omit future moves
									
									$va_list[$vn_date] = $vn_rel_id = $qr_locations_for_object->get('ca_objects_x_storage_locations.relation_id');
									$va_rel_ids[] = $vn_rel_id;
								}
								ksort($va_list, SORT_NUMERIC);
								$vn_current = end((array_values($va_list)));
								
								if(sizeof($va_list)) { 
									$this->getDb()->query("UPDATE ca_objects_x_storage_locations SET source_info = '' WHERE relation_id IN (?)", array($va_rel_ids));
								}
								if ($vn_current) {
									$this->getDb()->query("UPDATE ca_objects_x_storage_locations SET source_info = 'current' WHERE relation_id = ?", array($vn_current));
								}
							}
						}
					}
					break;
			}
			return true;
		}
		# -------------------------------------------------------
	}