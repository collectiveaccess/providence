<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/TagsController.php : 
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
 	require_once(__CA_LIB_DIR__."/ca/BaseSearchController.php");
	require_once(__CA_MODELS_DIR__."/ca_item_tags.php");
	require_once(__CA_MODELS_DIR__."/ca_items_x_tags.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ItemTagSearch.php");
 	
 	class TagsController extends BaseSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_item_tags';
 		
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
 		 * List of available search-result sorting fields
 		 * Is associative array: values are display names for fields, keys are full fields names (table.field) to be used as sort
 		 */
 		protected $opa_sorts;
 		
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		 	
 		 	$this->opa_views = array(
				'list' => _t('list')
			 );
			 
			 $this->opa_sorts = array(
				'ca_items_x_tags.created_on' => _t('date'),
				'ca_items_x_tags.user_id' => _t('user')
			);
			 
 			JavascriptLoadManager::register('tableList');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Search handler (returns search form and results, if any)
 		 * Most logic is contained in the BaseSearchController->Index() method; all you usually
 		 * need to do here is instantiate a new subject-appropriate subclass of BaseSearch 
 		 * (eg. CollectionSearch for collections, EntitySearch for entities) and pass it to BaseSearchController->Index() 
 		 */ 
 		public function Index($pa_options=null) {
 			$pa_options['search'] = new ItemTagSearch();
 			return parent::Index($pa_options);
 		}
 		# -------------------------------------------------------
 		public function ListUnmoderated() {
 			$t_tag = new ca_item_tags();
 			$this->view->setVar('tags_list', $t_tag->getUnmoderatedTags());
 			if(sizeof($t_tag->getUnmoderatedTags()) == 0){
 				$this->notification->addNotification(_t("There are no unmoderated tags"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('tag_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Approve() {
 			
 			$va_errors = array();
 			$pa_tag_relation_ids = $this->request->getParameter('tag_relation_id', pArray);
 			$ps_mode = $this->request->getParameter('mode', pString);
 			
 			if(is_array($pa_tag_relation_ids) && (sizeof($pa_tag_relation_ids) > 0)){
				foreach($pa_tag_relation_ids as $vn_relation_id){
				
					$t_item_x_tag = new ca_items_x_tags($vn_relation_id);
					
					if (!$t_item_x_tag->getPrimaryKey()) {
						$va_errors[] = _t("The tag does not exist");	
						break;
					}
				
					if (!$t_item_x_tag->moderate($this->request->getUserID())) {
		 				$va_errors[] = _t("Could not approve tag");
						break;
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your tags have been approved"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select tags for approval"), __NOTIFICATION_TYPE_WARNING__);
			}
 			switch($ps_mode){
 				case "dashboard":
 					$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
 				break;
 				# -----------------------
 				default:
 					$this->ListUnmoderated();
 				break;
 				# -----------------------
 			}
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			
 			$va_errors = array();
 			$pa_tag_relation_ids = $this->request->getParameter('tag_relation_id', pArray);
 			$ps_mode = $this->request->getParameter('mode', pString);
 			
 			if(is_array($pa_tag_relation_ids) && (sizeof($pa_tag_relation_ids) > 0)){
				foreach($pa_tag_relation_ids as $vn_relation_id){
				
					$t_item_x_tag = new ca_items_x_tags($vn_relation_id);
					
					if (!$t_item_x_tag->getPrimaryKey()) {
						$va_errors[] = _t("The tag does not exist");	
						break;
					}
					$t_item_x_tag->setMode(ACCESS_WRITE);;
					if (!$t_item_x_tag->delete()) {
		 				$va_errors[] = _t("Could not delete tag");
						break;
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your tags have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select tags for deletion"), __NOTIFICATION_TYPE_WARNING__);
			}
 			
 			switch($ps_mode){
 				case "dashboard":
 					$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
 				break;
 				# -----------------------
 				default:
 					$this->ListUnmoderated();
 				break;
 				# -----------------------
 			}
 		}
 		# -------------------------------------------------------
 		public function DeleteTags() {
 			
 			$va_errors = array();
 			$pa_tag_ids = $this->request->getParameter('tag_id', pArray);
 			
 			if(is_array($pa_tag_ids) && (sizeof($pa_tag_ids) > 0)){
				foreach($pa_tag_ids as $vn_tag_id){
				
					$t_item_tags = new ca_item_tags($vn_tag_id);
					
					if (!$t_item_tags->getPrimaryKey()) {
						$va_errors[] = _t("The tag does not exist");	
						break;
					}
					$t_item_tags->setMode(ACCESS_WRITE);;
					if (!$t_item_tags->delete(1)) {
		 				$va_errors[] = _t("Could not delete tag");
						break;
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your tags have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select tags for deletion"), __NOTIFICATION_TYPE_WARNING__);
			}
 			
 			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("tag") : _t("tags");
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of this controller (minus the "Controller" part)
 		 */
 		public function controllerName() {
 			return 'Tags';
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$t_tag = new ca_item_tags();
 			$this->view->setVar('unmoderated_tag_count', ($t_tag->getUnmoderatedTagCount()));
 			$this->view->setVar('moderated_tag_count', ($t_tag->getModeratedTagCount()));
 			$this->view->setVar('total_taggings_count', ($t_tag->getTagCount()));
 			$this->view->setVar('total_tag_count', ($t_tag->getItemTagsCount()));
 			
 			return $this->render('widget_tags_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>