<?php
/** ---------------------------------------------------------------------
 * app/lib/LocationRelationshipBaseModel.php :
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
 
/**
 *
 */
require_once(__CA_LIB_DIR__.'/BaseRelationshipModel.php');
require_once(__CA_LIB_DIR__.'/HistoryTrackingCurrentValueTrait.php');
 
class LocationRelationshipBaseModel extends BaseRelationshipModel {
	# ------------------------------------------------------
	/**
	 *
	 */
	public function insert($options=null) {
		if (!caGetOption('dontAutomaticallySetEffectiveDate', $options, false) && !$this->get('effective_date', array('getDirectDate' => true))) {  
			$this->set('effective_date', _t('now')); 
		}
		
		if($rc = parent::insert($options)) {
			if(!$this->setStorageLocationContainerReference()) { return false; }
		}
		return $rc;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function update($options=null) {
		if (!caGetOption('dontAutomaticallySetEffectiveDate', $options, false) && !$this->get('effective_date', array('getDirectDate' => true))) { 
			$this->set('effective_date', _t('now')); 
		}
		
		return parent::update($options);
	}	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function setStorageLocationContainerReference() {
		$t_loc = ca_storage_locations::findAsInstance($this->get('ca_storage_locations.location_id'));
		$new_parent_id = $t_loc->get('parent_id');
	
		if($new_parent_id) {
			$policies = HistoryTrackingCurrentValueTrait::getDependentHistoryTrackingCurrentValuePolicies('ca_storage_locations', ['type_id' => $t_loc->getTypeID()]);

			$type_code = $t_loc->getTypeCode();
			foreach($policies as $policy => $policy_info) {
				$dtls = $policy_info['elements']['ca_storage_locations'][$type_code] ?? $policy_info['elements']['ca_storage_locations']['__default__'] ?? null;
				if(!is_array($dtls)) { continue; }
		
				if(!is_array($container_types = $dtls['containerTypes'] ?? null)) { continue; }
				$container_ref_element_code = $dtls['containerReferenceElementCode'] ?? null;
				if(in_array($type_code, $container_types)) {				
					$this->addAttribute([$container_ref_element_code => $new_parent_id], $container_ref_element_code);
					$this->update();
						
					if($this->numErrors()) {
						return false;
					}
				}
			}
		}
	}
	# ------------------------------------------------------
}
