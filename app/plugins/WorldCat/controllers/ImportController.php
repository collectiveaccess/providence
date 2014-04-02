<?php
/* ----------------------------------------------------------------------
 * app/plugins/WorldCat/controllers/ImportController.php :
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
 * ----------------------------------------------------------------------
 */

 	require_once(__CA_LIB_DIR__.'/core/Plugins/InformationService/WorldCat.php');
 	require_once(__CA_MODELS_DIR__.'/ca_data_importers.php');
 	

 	class ImportController extends ActionController {
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
  		protected $opo_config;		// plugin configuration file

 		# -------------------------------------------------------
 		# Constructor
 		# -------------------------------------------------------
		/**
		 *
		 */
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->user->canDoAction('can_import_worldcat')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/WorldCat/conf/worldcat.conf');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Index() {
 			
 		
 			$this->render(__CA_THEME__."/import_settings_html.php");
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Run() {
 			$pa_worldcat_ids = $this->request->getParameter('WorldCatID', pArray);
 			
 			ca_data_importers::importDataFromSource(join(",", $pa_worldcat_ids), 'WorldCatBooks', array('format' => 'WorldCat'));
 			$this->render(__CA_THEME__."/import_settings_html.php");
 		}
 		# -------------------------------------------------------	
 		#
 		# -------------------------------------------------------	
 		/**
 		 *
 		 */
 		public function Lookup() {
 			$o_wc = new WLPlugInformationServiceWorldCat();	
 			
 			$this->view->setVar('results', $o_wc->lookup(array(), $this->request->getParameter('term', pString), array()));
 			
 			$this->render(__CA_THEME__."/ajax_worldcat_lookup_json.php");
 		}
 		# -------------------------------------------------------		
 	}
 ?>