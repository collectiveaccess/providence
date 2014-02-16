<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/ObjectRelationshipBaseModel.php :
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
 require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 
	class ObjectRelationshipBaseModel extends BaseRelationshipModel {
		# ------------------------------------------------------
		protected function initLabelDefinitions($pa_options=null) {
			parent::initLabelDefinitions($pa_options);
			$this->BUNDLES['ca_object_representation_chooser'] = array('type' => 'special', 'repeating' => false, 'label' => _t('Media representation chooser'));
		}
		# ------------------------------------------------------
		# Bundles
		# ------------------------------------------------------
		/**
		 * Returns HTML bundle for picking representations to attach to an object-* relationship
		 *
		 * @param object $po_request The current request
		 * @param $ps_form_name The name of the HTML form this bundle will be part of
		 *
		 * @return string HTML for bundle
		 */
		public function getRepresentationChooserHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings, $pa_options=null) {
			
		
			$o_view = new View($po_request, $po_request->getViewsDirectoryPath().'/bundles/');	
		
			$o_view->setVar('lookup_urls', caJSONLookupServiceUrl($po_request, $this->getAppDatamodel()->getTableName($this->get('table_num'))));
			$o_view->setVar('t_subject', $this);
			
			$vn_object_id = ($this->getLeftTableName() == 'ca_objects') ? $this->get($this->getLeftTableFieldName()) : $this->get($this->getRightTableFieldName());
			$o_view->setVar('t_object', $t_object = new ca_objects($vn_object_id));
			
			$o_view->setVar('id_prefix', $ps_form_name);	
			$o_view->setVar('placement_code', $ps_placement_code);	
			$o_view->setVar('element_code', caGetOption('element_code', $pa_bundle_settings, null));
			$o_view->setVar('settings', $pa_bundle_settings);
		
			return $o_view->render('ca_object_representation_chooser_html.php');
		}
		# ------------------------------------------------------
	}
?>
