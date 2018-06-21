<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/PlaceController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/BaseLookupController.php");
 
 	class SitePageMediaController extends BaseLookupController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $ops_table_name = 'ca_site_page_media';		// name of "subject" table (what we're editing)
 		protected $ops_name_singular = 'media';
 		protected $ops_search_class = null;
 		
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function Get($pa_additional_query_params=null, $pa_options=null) {
		    // Not implemented
 			return null;
		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function GetHierarchyLevel() {
 		    // Not implemented
 		    return null;
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function GetHierarchyAncestorList() {
 		    // Not implemented
 			return null;
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
		public function Intrinsic() {
		    // Not implemented
 			return null;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function Attribute() {
		    // Not implemented
 			return null;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */
		public function SetSortOrder() {
		    // Not implemented
 			return null;
		}
 		# -------------------------------------------------------
 	}