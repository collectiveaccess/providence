<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/ObjectCheckoutController.php : 
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
 	require_once(__CA_LIB_DIR__."/ca/BaseLookupController.php");
 
 	class ObjectCheckoutController extends BaseLookupController {
 		# -------------------------------------------------------
 		protected $opb_uses_hierarchy_browser = true;
 		protected $ops_table_name = 'ca_object_checkouts';		// name of "subject" table (what we're editing)
 		protected $ops_name_singular = 'object checkout';
 		protected $ops_search_class = 'ObjectCheckoutSearch';
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			
			$pa_options['filters'][] = array("ca_object_checkouts.checkout_date", "IS NOT", "NULL");
			$pa_options['filters'][] = array("ca_object_checkouts.return_date", "IS", "NULL");
			return parent::Get($pa_additional_query_params, $pa_options);
		}
 		# -------------------------------------------------------
 	}