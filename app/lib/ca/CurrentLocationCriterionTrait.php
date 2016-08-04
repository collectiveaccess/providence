<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/CurrentLocationCriterionTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 
	trait CurrentLocationCriterionTrait {
		# ------------------------------------------------------
		/**
		 * Update location for dependent objects
		 */
		public function update($pa_options=null) {
			// has there been a change that might affect current location of dependent objects?
			$vb_reload_current_locations = false;
			if (is_array($va_map = $this->getAppConfig()->getAssoc('current_location_criteria')) && is_array($va_criteria = $va_map[$this->tableName()])) {
				
				switch($this->tableName()) {
					case 'ca_objects_x_storage_locations':
						foreach ($va_criteria as $vs_type => $va_options) {
							if ($this->changed('effective_date')) {
								$vb_reload_current_locations = true;
								break;
							}
						}
						break;
					default:
						foreach ($va_criteria as $vs_type => $va_options) {
							if ($this->changed('_ca_attribute_'.ca_metadata_elements::getElementID($va_options['date']))) {
								$vb_reload_current_locations = true;
								break;
							}
						}
						break;
				}
			}
			$vn_rc = parent::update($pa_options);
							
			if ($vb_reload_current_locations) {
				// any related objects?
				if ($va_object_ids = $this->getRelatedItems('ca_objects', ['returnAs' => 'ids'])) {
					foreach($va_object_ids as $vn_object_id) {
						$t_object = new ca_objects($vn_object_id);
						if ($t_object->isLoaded()) { $t_object->deriveCurrentLocationForBrowse(); }
					}
				}
			}
			return $vn_rc;
		}
		# ------------------------------------------------------
	}