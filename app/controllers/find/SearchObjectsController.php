<?php
/* ----------------------------------------------------------------------
 * app/controllers/find/FindObjectsController.php : controller for object search request handling
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
 	require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ObjectSearch.php");
 	require_once(__CA_LIB_DIR__."/ca/Browse/ObjectBrowse.php");
 	require_once(__CA_LIB_DIR__."/core/GeographicMap.php");
	require_once(__CA_MODELS_DIR__."/ca_objects.php");
	require_once(__CA_MODELS_DIR__."/ca_sets.php");
	require_once(__CA_MODELS_DIR__."/ca_set_items.php");
	require_once(__CA_MODELS_DIR__."/ca_set_item_labels.php");
 	
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
				'thumbnail' => _t('thumbnails'),
				'full' => _t('full'),
				'list' => _t('list'),
				'editable' => _t('editable')
			 );
			 
			 $this->opa_sorts = array_merge(array(
			 	'_natural' => _t('relevance'),
			 	'ca_object_labels.name_sort' => _t('title'),
			 	'ca_objects.type_id' => _t('type'),
			 	'ca_objects.idno_sort' => _t('idno')
			 ), $this->opa_sorts);
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
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('tabUI');
 			JavascriptLoadManager::register('panel');
 			return parent::Index($pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function getPartialResult($pa_options=null) {
 			$pa_options['search'] = $this->opo_browse;
 			return parent::getPartialResult($pa_options);
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		public function saveInlineEdit($pa_options=null) {
 			global $g_ui_locale_id;
 			
 			$ps_table = $this->request->getParameter('table', pString);
 			$ps_bundle = $this->request->getParameter('bundle', pString);
 			$pa_bundle = explode("-", $ps_bundle);
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$ps_val = $this->request->getParameter('value', pString);
 			
 			$vs_resp = array();
 			$o_dm = Datamodel::load();
 			if (!($t_instance = $o_dm->getInstanceByTableName($ps_table, true))) {
 				$va_resp = array(
 					'error' => 100,
 					'message' => _t('Invalid table: %1', $ps_table)
 				);
 			} else {
				if (!$t_instance->load($pn_id)) {
					$va_resp = array(
						'error' => 100,
						'message' => _t('Invalid id: %1', $pn_id)
					);
				} else {
					if ($pa_bundle[0] == 'preferred_labels') {
						$vn_label_id = $t_instance->getPreferredLabelID($g_ui_locale_id);
						
						$va_label_values = array();
						if (sizeof($pa_bundle) == 1) {
							// is generic "preferred_labels"
							$va_label_values[$t_instance->getLabelDisplayField()] = $ps_val;
						} else {
							$vs_preferred_label_element = $pa_bundle[1];
							$va_label_values[$vs_preferred_label_element] = $ps_val;
						}
						
						if ($vn_label_id) {
							$t_instance->editLabel($vn_label_id, $va_label_values, $g_ui_locale_id, null, true);	// TODO: what about type?
						} else {
							$t_instance->addLabel($va_label_values, $g_ui_locale_id, null, true);
						}
						
						if ($t_instance->numErrors()) {
							$va_resp = array(
								'error' => 100,
								'message' => _t('Could not set preferred label %1 to %2: %3', $ps_bundle, $ps_val, join("; ", $t_instance->getErrors()))
							);
						} else {
							$va_resp = array(
								'error' => 0,
								'message' => _t('Set preferred label %1 to %2', $ps_bundle, $ps_val)
							);
						}
					} elseif ($t_instance->hasField($ps_bundle)) {
					
						$va_resp = array(
							'error' => 0,
							'message' => _t('Set %1 to %2', $ps_bundle, $ps_val)
						);
					} elseif ($t_instance->hasElement($ps_bundle)) {
						// Check it it repeats?
						
						// Check if it's a supported type?
						
						$t_instance->setMode(ACCESS_WRITE);
						$t_instance->replaceAttribute(array(
							'locale_id' => $g_ui_locale_id,
							$ps_bundle => $ps_val
						), $ps_bundle);
						
						$t_instance->update();
						
						if ($t_instance->numErrors()) {
							$va_resp = array(
								'error' => 100,
								'message' => _t('Could not set %1 to %2: %3', $ps_bundle, $ps_val, join("; ", $t_instance->getErrors()))
							);
						} else {
							$va_resp = array(
								'error' => 0,
								'message' => _t('Set %1 to %2', $ps_bundle, $ps_val),
								'value' => $t_instance->get($ps_table.'.'.$ps_bundle)
							);
						}
					} else {
						$va_resp = array(
							'error' => 100,
							'message' => _t('Invalid bundle: %1', $ps_bundle)
						);
					}
				}
			}
 			
 			print json_encode($va_resp);
 		}
 		# -------------------------------------------------------
 		/**
 		 * QuickLook
 		 */
 		public function QuickLook() {
 			$vn_object_id = (int)$this->request->getParameter('object_id', pInteger);
 			$t_object = new ca_objects($vn_object_id);
 			$t_rep = new ca_object_representations($t_object->getPrimaryRepresentationID());
 			
 			$this->response->addContent($t_rep->getRepresentationViewerHTMLBundle($this->request, array('display' => 'media_overlay', 'object_id' => $vn_object_id, 'containerID' => 'caMediaPanelContentArea')));
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
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("object") : _t("objects");
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
 	}
 ?>
