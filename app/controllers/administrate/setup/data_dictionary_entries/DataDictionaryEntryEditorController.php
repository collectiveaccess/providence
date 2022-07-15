<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/DataDictionaryEntryEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
 
 	require_once(__CA_MODELS_DIR__."/ca_metadata_dictionary_entries.php");
 	require_once(__CA_LIB_DIR__."/BaseEditorController.php");
 	
 
 	class DataDictionaryEntryEditorController extends BaseEditorController {
 		# -------------------------------------------------------
 		protected $ops_table_name = 'ca_metadata_dictionary_entries';		// name of "subject" table (what we're editing)
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->user->canDoAction("can_configure_data_dictionary")) { throw new ApplicationException(_t('Data dictionary is not available')); }
 		}
 		# -------------------------------------------------------
 		protected function _initView($pa_options=null) {
 			AssetLoadManager::register('bundleableEditor');
 			AssetLoadManager::register('sortableUI');
 			AssetLoadManager::register('bundleListEditorUI');
 			
 			$va_init = parent::_initView($pa_options);
 			if (!$va_init[1]->getPrimaryKey()) {
 				$va_init[1]->set('user_id', $this->getRequest()->getUserID());
 				$va_init[1]->set('table_num', $this->getRequest()->getParameter('table_num', pInteger));
 			}
 			
 			return $va_init;
 		}
 		# -------------------------------------------------------
 		public function Edit($pa_values=null, $pa_options=null) {
 			return parent::Edit($pa_values, $pa_options);
 		}
 		# -------------------------------------------------------
 		public function Delete($pa_options=null) {
 			return parent::Delete($pa_options); 
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		public function Info($pa_parameters) {
 			parent::info($pa_parameters);

 			return $this->render('widget_data_dictionary_entry_info_html.php', true);
 		}
		# -------------------------------------------------------
 	}
