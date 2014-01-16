<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseBrowseController.php : base controller for search interface
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');
 	require_once(__CA_LIB_DIR__.'/core/GeographicMap.php');
	require_once(__CA_MODELS_DIR__."/ca_bundle_displays.php");
	require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
	require_once(__CA_MODELS_DIR__."/ca_lists.php");
 	
	class BaseDetailController extends ActionController {
		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $ops_context = '';
		protected $opo_browse;
		protected $ops_tablename;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_datamodel = Datamodel::load();
 			
 			JavascriptLoadManager::register('maps');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Sets current browse context
 		 * Settings for the current browse are stored per-context. This means if you
 		 * have multiple interfaces in the same application using browse services
 		 * you can keep their settings (and caches) separate by varying the context.
 		 *
 		 * The browse engine and browse controller both have their own context settings
 		 * but the BaseDetailController is setup to make the browse engine's context its own.
 		 * Thus you only need set the context for the engine; the controller will inherit it.
 		 */
 		public function setContext($ps_context) {
 			$this->ops_context = $ps_context;
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns the current browse context
 		 */
 		public function getContext($ps_context) {
 			return $this->ops_context;
 		}
 		# -------------------------------------------------------
 		# Detail display
 		# -------------------------------------------------------
 		/**
 		 * Alias for show() method
 		 */
 		public function Index() {
 			$this->Show();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Generates detail detail. Will use a view named according to the following convention:
 		 *		<table_name>_<type_code>_detail_html.php
 		 *
 		 * So for example, the detail for objects of type 'artwork' (where 'artwork' is the type code for the artwork object type)
 		 * the view would be named "ca_objects_artwork_detail_html.php
 		 *
 		 * If the type specific view does not exist, then Show() will attemp to use a generic table-wide view name like this:
 		 *		<table_name>_detail_html.php
 		 *
 		 * For example: "ca_objects_detail_html.php"
 		 *
 		 * In general you should always have the table wide views defined. Then you can define type-specific views for your
 		 * application on an as-needed basis.
 		 */
 		public function Show() {
 			JavascriptLoadManager::register('browsable');
 			JavascriptLoadManager::register('imageScroller');
 			JavascriptLoadManager::register('maps3');
 			
 			$va_access_values = caGetUserAccessValues($this->request);
 			$this->view->setVar('access_values', $va_access_values);
 			
 			if(!$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true)) {
 				die("Invalid table name ".$this->ops_tablename." for detail");
 			}

 			if(!($vn_item_id = $this->request->getParameter($t_item->primaryKey(), pInteger))){
  				$this->notification->addNotification(_t("Invalid ID"), "message");
 				$this->response->setRedirect(caNavUrl($this->request, "", "", "", ""));
 				return;
 			}
 			if(!$t_item->load($vn_item_id)){
  				$this->notification->addNotification(_t("ID does not exist"), "message");
 				$this->response->setRedirect(caNavUrl($this->request, "", "", "", ""));
 				return;
 			}
 			
 			
 			#
 			# Enforce access control
 			#
 			if(sizeof($va_access_values) && !in_array($t_item->get("access"), $va_access_values)){
  				$this->notification->addNotification(_t("This item is not available for view"), "message");
 				$this->response->setRedirect(caNavUrl($this->request, "", "", "", ""));
 				return;
 			}
 			
 			//
 			// In-detail browsing of objects - limited to object linked to the item being displayed
 			//
 			if ($this->request->config->get('allow_browse_within_detail_for_'.$this->ops_tablename) && is_object($this->opo_browse)) {
 				// set browse context for controller
 				$this->setContext($this->opo_browse->getContext());
 				
 				$t_table = $this->opo_datamodel->getTableInstance($this->ops_tablename);
				if ($this->request->session->getVar($this->ops_tablename.'_'.$this->ops_appname.'_detail_current_item_id') != $vn_item_id) {
					$this->opo_browse->removeAllCriteria();	
				}
				
				// look for 'authority' facet for current detail table type so we can limit the object browse to the currently displayed item
				$vs_limit_facet_name = null;
				foreach($this->opo_browse->getInfoForFacets() as $vs_facet_name => $va_facet_info) {
					if (($va_facet_info['type'] === 'authority') && ($va_facet_info['table'] === $this->ops_tablename)) {
						$vs_limit_facet_name = $vs_facet_name;
						break;
					}
				}
				
				if ($vs_limit_facet_name) {
					$this->opo_browse->addCriteria($vs_limit_facet_name, array($vn_item_id));
					$this->opo_browse->execute(array('checkAccess' => $va_access_values));
					$this->request->session->setVar($this->ops_tablename.'_'.$this->ops_appname.'_detail_current_browse_id', $this->opo_browse->getBrowseID());
					$this->view->setVar('show_browse', true);
					
					//
					// Browse paging
					//
					$vn_items_per_page = $this->request->config->get("objects_per_page_for_detail_pages");
					if(!$vn_items_per_page){
						$vn_items_per_page = 12;
					}
					$this->view->setVar('page', ($vn_p = $this->request->getParameter('page', pInteger)) ? $vn_p : 1);
					
					if ($this->opo_browse) {
						$qr_hits = $this->opo_browse->getResults();
						$vn_num_pages = ceil($qr_hits->numHits()/$vn_items_per_page);
						$qr_hits->seek(($vn_p - 1) * $vn_items_per_page);
					} else {
						$vn_num_pages = 0;
					}
					$this->view->setVar('browse_results', $qr_hits);
					$this->view->setVar('num_pages', $vn_num_pages);
					$this->view->setVar('items_per_page', $vn_items_per_page);
					$this->view->setVar('opo_browse', $this->opo_browse);
 					$this->view->setVar('sorts', $this->opa_sorts);				// supported sorts for the object browse
				
					// browse criteria in an easy-to-display format
					$va_browse_criteria = array();
					foreach($this->opo_browse->getCriteriaWithLabels() as $vs_facet_code => $va_criteria) {
						$va_facet_info = $this->opo_browse->getInfoForFacet($vs_facet_code);
						
						$va_criteria_list = array();
						foreach($va_criteria as $vn_criteria_id => $vs_criteria_label) {
							$va_criteria_list[] = $vs_criteria_label;
						}
						
						$va_browse_criteria[$va_facet_info['label_singular']] = join('; ', $va_criteria_list);
					}
					$this->view->setVar('browse_criteria', $va_browse_criteria);
				} else {
					// not configured for browse
					$this->request->session->setVar($this->ops_tablename.'_'.$this->ops_appname.'_detail_current_browse_id', null);
					$this->view->setVar('show_browse', false);
				}
 			}
 			$this->request->session->setVar($this->ops_tablename.'_'.$this->ops_appname.'_detail_current_item_id', $vn_item_id);
 			
 			# Next and previous navigation
 			$opo_result_context = new ResultContext($this->request, $this->ops_tablename, ResultContext::getLastFind($this->request, $this->ops_tablename));
 			$this->view->setVar('next_id', $opo_result_context->getNextID($vn_item_id));
 			$this->view->setVar('previous_id', $opo_result_context->getPreviousID($vn_item_id));
 			
 			# Item instance and id
 			$this->view->setVar('t_item', $t_item);
 			$this->view->setVar($t_item->getPrimaryKey(), $vn_item_id);
 			
 			# Item  - preferred
 			$this->view->setVar('label', $t_item->getLabelForDisplay());
 			
 			# Item  - nonpreferred
 			$this->view->setVar('nonpreferred_labels', caExtractValuesByUserLocale($t_item->getNonPreferredLabels()));
 		
 			# Item timestamps (creation and last change)
 			if ($va_entry_info = $t_item->getCreationTimestamp()) {
 				$this->view->setVar('date_of_entry', date('m/d/Y', $va_entry_info['timestamp']));
 			}
 			
 			if ($va_last_change_info = $t_item->getLastChangeTimestamp()) {
 				$this->view->setVar('date_of_last_change', date('m/d/Y', $va_last_change_info['timestamp']));
 			}
 			
 			
 			# Media representations to display (objects only)
 			if (method_exists($t_item, 'getPrimaryRepresentationInstance')) {
 				if ($t_primary_rep = $t_item->getPrimaryRepresentationInstance()) {
 					if (!sizeof($va_access_values) || in_array($t_primary_rep->get('access'), $va_access_values)) { 		// check rep access
						$va_info = $t_primary_rep->getMediaInfo('media', 'original'); 
						$this->view->setVar('t_primary_rep', $t_primary_rep);
						
						$va_rep_display_info = caGetMediaDisplayInfo('detail', $t_primary_rep->getMediaInfo('media', 'original', 'MIMETYPE'));
						
						$this->view->setVar('primary_rep_display_version', $va_rep_display_info['display_version']);
						unset($va_display_info['display_version']);
						$va_rep_display_info['poster_frame_url'] = $t_primary_rep->getMediaUrl('media', $va_rep_display_info['poster_frame_version']);
						unset($va_display_info['poster_frame_version']);
						$this->view->setVar('primary_rep_display_options', $va_rep_display_info);
					}
				}
 			}
 					
 			 
 			#
 			# User-generated comments, tags and ratings
 			#
 			$va_user_comments = $t_item->getComments(null, true);
 			$va_comments = array();
 			if (is_array($va_user_comments)) {
				foreach($va_user_comments as $va_user_comment){
					$va_comment = array();
					if($va_user_comment["comment"]){
						$va_comment["comment"] = $va_user_comment["comment"];
						# TODO: format date based on local
						$va_comment["date"] = date("n/j/Y", $va_user_comment["created_on"]);
						$va_comment["created_on"] = $va_user_comment["created_on"];
						
						# -- get name of commenter
						$t_user = new ca_users($va_user_comment["user_id"]);
						$va_comment["author"] = $t_user->getName();
						
						$va_comment["email"] = $va_user_comment["email"];
						$va_comment["name"] = $va_user_comment["name"];
						$va_comments[] = $va_comment;
					}
				}
			}
 			$this->view->setVar('comments', $va_comments);
 			
 			$va_user_tags = $t_item->getTags(null, true);
 			$va_tags = array();
 			
 			if (is_array($va_user_tags)) {
				foreach($va_user_tags as $va_user_tag){
					if(!in_array($va_user_tag["tag"], $va_tags)){
						$va_tags[] = $va_user_tag["tag"];
					}
				}
			}
 			$this->view->setVar('tags_array', $va_tags);
 			$this->view->setVar('tags', implode(", ", $va_tags));
 			
 			# -- get average user ranking
 			$this->view->setVar('ranking', $t_item->getAverageRating(null));	// null makes it ignore moderation status
 			# -- get number of user rankings
 			$this->view->setVar('numRankings', $t_item->getNumRatings(null));	// null makes it ignore moderation status
 			

 			# --- get related objects - explicit relationships
 			$va_related_objects = array();
 			if (method_exists($this, 'getRelatedObjects')) {
 				$va_related_objects = $this->getRelatedObjects();
 			}
 			$this->view->setVar('related_objects', $va_related_objects);
 			
 			
 			#
 			# get suggested objects - you may be interested in
 			#
 			$va_suggested_objects = array();
 			if (method_exists($this, 'getSuggestedItems')) {
 				$va_suggested_objects = $this->getSuggestedItems();
 			}
 			$this->view->setVar('suggested_objects', $va_suggested_objects);
 			
 			
 			#
 			# Miscellaneous useful information
 			#
 			$this->view->setVar('t_relationship_types', new ca_relationship_types());					// relationship types object - used for displaying relationship type of related authority information
 			if (method_exists($t_item, 'getTypeName')) { $this->view->setVar('typename', $t_item->getTypeName()); }
 	 		
 	 		
 			# Hierarchy
//  			if($t_occurrence->get("parent_id")){
//  				$t_parent = new ca_occurrences($t_occurrence->get("parent_id"));
//  				$this->view->setVar('parent_title', $t_parent->getLabelForDisplay());
//  				$this->view->setVar('parent_id', $t_occurrence->get("parent_id"));
//  			}
 			
 		// 	$va_occurrence_children = $t_occurrence->getHierarchyChildren();
//  			if(is_array($va_occurrence_children) && (sizeof($va_occurrence_children) > 0)){
//  				$this->view->setVar('num_children', sizeof($va_occurrence_children));
//  			}


 			
 			// Record view
 			$t_item->registerItemView($this->request->getUserID());
 			
 			//
 			// Render view
 			//
 			if ($this->getView()->viewExists($this->ops_tablename.'_'.$t_item->getTypeCode().'_detail_html.php')) {
 				$this->render($this->ops_tablename.'_'.$t_item->getTypeCode().'_detail_html.php');
 			} else {
 				$this->render($this->ops_tablename.'_detail_html.php');
 			}
 		}
 		# -------------------------------------------------------
 		# Tagging and commenting
 		# -------------------------------------------------------
 		public function saveCommentRanking() {
 			if(!$t_item = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true)) {
 				die("Invalid table name ".$this->ops_tablename." for saving comment");
 			}

 			if(!($vn_item_id = $this->request->getParameter($t_item->primaryKey(), pInteger))){
  				$this->notification->addNotification(_t("Invalid ID"), "message");
 				$this->response->setRedirect(caNavUrl($this->request, "", "", "", ""));
 				return;
 			}
 			if(!$t_item->load($vn_item_id)){
  				$this->notification->addNotification(_t("ID does not exist"), "message");
 				$this->response->setRedirect(caNavUrl($this->request, "", "", "", ""));
 				return;
 			}
 			# --- get params from form
 			$ps_comment = $this->request->getParameter('comment', pString);
 			$pn_rank = $this->request->getParameter('rank', pInteger);
 			$ps_tags = $this->request->getParameter('tags', pString);
 			$ps_email = $this->request->getParameter('email', pString);
 			$ps_name = $this->request->getParameter('name', pString);
 			if($ps_comment || $pn_rank || $ps_tags){
 				if(!(($pn_rank > 0) && ($pn_rank <= 5))){
 					$pn_rank = null;
 				}
 				if($ps_comment || $pn_rank){
 					$t_item->addComment($ps_comment, $pn_rank, $this->request->getUserID(), null, $ps_name, $ps_email);
 				}
 				if($ps_tags){
 					$va_tags = array();
 					$va_tags = explode(",", $ps_tags);
 					foreach($va_tags as $vs_tag){
 						$t_item->addTag(trim($vs_tag), $this->request->getUserID());
 					}
 				}
 				if($ps_comment || $ps_tags){
 					$this->notification->addNotification(_t("Thank you for contributing.  Your comments will be posted on this page after review by site staff."), "message");
 				}else{
 					$this->notification->addNotification(_t("Thank you for your contribution."), "message");
 				}
 			}
 			
 			$this->Show();
 		}
 		# -------------------------------------------------------
 		# Detail-based browsing
 		# -------------------------------------------------------
 		public function getFacet() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			if ($this->request->getParameter('clear', pInteger)) {
 				$this->opo_browse->removeAllCriteria();
 				$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 				$this->request->session->setVar($this->ops_tablename.'_'.$this->ops_context.'_current_browse_id', $this->opo_browse->getBrowseID());
 			} else {
 				if ($this->request->getParameter('modify', pString)) {
 					$vm_id = $this->request->getParameter('id', pString);
 					$this->opo_browse->removeCriteria($ps_facet_name, array($vm_id));
 					$this->opo_browse->execute(array('checkAccess' => $va_access_values));
 					
 					$this->view->setVar('modify', $vm_id);
 				}
 			}
 			
 			$va_facet = $this->opo_browse->getFacet($ps_facet_name, array('sort' => 'name', 'checkAccess' => $va_access_values));
 			
 			$this->view->setVar('facet', $va_facet);
 			$this->view->setVar('facet_info', $va_facet_info = $this->opo_browse->getInfoForFacet($ps_facet_name));
 			$this->view->setVar('facet_name', $ps_facet_name);
 			$this->view->setVar('browse_id', $pn_browse_id);
 			$this->view->setVar('grouping', $vs_grouping = $this->request->getParameter('grouping', pString));

 			// this should be 'facet' but we don't want to render all old 'ajax_refine_facet_html' views (pawtucket themes) unusable
 			$this->view->setVar('grouped_facet',$this->opo_browse->getFacetWithGroups($ps_facet_name, $va_facet_info["group_mode"], $vs_grouping, array('sort' => 'name', 'checkAccess' => $va_access_values)));
 			
 			// generate type menu and type value list for related authority table facet
 			if ($va_facet_info['type'] === 'authority') {
				$t_model = $this->opo_datamodel->getTableInstance($va_facet_info['table']);
				if (method_exists($t_model, "getTypeList")) {
					$this->view->setVar('type_list', $t_model->getTypeList());
				}
				
				$t_rel_types = new ca_relationship_types();
				$this->view->setVar('relationship_type_list', $t_rel_types->getRelationshipInfo($va_facet_info['relationship_table']));
			}
			
			$t_table = $this->opo_datamodel->getTableInstance($this->ops_tablename);
			$this->view->setVar('other_parameters', array($t_table->primaryKey() => $this->request->getParameter($t_table->primaryKey(), pInteger)));
 			$this->render('../Browse/ajax_browse_facet_html.php');
 		}
 		# -------------------------------------------------------
 		public function addCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			$this->Show();
 		}
 		# -------------------------------------------------------
 		public function modifyCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('mod_id', pString)));
 			$this->opo_browse->addCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			$this->Show();
 		}
 		# -------------------------------------------------------
 		public function removeCriteria() {
 			$ps_facet_name = $this->request->getParameter('facet', pString);
 			$this->opo_browse->removeCriteria($ps_facet_name, array($this->request->getParameter('id', pString)));
 			$this->Show();
 		}
 		# -------------------------------------------------------
 		public function clearCriteria() {
 			$this->opo_browse->removeAllCriteria();
 			$this->Show();
 		}	
 		# -------------------------------------------------------
	}
?>