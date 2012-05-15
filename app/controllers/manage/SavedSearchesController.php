<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SavedSearchesController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__."/core/Controller/ActionController.php");
 	
 	class SavedSearchesController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		 
 			JavascriptLoadManager::register('tableList');
 		}
 		# -------------------------------------------------------
 		public function ListSearches() {
 			$va_tables = array("ca_objects", "ca_entities", "ca_places", "ca_object_lots", "ca_storage_locations", "ca_collections", "ca_occurrences");
			$va_searches = array();
			$vn_c = 0;
			foreach($va_tables as $vs_table){
				if(sizeof($va_saved_search_list = $this->request->user->getSavedSearches($vs_table, "basic_search")) > 0){
					$va_searches[$vs_table]["basic_search"] = $va_saved_search_list;
					$vn_c += sizeof($va_saved_search_list);
				}
				if(sizeof($va_saved_search_list = $this->request->user->getSavedSearches($vs_table, "advanced_search")) > 0){
					$va_searches[$vs_table]["advanced_search"] = $va_saved_search_list;
					$vn_c += sizeof($va_saved_search_list);
				}
			}
			$this->view->setVar("saved_searches", $va_searches);
			$this->request->session->setVar('saved_search_count', $vn_c);
			if(sizeof($va_searches) == 0){
 				$this->notification->addNotification(_t("There are no saved searches"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('saved_searches_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			
 			$va_errors = array();
 			$pa_saved_search_ids = $this->request->getParameter('saved_search_id', pArray);
 			$va_errors = array();
 			if(is_array($pa_saved_search_ids) && (sizeof($pa_saved_search_ids) > 0)){
				foreach($pa_saved_search_ids as $vn_search_id){
					$va_search_pieces = array();
					$va_search_pieces = explode("-", $vn_search_id);
					if(!$this->request->user->removeSavedSearch($va_search_pieces[0], $va_search_pieces[1], $va_search_pieces[2])){
						$va_errors[] = _t("Your search could not be deleted");
					}
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your searches have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select searches for deletion"), __NOTIFICATION_TYPE_WARNING__);
			}
 			
 			$this->ListSearches();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$this->view->setVar('search_count', $this->request->session->getVar('saved_search_count'));
 			return $this->render('widget_saved_searches_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>