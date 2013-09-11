<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseRepresentationRelationship.php :
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/core/BaseRelationshipModel.php');
 require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
	class BaseRepresentationRelationship extends BaseRelationshipModel {
		# ------------------------------------------------------
		private function _getTarget() {
			$vs_target_table = ($this->getLeftTableName() == 'ca_object_representations') ? $this->getRightTableName() : $this->getLeftTableName();
			$vs_target_key = ($this->getLeftTableName() == 'ca_object_representations') ? $this->getRightTableFieldName() : $this->getLeftTableFieldName();
			
			return array($vs_target_table, $vs_target_key);
		}
		# ------------------------------------------------------
		public function insert($pa_options=null) {
			$o_trans = new Transaction();
			$o_db = $o_trans->getDb();
			$this->setTransaction($o_trans);
		
			list($vs_target_table, $vs_target_key) = $this->_getTarget();
			$vs_rel_table = $this->tableName();
			
			$t_target = $this->getAppDatamodel()->getInstanceByTableName($vs_target_table);
			
			$vn_target_id = $this->get($vs_target_key);
			if (!$t_target->load($vn_target_id)) { 
				// invalid object
				$this->postError(720, _t("Related %1 does not exist", $t_target->getProperty('NAME_SINGULAR')), "BaseRepresentationRelationship->insert()");
				return false;
			}
			if (!$this->get('is_primary')) {
				// force is_primary to be set if no other represention is so marked 
		
				// is there another rep for this object marked is_primary?
				$qr_res = $o_db->query("
					SELECT oxor.relation_id
					FROM {$vs_rel_table} oxor
					INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = oxor.representation_id
					WHERE
						oxor.{$vs_target_key} = ? AND oxor.is_primary = 1 AND o_r.deleted = 0
				", (int)$vn_target_id);
				if(!$qr_res->nextRow()) {
					// nope - force this one to be primary
					$this->set('is_primary', 1);
				}
			
				$vb_rc = parent::insert($pa_options);
				$o_trans->commitTransaction();
				return $vb_rc;
			} else {
				// unset other reps is_primary field
				//$o_db->beginTransaction();
			
				$o_db->query("
					UPDATE {$vs_rel_table}
					SET is_primary = 0
					WHERE
						{$vs_target_key} = ?
				", (int)$vn_target_id);
			
				if (!$vb_rc = parent::insert($pa_options)) {
					$o_trans->rollbackTransaction();
				} else {
					$o_trans->commitTransaction();
				}
			
				return $vb_rc;
			}
		}
		# ------------------------------------------------------
		public function update($pa_options=null) {
			$o_trans = new Transaction();
			$o_db = $o_trans->getDb();
			$this->setTransaction($o_trans);
		
			list($vs_target_table, $vs_target_key) = $this->_getTarget();
			
			$vs_rel_table = $this->tableName();
			$t_target = $this->getAppDatamodel()->getInstanceByTableName($vs_target_table);
			
			$vn_target_id = $this->get($vs_target_key);
			if (!$t_target->load($vn_target_id)) { 
				// invalid target
				$this->postError(720, _t("Related %1 does not exist", $t_target->getProperty('NAME_SINGULAR')), "BaseRepresentationRelationship->update()");
				return false;
			}
		
			if ($this->changed('is_primary')) {
				if (!$this->get('is_primary')) {
				
					// force is_primary to be set if no other represention is so marked 
					// is there another rep for this object marked is_primary?
					$qr_res = $o_db->query("
						SELECT oxor.relation_id
						FROM {$vs_rel_table} oxor
						INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = oxor.representation_id
						WHERE
							oxor.{$vs_target_key} = ? AND oxor.is_primary = 1 AND o_r.deleted = 0 AND oxor.relation_id <> ?
					", (int)$vn_target_id, (int)$this->getPrimaryKey());
					if(!$qr_res->nextRow()) {
						// nope - force one to be primary
						//$this->set('is_primary', 1);
						$qr_res = $o_db->query("
							SELECT oxor.relation_id
							FROM {$vs_rel_table} oxor
							INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = oxor.representation_id
							WHERE
								oxor.{$vs_target_key} = ? AND oxor.is_primary = 0 AND o_r.deleted = 0 AND oxor.relation_id <> ?
							ORDER BY oxor.rank, oxor.relation_id
						", (int)$vn_target_id, (int)$this->getPrimaryKey());
						if ($qr_res->nextRow()) {
							$o_db->query("
								UPDATE {$vs_rel_table}
								SET is_primary = 1
								WHERE
									relation_id = ?
							", (int)$qr_res->get('relation_id'));
							if (!($vb_rc = parent::update($pa_options))) {
								$o_trans->rollbackTransaction();
							} else {
								$o_trans->commitTransaction();
							}
						}
					}
				
					return parent::update($pa_options);
				} else {
					// unset other reps is_primary field
					$o_db->query("
						UPDATE {$vs_rel_table}
						SET is_primary = 0
						WHERE
							{$vs_target_key} = ?
					", (int)$vn_target_id);
					if (!($vb_rc = parent::update($pa_options))) {
						$o_trans->rollbackTransaction();
					} else {
						$o_trans->commitTransaction();
					}
					return $vb_rc;
				}
			} else {
				$vb_rc = parent::update($pa_options);
				$o_trans->commitTransaction();
				return $vb_rc;
			}
		}
		# ------------------------------------------------------
		public function delete($pb_delete_related=false, $pa_options=null, $pa_fields=null, $pa_table_list=null) {
		
			list($vs_target_table, $vs_target_key) = $this->_getTarget();
			$vs_rel_table = $this->tableName();
			
			$t_target = $this->getAppDatamodel()->getInstanceByTableName($vs_target_table);
			
			$vn_target_id = $this->get($vs_target_key);
			if (!$t_target->load($vn_target_id)) { 
				// invalid object
				$this->postError(720, _t("Related %1 does not exist", $t_target->getProperty('NAME_SINGULAR')), "BaseRepresentationRelationship->delete()");
				return false;
			}
		
			$o_trans = new Transaction();
			$this->setTransaction($o_trans);
			if($vb_rc = parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list)) {
		
				if ($this->get('is_primary')) {
					$o_db = $this->getDb();
			
					// make some other row primary
					$qr_res = $o_db->query("
						SELECT oxor.relation_id
						FROM {$vs_rel_table} oxor
						INNER JOIN ca_object_representations AS o_r ON o_r.representation_id = oxor.representation_id
						WHERE
							oxor.{$vs_target_key} = ? AND oxor.is_primary = 0 AND o_r.deleted = 0 AND oxor.relation_id <> ?
						ORDER BY
							oxor.rank, oxor.relation_id
					", (int)$vn_target_id, (int)$this->getPrimaryKey());
					if($qr_res->nextRow()) {
						// nope - force this one to be primary
						$t_rep_link = $this->getAppDatamodel()->getInstanceByTableName($vs_rel_table);
						$t_rep_link->setTransaction($o_trans);
						if ($t_rep_link->load($qr_res->get('relation_id'))) {
							$t_rep_link->setMode(ACCESS_WRITE);
							$t_rep_link->set('is_primary', 1);
							$t_rep_link->update();
					
							if ($t_rep_link->numErrors()) {
								$this->postError(2700, _t('Could not update primary flag for representation: %1', join('; ', $t_rep_link->getErrors())), 'BaseRepresentationRelationship->delete()');
								$o_trans->rollbackTransaction();
								return false;
							}
						} else {
							$this->postError(2700, _t('Could not load %1-representation link', $t_target->getProperty('NAME_SINGULAR')), 'BaseRepresentationRelationship->delete()');
							$o_trans->rollbackTransaction();
							return false;
						}				
					}
				} 
				$o_trans->commitTransaction();
			} else {
				$o_trans->rollbackTransaction();
			}
		
			return $vb_rc;
		}
		# ------------------------------------------------------
	}
?>