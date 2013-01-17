<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/VocabularyController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__."/core/Media.php");
 	require_once(__CA_LIB_DIR__."/core/Media/MediaProcessingSettings.php");
 	require_once(__CA_LIB_DIR__."/ca/BaseEditorController.php");
 	require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ListItemSearch.php");
 	require_once(__CA_MODELS_DIR__."/ca_objects.php");
 	
 
 require_once(__CA_LIB_DIR__."/ca/BaseLookupController.php");
 
 	class VocabularyController extends BaseLookupController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = true;
 		protected $ops_table_name = 'ca_list_items';		// name of "subject" table (what we're editing)
 		protected $ops_name_singular = 'vocabulary';
 		protected $ops_search_class = 'ListItemSearch';
 		# -------------------------------------------------------
 		public function Get($pa_additional_query_params=null, $pa_options=null) {
 			$pa_options = array();
 			$pa_additional_query_params = array('ca_lists.use_as_vocabulary:1');
 			if ($ps_list = $this->request->getParameter('list', pString)) {
				if(!is_array($pa_additional_query_params)) { $pa_additional_query_params = array(); }
				
				$pa_additional_query_params[] = "ca_lists.list_code:{$ps_list}";
			} else {
				if ($ps_lists = $this->request->getParameter('lists', pString)) {
					if(!is_array($pa_additional_query_params)) { $pa_additional_query_params = array(); }
					
					$va_lists = explode(";", $ps_lists);
					$va_tmp = array();
					$pa_options['filters'] = array();
					foreach($va_lists as $vs_list) {
						if ($vs_list = trim($vs_list)) {
							$va_tmp[(int)preg_replace("![\"']+!", "", $vs_list)] = true;
						}
					}
					if (is_array($va_tmp) && sizeof($va_tmp)) {
						$pa_options['filters'][] = array("ca_list_items.list_id", "IN", join(",", array_keys($va_tmp)));
					}
				}
			}
			
 			return parent::Get($pa_additional_query_params, $pa_options);	// only lookup items in lists with use_as_vocabulary set
 		}
 		# -------------------------------------------------------
 	}
 ?>