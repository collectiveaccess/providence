<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/UserGroupController.php : 
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
 	require_once(__CA_LIB_DIR__."/ca/BaseLookupController.php");
 
 	class UserGroupController extends BaseLookupController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = false;
 		protected $ops_table_name = 'ca_user_groups';		// name of "subject" table (what we're editing)
 		protected $ops_name_singular = 'user group';
 		protected $ops_search_class = 'UserGroupSearch';
 		protected $opa_filters = array();
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			//if (!$this->request->user->canDoAction('is_administrator')) {
			//	$this->opa_filters = array("ca_user_groups.user_id" => array($this->request->getUserID()));
			//} else {
			//	$this->opa_filters = array();
			//}
			return parent::Get($pa_additional_query_params, $pa_options);
		}
 		# -------------------------------------------------------
 	}
 ?>
