<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/IntrinsicController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/core/Controller/ActionController.php");
 
 	//
 	// This lookup controller doesn't extend BaseLookupController
 	// since direct lookups on attributes are handled specially – not via the search engine
 	class IntrinsicController extends ActionController {
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			$ps_query = $this->request->getParameter('q', pString);
			$ps_bundle = $this->request->getParameter('bundle', pString);
			
			$va_tmp = explode('.', $ps_bundle);
			$vs_table = $va_tmp[0];
			$vs_field = $va_tmp[1];
			
			$o_dm = Datamodel::load();
			
			if (!($t_table = $o_dm->getInstanceByTableName($vs_table, true))) {
				// bad table name
				print _t("Invalid table name");
				return null;
			}
			
			if (!($t_table->hasField($vs_field)) || (!in_array($t_table->getFieldInfo($vs_field, 'FIELD_TYPE'), array(FT_TEXT, FT_NUMBER)))) {
				// bad field name
				print _t("Invalid bundle name");
				return null;
			}
			
			if ($this->request->user->getBundleAccessLevel($vs_table, $vs_field) == __CA_BUNDLE_ACCESS_NONE__) {
				print _t("You do not have access to this bundle");
				return null;
			}
			
			$vn_max_returned_values = 50;
			
			$o_db = new Db();
			
			$qr_res = $o_db->query("
				SELECT DISTINCT {$vs_field}
				FROM {$vs_table}
				WHERE
					({$vs_field} LIKE ?)
				ORDER BY
					{$vs_field}
				LIMIT {$vn_max_returned_values}
			", (string)$ps_query.'%');
		
			$this->view->setVar('intrinsic_value_list', $qr_res->getAllFieldValues($vs_field));
			return $this->render('ajax_intrinsic_value_list_html.php');
		}
		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of direct children for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyLevel() {
 			// Not implemented
 			return null;
 		}
 		# -------------------------------------------------------
 		/**
 		 * Given a item_id (request parameter 'id') returns a list of ancestors for use in the hierarchy browser
 		 * Returned data is JSON format
 		 */
 		public function GetHierarchyAncestorList() {
 			// Not implemented
 			return null;
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function IDNo() {
			// Not implemented
			return null;
		}
		# -------------------------------------------------------
 		/**
 		 * Checks value of instrinsic field and return list of primary keys that use the specified value
 		 * Can be used to determine if a value that needs to be unique is actually unique.
 		 */
		public function Intrinsic() {
			// Not implemented
			return null;
		}
 		# -------------------------------------------------------
 	}
 ?>