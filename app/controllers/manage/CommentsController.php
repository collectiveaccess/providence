<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/CommentsController.php : 
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
	require_once(__CA_MODELS_DIR__."/ca_item_comments.php");
 	require_once(__CA_LIB_DIR__."/ca/Search/ItemCommentSearch.php");
 	
 	class CommentsController extends BaseSearchController {
 		# -------------------------------------------------------
 		/**
 		 * Name of subject table (ex. for an object search this is 'ca_objects')
 		 */
 		protected $ops_tablename = 'ca_item_comments';
 		
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
				'ca_item_comments.created_on' => _t('date'),
				'ca_item_comments.user_id' => _t('user')
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
 			$pa_options['search'] = new ItemCommentSearch();
 			return parent::Index($pa_options);
 		}
 		# -------------------------------------------------------
 		public function ListUnmoderated() {
 			$t_comments = new ca_item_comments();
 			$this->view->setVar('t_comments', $t_comments);
 			$this->view->setVar('comments_list', $t_comments->getUnmoderatedComments());
 			if(sizeof($t_comments->getUnmoderatedComments()) == 0){
 				$this->notification->addNotification(_t("There are no unmoderated comments"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('comment_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Approve() {
 			$va_errors = array();
 			$pa_comment_ids = $this->request->getParameter('comment_id', pArray);
 			$ps_mode = $this->request->getParameter('mode', pString);
 			
 			if(is_array($pa_comment_ids) && (sizeof($pa_comment_ids) > 0)){
				foreach($pa_comment_ids as $vn_comment_id){
				
					$t_comment = new ca_item_comments($vn_comment_id);
					
					if (!$t_comment->getPrimaryKey()) {
						$va_errors[] = _t("The comment does not exist");	
						break;
					}
				
					if (!$t_comment->moderate($this->request->getUserID())) {
		 				$va_errors[] = _t("Could not approve comment");
						break;
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your comments have been approved"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select comments for approval"), __NOTIFICATION_TYPE_WARNING__);
			}
			switch($ps_mode){
 				case "list":
 					$this->ListUnmoderated();
 				break;
 				# -----------------------
 				case "search":
 					$this->Index();
 				break;
 				# -----------------------
 				case "dashboard":
 					$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
 				break;
 				# -----------------------
 			}
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			
 			$va_errors = array();
 			$pa_comment_ids = $this->request->getParameter('comment_id', pArray);
 			$ps_mode = $this->request->getParameter('mode', pString);
 			
 			if(is_array($pa_comment_ids) && (sizeof($pa_comment_ids) > 0)){
				foreach($pa_comment_ids as $vn_comment_id){
				
					$t_comment = new ca_item_comments($vn_comment_id);
					
					if (!$t_comment->getPrimaryKey()) {
						$va_errors[] = _t("The comment does not exist");	
						break;
					}
					$t_comment->setMode(ACCESS_WRITE);;
					if (!$t_comment->delete()) {
		 				$va_errors[] = _t("Could not delete comment");
						break;
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your comments have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select comments for deletion"), __NOTIFICATION_TYPE_WARNING__);
			}
			switch($ps_mode){
 				case "list":
 					$this->ListUnmoderated();
 				break;
 				# -----------------------
 				case "search":
 					$this->Index();
 				break;
 				# -----------------------
 				case "dashboard":
 					$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
 				break;
 				# -----------------------
 			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function searchName($ps_mode='singular') {
 			return ($ps_mode == 'singular') ? _t("comment") : _t("comments");
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of this controller (minus the "Controller" part)
 		 */
 		public function controllerName() {
 			return 'Comments';
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$o_dm = Datamodel::load();
 			
 			$t_comments = new ca_item_comments();
 			$this->view->setVar('unmoderated_comment_count', ($t_comments->getUnmoderatedCommentCount()));
 			$this->view->setVar('moderated_comment_count', ($t_comments->getModeratedCommentCount()));
 			$this->view->setVar('total_comment_count', ($t_comments->getCommentCount()));
 			
 			return $this->render('widget_comments_info_html.php', true);
 		}
 		# -------------------------------------------------------
 		public function DownloadMedia() {
 			$pn_comment_id = $this->request->getParameter('comment_id', pString);
 			$ps_field = $this->request->getParameter('field', pString);
 			if(!$ps_field || (!in_array($ps_field, array("media1", "media2", "media3", "media4")))){
 				$ps_field = "media1";
 			}
 			$ps_mode = $this->request->getParameter('mode', pString);
 			$ps_version = $this->request->getParameter('version', pString);
 			$t_item_comment = new ca_item_comments($pn_comment_id);
 			$va_versions = $t_item_comment->getMediaVersions($ps_field); 			
 			if (!in_array($ps_version, $va_versions)) { $ps_version = $va_versions[0]; }
 			
 			if(!$t_item_comment->getMediaTag($ps_field, $ps_version)){
 				# --- redirect based on mode
 				switch($ps_mode){
					case "list":
						$this->ListUnmoderated();
					break;
					# -----------------------
					case "search":
						$this->Index();
					break;
					# -----------------------
					case "dashboard":
						$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
					break;
					# -----------------------
				}
 			}else{
 				$this->view->setVar('version_path', $t_item_comment->getMediaPath($ps_field, $ps_version));
 				$va_info = $t_item_comment->getMediaInfo($ps_field);
 				$va_version_info = $t_item_comment->getMediaInfo($ps_field, $ps_version);
 				if ($va_info['ORIGINAL_FILENAME']) {
					if ($ps_version == 'original') {
						if (!preg_match('!'.$va_version_info['EXTENSION'].'$!i', $va_info['ORIGINAL_FILENAME'])) {
							$va_info['ORIGINAL_FILENAME'] .= '.'.$va_version_info['EXTENSION'];
						} 
						$this->view->setVar('version_download_name', $va_info['ORIGINAL_FILENAME']);
					} else {
						$va_tmp = explode('.', $va_info['ORIGINAL_FILENAME']);
						if (sizeof($va_tmp) > 1) { array_pop($va_tmp); }
						$this->view->setVar('version_download_name', join('_', $va_tmp).'.'.$va_version_info['EXTENSION']);
					}
				} else {
					$this->view->setVar('version_download_name', 'comment_media_'.$pn_comment_id.'_'.$ps_version.'.'.$va_version_info['EXTENSION']);
				}
 				return $this->render('comment_download_binary.php');
 			} 			
 		}
 		# -------------------------------------------------------
 	}
 ?>