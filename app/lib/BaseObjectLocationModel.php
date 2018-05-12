<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseLocationModel.php : 
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
  
 	require_once(__CA_LIB_DIR__.'/RepresentableBaseModel.php');
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
		# -------------------------------------------------------
	}