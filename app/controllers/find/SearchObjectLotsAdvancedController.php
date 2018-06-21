<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/SearchObjectLotsAdvancedController.php : controller for "advanced" place search request handling
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2015 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/BaseAdvancedSearchController.php");
 	require_once(__CA_LIB_DIR__."/Search/ObjectLotSearch.php");
 	require_once(__CA_LIB_DIR__."/Browse/ObjectLotBrowse.php");
	require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	
 	class SearchObjectLotsAdvancedController extends BaseAdvancedSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_object_lots';
 		
 		/** 
 		 * Number of items per search results page
 		 */
 		protected $opa_items_per_page = array(10, 20, 30, 40, 50);
 		 
 		/**
 		 * List of search-result views supported for this find
 		 * Is associative array: values are view labels, keys are view specifier to be incorporated into view name
 		 */ 
 		protected $opa_views;
 		
 		/**
 		 * Name of "find" used to defined result context for ResultContext object
 		 * Must be unique for the table and have a corresponding entry in find_navigation.conf
 		 */
 		protected $ops_find_type = 'advanced_search';
 		 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			$this->opa_views = array(
				'list' => _t('list')
			 );
			 
			 $this->opo_browse = new ObjectLotBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
		}
 		# -------------------------------------------------------
 		/**
 		 * Advanced search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseAdvancedSearchController->Index() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. ObjectSearch for objects, ObjectLotSearch for ObjectLots) and pass it to BaseAdvancedSearchController->Index() 
 		 */ 
 		public function Index($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			AssetLoadManager::register('imageScroller');
 			AssetLoadManager::register('tabUI');
 			return parent::Index($pa_options);
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
 		 * Returns "search tools" widget
 		 */ 
 		public function Tools($pa_parameters) {
 			return parent::Tools($pa_parameters);
 		}
 		# -------------------------------------------------------
 	}