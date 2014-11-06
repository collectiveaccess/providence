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

	public function __construct($po_request,$ps_table="") {

		parent::__construct($po_request,$ps_table);
	}
	# -------------------------------------------------------


	protected function search($pa_bundles=null){
		$va_return = parent::search($pa_bundles);
		if(($this->getTableName() == 'ca_entities') && is_array($va_return['results']) && sizeof($va_return['results'])>0) {
			$o_db = new Db();
			$t_element = new ca_metadata_elements();

			$t_element->load(array('element_code' => 'is_index_display'));
			$vn_element_id = $t_element->getPrimaryKey();
			if(!$vn_element_id) { return $va_return; }
			$vn_yes_id = caGetListItemID('yn', 'yes');


			foreach($va_return['results'] as &$va_result) {
				$vn_entity_id = $va_result['entity_id'];

				$qr_objects = $o_db->query("
					SELECT DISTINCT ca_objects.object_id FROM ca_entities, ca_objects_x_entities, ca_objects, ca_attributes, ca_attribute_values
					WHERE ca_entities.entity_id = ca_objects_x_entities.entity_id
					AND ca_attributes.row_id = ca_objects.object_id
					AND ca_attribute_values.attribute_id = ca_attributes.attribute_id
					AND ca_objects_x_entities.object_id = ca_objects.object_id
					AND ca_attribute_values.element_id = ?
					AND ca_entities.entity_id = ?
					AND ca_attribute_values.item_id = ?
					AND ca_objects.deleted = 0
				", $vn_element_id, $vn_entity_id, $vn_yes_id);

				while($qr_objects->nextRow()) {
					$va_object_info = array();
					$vn_object_id = $qr_objects->get('ca_objects.object_id');
					if(MemoryCache::contains($vn_object_id, 'AboutDrawingServices')) {
						$va_object_info = MemoryCache::fetch($vn_object_id, 'AboutDrawingServices');
					} else {
						$va_object_info['object_id'] = $vn_object_id;

						$qr_labels = $o_db->query("
							SELECT ca_object_labels.* FROM ca_object_labels, ca_objects
							WHERE ca_object_labels.object_id = ca_objects.object_id
							AND ca_objects.object_id = ?
						", $vn_object_id);

						$vs_label = '';
						while($qr_labels->nextRow()) {
							$va_object_info['labels'][] = $qr_labels->getRow();
							$vs_label = $qr_labels->get('name'); // keep the last label around
						}

						$va_object_info['label'] = $vs_label;

						$qr_reps = $o_db->query("
							SELECT ca_object_representations.* FROM ca_object_representations, ca_objects_x_object_representations, ca_objects
							WHERE ca_object_representations.representation_id = ca_objects_x_object_representations.representation_id
							AND ca_objects_x_object_representations.object_id = ca_objects.object_id
							AND ca_objects.object_id = ?
							AND ca_objects.deleted = 0 AND ca_object_representations.deleted = 0
						", $vn_object_id);

						while($qr_reps->nextRow()) {
							$va_object_info['representations'][] = array(
								'representation_id' => $qr_reps->get('representation_id'),
								'icon' => $qr_reps->getMediaUrl('media', 'icon'),
								'preview170' => $qr_reps->getMediaUrl('media', 'preview170'),
								'medium' => $qr_reps->getMediaUrl('media', 'medium'),
								'original' => $qr_reps->getMediaUrl('media', 'original'),
							);
						}

						MemoryCache::save($vn_object_id, $va_object_info, 'AboutDrawingServices');
					}

					$va_result['ca_objects'][] = $va_object_info;
				}

				/* the old&simple, but very slow code
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
				}*/
			}
		}
		return $va_return;
	}

}
