<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/WatchedItemsController.php : 
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
 	require_once(__CA_MODELS_DIR__."/ca_watch_list.php");
 	
 	class WatchedItemsController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		 
 			JavascriptLoadManager::register('tableList');
 		}
 		# -------------------------------------------------------
 		public function ListItems() {
 			$t_watch_list = new ca_watch_list();
			$va_watched_items = $t_watch_list->getWatchedItems($this->request->user->get("user_id"));
			$this->view->setVar("watched_items", $va_watched_items);
 			if(sizeof($va_watched_items) == 0){
 				$this->notification->addNotification(_t("There are no watched items"), __NOTIFICATION_TYPE_INFO__);
 			}
 			$this->render('watched_items_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Delete() {
 			$ps_mode = $this->request->getParameter('mode', pString);
 			$va_errors = array();
 			$pa_watch_ids = $this->request->getParameter('watch_id', pArray);
 			$va_errors = array();
 			if(is_array($pa_watch_ids) && (sizeof($pa_watch_ids) > 0)){
				$t_watch_list = new ca_watch_list();
				foreach($pa_watch_ids as $vn_watch_id){
					if($t_watch_list->load(array('watch_id' => $vn_watch_id))){
						$t_watch_list->setMode(ACCESS_WRITE);
						$t_watch_list->delete();
						if ($t_watch_list->numErrors()) {
							$va_errors = $t_item->errors;
						}
					}
				
					
				
				}
				if(sizeof($va_errors) > 0){
					$this->notification->addNotification(implode("; ", $va_errors), __NOTIFICATION_TYPE_ERROR__);
				}else{
					$this->notification->addNotification(_t("Your watched items have been deleted"), __NOTIFICATION_TYPE_INFO__);
				}
			}else{
				$this->notification->addNotification(_t("Please use the checkboxes to select items to remove from your watch list"), __NOTIFICATION_TYPE_WARNING__);
			}
 			if($ps_mode == "dashboard"){
 				$this->response->setRedirect(caNavUrl($this->request, "", "Dashboard", "Index"));
 			}else{
 				$this->ListItems();
 			}
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			return $this->render('widget_watched_items_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}
 ?>