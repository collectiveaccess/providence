<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/BrowseObjectsController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 
 	require_once(__CA_LIB_DIR__."/ca/BaseBrowseController.php");
 	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
 	require_once(__CA_LIB_DIR__."/core/GeographicMap.php");
 
 	class BrowseObjectsController extends BaseBrowseController {
 		# -------------------------------------------------------
 		 /** 
 		 * Name of table for which this browse returns items
 		 */
 		 protected $ops_tablename = 'ca_objects';
 		 
 		/** 
 		 * Number of items per results page
 		 */
 		protected $opa_items_per_page = array(8, 16, 24, 32);
 		 
 		/**
 		 * List of result views supported for this browse
 		 * Is associative array: keys are view labels, values are view specifier to be incorporated into view name
 		 */ 
 		protected $opa_views;
 		 
 		 
 		/**
 		 * List of available result sorting fields
 		 * Is associative array: values are display names for fields, keys are full fields names (table.field) to be used as sort
 		 */
 		protected $opa_sorts;
 		
 		/**
 		 * Name of "find" used to defined result context for ResultContext object
 		 * Must be unique for the table and have a corresponding entry in find_navigation.conf
 		 */
 		protected $ops_find_type = 'basic_browse';
 		 
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_browse = new ObjectBrowse($this->opo_result_context->getSearchExpression(), 'providence');

 			
 			$this->opa_views = array(
				'thumbnail' => _t('thumbnails'),
				'full' => _t('full'),
				'list' => _t('list'),
				'editable' => _t('editable')
			);
			 
			$this->opa_sorts = array_merge(array(
			 	'ca_object_labels.name' => _t('title'),
			 	'ca_objects.type_id' => _t('type'),
			 	'ca_objects.idno_sort' => _t('idno')
			), $this->opa_sorts);
 		}
 		# -------------------------------------------------------
 		public function Index($pa_options=null) {
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('tabUI');
 			JavascriptLoadManager::register('panel');
 			
 			parent::Index($pa_options);
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
 		/**
 		 * Returns string representing the name of the item the browse will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function browseName($ps_mode='singular') {
 			return ($ps_mode === 'singular') ? _t('object') : _t('objects');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of this controller (minus the "Controller" part)
 		 */
 		public function controllerName() {
 			return 'BrowseObjects';
 		}
 		# -------------------------------------------------------
 	}
 ?>