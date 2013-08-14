<?php
/* ----------------------------------------------------------------------
 * app/controllers/administrate/setup/ListsController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
 	require_once(__CA_LIB_DIR__."/ca/Browse/ListItemBrowse.php");
 	
 	class ListsController extends BaseSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_list_items';
 		
 		/** 
 		 * Number of items per search results page
 		 */
 		protected $opa_items_per_page = array(10, 20, 30, 40, 50);
 		
 		/**
 		 * List of search-result views supported for this find
 		 * Is associative array: keys are view labels, values are view specifier to be incorporated into view name
 		 */
 		protected $opa_views;
 		
 		/**
 		 * List of available search-result sorting fields
 		 * Is associative array: values are display names for fields, keys are full fields names (table.field) to be used as sort
 		 */
 		protected $opa_sorts;
 		
 		/**
 		 * Name of "find" used to defined result context for ResultContext object
 		 * Must be unique for the table and have a corresponding entry in find_navigation.conf
 		 */
 		protected $ops_find_type = 'basic_search';
 		
 		
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			$this->opa_views = array(
				'list' => _t('list'),
				'editable' => _t('editable')
			 );
			 
			 $this->opa_sorts = array_merge(array(
			 	'_natural' => _t('relevance'),
				'ca_list_item_labels.name_singular' => _t('name'),
				'ca_list_items.idno_sort' => _t('idno')
			), $this->opa_sorts);
			
			$this->opo_browse = new ListItemBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
			
			//
			// We need to set the same "last find" for ca_lists as well as ca_list_items; here we set
			// it for ca_lists... ca_list_items is taken care of automatically by BaseFindController 
			// because $this->ops_tablename is set to ca_list_items
			//
 			$o_ca_lists_result_context = new ResultContext($po_request, 'ca_lists', $this->ops_find_type);
 			$o_ca_lists_result_context->setAsLastFind();
			$o_ca_lists_result_context->saveContext();
		}
 		# -------------------------------------------------------
 		/**
 		 * Search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseSearchController->Search() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. PlaceSearch for objects, EntitySearch for entities) and pass it to BaseSearchController->Search() 
 		 */ 
 		public function Index($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			$this->opb_uses_hierarchy_browser = true;
 			return parent::Index($pa_options);
 		}
 		# -------------------------------------------------------
 		public function ViewHierarchy() {
 			$this->view->setVar('force_hierarchy_browser_open', 1);
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("list/vocabulary term") : _t("list/vocabulary terms");
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of this controller (minus the "Controller" part)
 		 */
 		public function controllerName() {
 			return 'Lists';
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
 		 * Returns "search tools" widget
 		 */ 
 		public function Tools($pa_parameters) {
 			// pass instance of subject-appropriate search object as second parameter (ex. for an object search this is an instance of PlaceSearch()
 			return parent::Tools($pa_parameters);
 		}
 		# -------------------------------------------------------
 	}
 ?>