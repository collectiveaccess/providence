<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/ObjectEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
require_once(__CA_MODELS_DIR__."/ca_objects.php"); 
require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
require_once(__CA_MODELS_DIR__."/ca_object_representation_multifiles.php");
require_once(__CA_LIB_DIR__."/Media.php");
require_once(__CA_LIB_DIR__."/Media/MediaProcessingSettings.php");
require_once(__CA_LIB_DIR__."/BaseEditorController.php");
require_once(__CA_LIB_DIR__."/MediaContentLocationIndexer.php");


class ObjectEditorController extends BaseEditorController {
	# -------------------------------------------------------
	protected $ops_table_name = 'ca_objects';		// name of "subject" table (what we're editing)
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		AssetLoadManager::register('panel');
	}
	# -------------------------------------------------------
	public function Edit($pa_values=null, $pa_options=null) {
		$va_values = array();
		
		if ($vn_lot_id = $this->request->getParameter('lot_id', pInteger)) {
			$t_lot = new ca_object_lots($vn_lot_id);
			
			if ($t_lot->getPrimaryKey()) {
				$va_values['lot_id'] = $vn_lot_id;
				
				if (!$this->request->getAppConfig()->get('ca_objects_dont_inherit_idno_from_lot')) {
					$va_values['idno'] = $t_lot->get('idno_stub');
				}
			}
		}
		
		return parent::Edit($va_values, $pa_options);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function postSave($t_object, $pb_is_insert) {
		if (
			$this->request->config->get('ca_objects_x_collections_hierarchy_enabled') && 
			is_array($coll_rel_types = caGetObjectCollectionHierarchyRelationshipTypes()) && 
			sizeof($coll_rel_types) &&
			($collection_id = $this->request->getParameter('collection_id', pInteger)) &&
			!$t_object->relationshipExists('ca_collections', $collection_id, $coll_rel_types[0])
		) {
			if (!($t_object->addRelationship('ca_collections', $collection_id, $coll_rel_types[0]))) {
				$this->notification->addNotification(_t("Could not add parent collection to object: %1", join("; ", $t_object->getErrors())), __NOTIFICATION_TYPE_ERROR__);
			}
			$t_object->isChild();
		}
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	public function info($pa_parameters) {
		parent::info($pa_parameters);
		return $this->render('widget_object_info_html.php', true);
	}
	# -------------------------------------------------------
}
