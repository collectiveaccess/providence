<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/FindObjectsController.php : controller for object search request handling
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2016 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/BaseSearchController.php");
 	require_once(__CA_LIB_DIR__."/Search/ObjectSearch.php");
 	require_once(__CA_LIB_DIR__."/Browse/ObjectBrowse.php");
 	require_once(__CA_LIB_DIR__."/GeographicMap.php");
	require_once(__CA_MODELS_DIR__."/ca_objects.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
	require_once(__CA_MODELS_DIR__."/ca_set_items.php");
	require_once(__CA_MODELS_DIR__."/ca_set_item_labels.php");
	require_once(__CA_LIB_DIR__.'/Media/MediaViewerManager.php');
 	
 	class SearchObjectsController extends BaseSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_objects';
 		
 		/** 
 		 * Number of items per search results page
 		 */
 		protected $opa_items_per_page = array(8, 16, 24, 32);
 		 
 		/**
 		 * List of search-result views supported for this find
 		 * Is associative array: values are view labels, keys are view specifier to be incorporated into view name
 		 */ 
 		protected $opa_views;
 		
 		/**
 		 * Name of "find" used to defined result context for ResultContext object
 		 * Must be unique for the table and have a corresponding entry in find_navigation.conf
 		 */
 		protected $ops_find_type = 'basic_search';
 		 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			$this->opa_views = array(
				'thumbnail' => _t('thumbnails'),
				'full' => _t('full'),
				'list' => _t('list')
			 );

			 $this->opo_browse = new ObjectBrowse($this->opo_result_context->getParameter('browse_id'), 'providence');
		}
 		# -------------------------------------------------------
 		/**
 		 * Search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseSearchController->Index() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. ObjectSearch for objects, EntitySearch for entities) and pass it to BaseSearchController->Index() 
 		 */ 
 		public function Index($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			AssetLoadManager::register('imageScroller');
 			AssetLoadManager::register('tabUI');
 			AssetLoadManager::register('panel');
            return parent::Index($pa_options);
 		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function GetMediaData() {
			$ps_identifier = $this->request->getParameter('identifier', pString);
			if (!($va_identifier = caParseMediaIdentifier($ps_identifier))) {
				// error: invalid identifier
				die("Invalid identifier");
			}
		
			$t_rep = new ca_object_representations($vn_representation_id = $va_identifier['id']);
		
			if (!($vs_viewer_name = MediaViewerManager::getViewerForMimetype("media_overlay", $vs_mimetype = $t_rep->getMediaInfo('media', 'original', 'MIMETYPE')))) {
				// error: no viewer available
				die("Invalid viewer $vs_mimetype");
			}
		
			$this->response->addContent($vs_viewer_name::getViewerData($this->request, "representation:{$vn_representation_id}", ['request' => $this->request, 't_subject' => null, 't_instance' => $t_rep, 'display' => caGetMediaDisplayInfo('media_overlay', $vs_mimetype)]));
		}
 		# -------------------------------------------------------
 		/**
 		 * Ajax action that returns info on a mapped location based upon the 'id' request parameter.
 		 * 'id' is a list of object_ids to display information before. Each integer id is separated by a semicolon (";")
 		 * The "ca_objects_results_map_balloon_html" view in Results/ is used to render the content.
 		 */ 
 		public function getMapItemInfo() {
 			$pa_object_ids = explode(';', $this->request->getParameter('id', pString));
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			
 			$this->view->setVar('ids', $pa_object_ids);
 			$this->view->setVar('access_values', $va_access_values);
 			
 		 	$this->render("Results/ca_objects_results_map_balloon_html.php");
 		}
 		# -------------------------------------------------------
 		# Sidebar info handler
 		# -------------------------------------------------------
 		/**
 		 * Returns "search tools" widget
 		 */ 
 		public function Tools($pa_parameters) {
 			// pass instance of subject-appropriate search object as second parameter (ex. for an object search this is an instance of ObjectSearch()
 			return parent::Tools($pa_parameters);
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function _getSubTypeActionNav($pa_item) {
 			return [
				[
					'displayName' => _t('Search'),
					"default" => ['module' => 'find', 'controller' => 'SearchObjects', 'action' => 'Index'],
					'parameters' => array(
						'type_id' => $pa_item['item_id'],
						'reset' => $this->request->getUser()->getPreference('persistent_search')
					),
					'is_enabled' => true,
				],
				[
					'displayName' => _t('Advanced search'),
					"default" => ['module' => 'find', 'controller' => 'SearchObjectsAdvanced', 'action' => 'Index'],
					'useActionInPath' => 1,
					'parameters' => array(
						'type_id' => $pa_item['item_id'],
						'reset' => $this->request->getUser()->getPreference('persistent_search')
					),
					'is_enabled' => true,
				],
				[
					'displayName' => _t('Browse'),
					"default" => ['module' => 'find', 'controller' => 'BrowseObjects', 'action' => 'Index'],
					'parameters' => array(
						'type_id' => $pa_item['item_id']
					),
					'is_enabled' => true,
				]
			];
 		}
 		# -------------------------------------------------------
 	}