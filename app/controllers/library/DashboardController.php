<?php
/* ----------------------------------------------------------------------
 * app/controllers/library/DashboardController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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

 	require_once(__CA_APP_DIR__.'/helpers/libraryServicesHelpers.php');
	require_once(__CA_LIB_DIR__.'/Search/ObjectCheckoutSearch.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_checkouts.php');
	require_once(__CA_LIB_DIR__.'/ResultContext.php');

 	class DashboardController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		private $opo_library_services_config;
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			$this->opo_library_services_config = caGetLibraryServicesConfiguration();
 			
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_do_library_checkout') || !$this->request->config->get('enable_library_services')  || !$this->request->config->get('enable_object_checkout')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function Index() {
 			AssetLoadManager::register("jquery", "scrollto");
 			if (!($ps_daterange = $this->request->getParameter('daterange', pString))) { $ps_daterange = _t('today'); }
 			
			$va_dashboard_config = $this->opo_library_services_config->getAssoc('dashboard');
 			
 			$this->view->setVar('stats', $va_stats = ca_object_checkouts::getDashboardStatistics($ps_daterange));
 			$this->view->setVar('daterange', $ps_daterange);
 			
 			// process user lists
 			
 			foreach(array(
 				'checkoutUserList' => 'checkout_user_list',
 				'checkinUserList' => 'checkin_user_list',
 				'overdueCheckoutUserList' => 'overdue_checkout_user_list',
 				'reservationUserList' => 'reservation_user_list',
 			) as $vs_stat_key => $vs_var_name) {
 				$va_user_list = array();
				$vn_c = 0;
				foreach($va_stats[$vs_stat_key] as $va_user) {
					$va_user_list[] = "<a href='#' class='caLibraryUserLink' data-user_id='".$va_user['user_id']."'>".trim($va_user['fname'].' '.$va_user['lname'])."</a>"; //.($va_user['email'] ? ' ('.$va_user['email'].')' : '');
					$vn_c++;
					if ($vn_c >= 100) {
						$va_user_list[] = _t(' and %1 more', sizeof($va_stats[$vs_stat_key]) - $vn_c);
						break;
					}
				}
				$this->view->setVar($vs_var_name, $va_user_list);
			}
			
			$this->view->setVar('panels', $va_panels = is_array($va_dashboard_config['panels']) ? $va_dashboard_config['panels'] : array());
			
			//
			// Gather data for configurable stat panels. 
			//
			// These panels break counts of checkins, checkouts or reservations by an object metadata element.
			// For example: # of checkouts by item format
			//
			foreach($va_panels as $vs_panel => $va_panel_info) {
				if (!($va_group_bys = $va_panel_info['group_by'])) { continue;}
				if (!is_array($va_group_bys)) { $va_group_bys = array($va_group_bys); }
				
				$va_group_by_elements = array();
				foreach($va_group_bys as $vn_i => $vs_group_by) {
					$va_tmp = explode('.', $vs_group_by);
					$va_group_by_elements[$vn_i] = array_pop($va_tmp);
				}
				
				$va_counts = array();
				
				switch(strtolower($va_panel_info['event'])) {
					case 'checkin':
						$va_object_ids = ca_object_checkouts::getObjectIDsForCheckins($ps_daterange);
						break;
					case 'reserve':
						$va_object_ids = ca_object_checkouts::getObjectIDsForReservations();
						break;
					case 'checkout':
					default:
						$va_object_ids = ca_object_checkouts::getObjectIDsForOutstandingCheckouts($ps_daterange);
						break;
				}
				if(sizeof($va_object_ids) == 0) { continue; }
				
				$qr_objects = caMakeSearchResult('ca_objects', $va_object_ids);
				while($qr_objects->nextHit()) {
					foreach($va_group_bys as $vn_i => $vs_group_by) {
						if (is_array($va_attrs = $qr_objects->get($vs_group_by, array( 'returnWithStructure' => true, 'convertCodesToDisplayText' => true)))) {
							if (!sizeof($va_attrs)) { $va_count['?']++; break; }
							foreach($va_attrs as $vn_attr_id => $va_vals) {
							    if(is_array($va_vals)) {
                                    foreach($va_vals as $vn_val_id => $va_val) {
                                        $va_counts[$va_val[$va_group_by_elements[$vn_i]]]++;
                                    }
                                } else {
                                    $va_counts[$va_vals]++;
                                }
							}
							
							break;
						} else {
							$va_count['?']++;
						}
					}
				}
				$this->view->setVar("panel_{$vs_panel}", $va_counts);
			}
			
 			
 			$this->render('dashboard/index_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
 		public function getUserDetail() {
 			$pn_user_id = $this->request->getParameter('user_id', pInteger);
 			$ps_daterange = $this->request->getParameter('daterange', pString);
 			$t_user = new ca_users($pn_user_id);
 			 
 			$this->view->setVar('t_user', $t_user);
 			if ($t_user->getPrimaryKey()) {	
 				$this->view->setVar('name', trim($t_user->get('fname').' '.$t_user->get('lname')));
 			
 				$vs_item_display_template = "<unit relativeTo=\"ca_objects\"><l>^ca_objects.preferred_labels.name</l> (^ca_objects.idno)</unit>";
 			
				// Get checkouts 
				$this->view->setVar('checkouts', ca_object_checkouts::getOutstandingCheckoutsForUser($pn_user_id, $vs_item_display_template, $ps_daterange, ['omitOverdue' => true]));
			
				// Get checkins 
				$this->view->setVar('checkins', ca_object_checkouts::getCheckinsForUser($pn_user_id, $vs_item_display_template, $ps_daterange));
			
				// Get overdue
				$this->view->setVar('overdue_checkouts', ca_object_checkouts::getOverdueCheckoutsForUser($pn_user_id, $vs_item_display_template, $ps_daterange));
			
				// Get reservations
				$this->view->setVar('reservations', ca_object_checkouts::getOutstandingReservationsForUser($pn_user_id, $vs_item_display_template));
			
			} else {
				$this->view->setVar('name', "???");
			}
 			
 			
 			$this->render('dashboard/user_detail_html.php');
 		}
 		# -------------------------------------------------------
 	}
