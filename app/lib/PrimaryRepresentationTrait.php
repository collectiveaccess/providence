<?php
/** ---------------------------------------------------------------------
 * app/lib/PrimaryRepresentationTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021-2024 Whirl-i-Gig
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
 * Methods for relationship models that include an is_primary flag
 */
trait PrimaryRepresentationTrait {
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($options=null) {
		$dont_check_primary_value = caGetOption('dontCheckPrimaryValue', $options, false);
		
		if($rc = parent::insert($options)) {
			if(!$dont_check_primary_value && ($this->setPrimary(['force' => true]) === false)) {
				$this->postError(2700, _t('Could not set primary representation: %1', join('; ', $this->getErrors())), 'PrimaryRepresentationTrait::insert');
			}
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($options=null) {
		$dont_check_primary_value = caGetOption('dontCheckPrimaryValue', $options, false);
		
		if($rc = parent::update($options)) {
			if(!$dont_check_primary_value && ($this->setPrimary() === false)) {
				$this->postError(2700, _t('Could not set primary representation: %1', join('; ', $this->getErrors())), 'PrimaryRepresentationTrait::update');
			}
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 * @param bool $delete_related
	 * @param array $options Options include:
	 *		dontCheckPrimaryValue = if set the is_primary state of other related representations is not considered during the delete. [Default is false]
	 * @array $fields 
	 * @array $table_list
	 *
	 * @return bool
	 */
	public function delete($delete_related=false, $options=null, $fields=null, $table_list=null) {
		$dont_check_primary_value = caGetOption('dontCheckPrimaryValue', $options, false);
		if($rc = parent::delete($delete_related, $options, $fields, $table_list)) {
			if(!$dont_check_primary_value && ($this->setPrimary(['force' => true]) === false)) {
				$this->postError(2700, _t('Could not set primary representation: %1', join('; ', $this->getErrors())), 'PrimaryRepresentationTrait::delete');
			}
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param array $options Options include:
	 *		force = Check and set primary even if is_primary field appears to be unchanged. [Default is false] 
	 */
	public function setPrimary(?array $options=null) {
		$force = caGetOption('force', $options, false);
		if(!$this->didChange('is_primary') && !$force) { return true; }
		$table = $this->tableName();
		$rel_table = ($this->RELATIONSHIP_LEFT_TABLENAME !== 'ca_object_representations') ? $this->RELATIONSHIP_LEFT_TABLENAME : $this->RELATIONSHIP_RIGHT_TABLENAME;
		$rel_key = ($this->RELATIONSHIP_LEFT_TABLENAME !== 'ca_object_representations') ? $this->RELATIONSHIP_LEFT_FIELDNAME : $this->RELATIONSHIP_RIGHT_FIELDNAME;
		if(!($related_id = $this->get($rel_key)) && !($related_id = $this->getOriginalValue($rel_key))) {
			return null;
		}
		$o_db = $this->getDb();
		
		$qr = $o_db->query("
			SELECT r.relation_id, r.is_primary FROM {$table} r 
			INNER JOIN {$rel_table} AS rel ON r.{$rel_key} = rel.{$rel_key}
			INNER JOIN ca_object_representations AS rep ON r.representation_id = rep.representation_id
			WHERE r.{$rel_key} = ? AND rel.deleted = 0 AND rep.deleted = 0 ORDER BY r.is_primary DESC", [$related_id]);
		$seen_primary = false;
		$subject_is_primary = ((int)$this->get('is_primary') === 1);
		$relation_id = $this->getPrimaryKey();
		
		if($qr->numRows() == 0) { return true; }
		while($qr->nextRow()) {
			$row_rel_id = $qr->get('relation_id');
			if($subject_is_primary && ($relation_id == $row_rel_id)) { 
				$seen_primary = true;
				continue;
			}
			if (!$subject_is_primary && !$seen_primary && ((int)$qr->get('is_primary') === 1)) {
				$seen_primary = true;
				continue;
			}
			if (!$seen_primary && ((int)$qr->get('is_primary') === 0)) {
				// No primary - set one
				if ($t_rel = $table::findAsInstance($row_rel_id, ['transaction' => $this->getTransaction()])) {
					$t_rel->setTransaction($this->getTransaction());
					$t_rel->set('is_primary', 1);
					$rc = $t_rel->update(['dontCheckPrimaryValue' => true]);
					
					if($t_rel->numErrors() > 0) {
						$this->errors = array_merge($this->errors, $t_rel->errors);
					} else {
						$seen_primary = true;
					}
					continue;
				}
			}
			if (
				($seen_primary && ((int)$qr->get('is_primary') === 1))
				||
				(!$seen_primary && $subject_is_primary && ((int)$qr->get('is_primary') === 1))
			) {
				// Already set/found primary so unset this one
				$t_rel = $table::findAsInstance($qr->get('relation_id'));
				$t_rel->setTransaction($this->getTransaction());
				$t_rel->set('is_primary', 0);
				$rc = $t_rel->update(['dontCheckPrimaryValue' => true]);
				
				if($t_rel->numErrors() > 0) {
					$this->errors = array_merge($this->errors, $t_rel->errors);
				}
				continue;
			}
		}
		return $seen_primary;
	}
	# -------------------------------------
}
