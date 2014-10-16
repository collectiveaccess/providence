<?php
/** ---------------------------------------------------------------------
 * app/plugins/aboutDrawingServices/services/AboutDrawingSearchService.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

 /**
  *
  */
  
require_once(__CA_LIB_DIR__."/ca/Service/SearchJSONService.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");

class AboutDrawingSearchService extends SearchJSONService {

	public function __construct($po_request,$ps_table=""){

		if(!$po_request->getUser()->canDoAction('can_use_about_drawing_search_service')) {
			throw new Exception("You're not allowed to access this service.");
		}

		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------


	protected function search($pa_bundles=null){
		$va_return = parent::search($pa_bundles);
		if(($this->getTableName() == 'ca_entities') && is_array($va_return['results']) && sizeof($va_return['results'])>0) {
			foreach($va_return['results'] as &$va_result) {
				$t_entity = new ca_entities($va_result['entity_id']);
				$va_objects = $t_entity->getRelatedItems('ca_objects');

				foreach($va_objects as $va_object) {
					$t_object = new ca_objects($va_object['object_id']);
					$va_reps = $t_object->getRepresentations(array('icon', 'preview170', 'medium', 'original'));
					$va_representation_info = array();
					foreach($va_reps as $va_rep) {
						$va_representation_info[] = array(
							'representation_id' => $va_rep['representation_id'],
							'urls' => $va_rep['urls'],
						);
					}

					$va_object_info = array();
					$va_object_info['object_id'] = $va_object['object_id'];
					$va_object_info['labels'] = $va_object['labels'];
					$va_object_info['label'] = $va_object['label'];
					$va_object_info['representations'] = $va_representation_info;

					$va_result['ca_objects'][] = $va_object_info;
				}
			}
		}
		return $va_return;
	}

}
