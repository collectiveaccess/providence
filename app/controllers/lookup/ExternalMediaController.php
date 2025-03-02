<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ExternalMediaController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
require_once(__CA_APP_DIR__."/helpers/externalMediaHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");
require_once(__CA_LIB_DIR__.'/InformationServiceManager.php');
  
class ExternalMediaController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$request, &$response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	/**
	 * Fetch details on an item from a remote data source and output results of the 'display' key in the response.
	 *
	 */
	public function GetDetail() {
		$element_id = $this->request->getParameter('element_id', pInteger);
		$t_element = new ca_metadata_elements($element_id);
		$data = [];
		if (!$t_element->getPrimaryKey()) { 
			// error
			$items['error'] = ['label' => _t('ERROR: Invalid element_id'), 'idno' => ''];
		} else {
			$service = $t_element->getSetting('service');
			$settings = $t_element->getSettings();
			
			$attribute_id = $this->request->getParameter('id', pInteger);
			
			$t_attr_val = new ca_attribute_values();
			if ($t_attr_val->load(array('attribute_id' => $attribute_id, 'element_id' => $element_id))) {
				$t_attr = new ca_attributes();
				if ($t_attr->load($attribute_id)) {
					if (!caCanRead($this->request->getUserID(), $t_attr->get('table_num'), $t_attr->get('row_id'), $t_element->get('element_code'))) {
						$items['error'] = array('label' => _t('ERROR: You do not have access to this item'), 'idno' => '');
					} else {			
						$url = $t_attr_val->get('value_longtext1');
						$this->view->setVar('embed', caGetExternalMediaEmbedCode($url, ['width' => caGetOption('mediaWidth', $settings, '670px'), 'height' => caGetOption('mediaHeight', $settings, '300px')]));
					}
				}
			}
		}
		
		return $this->render('ajax_external_media_detail_html.php');
	}
	# -------------------------------------------------------
}
