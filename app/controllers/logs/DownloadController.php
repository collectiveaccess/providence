<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/DownloadController.php :
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

 	require_once(__CA_LIB_DIR__.'/Logging/Downloadlog.php');

 	class DownloadController extends ActionController {
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function Index() {
 			AssetLoadManager::register('tableList');
 			$t_download_log = new Downloadlog();
 			
 			$va_download_list = array();
 			if (!($ps_search = $this->request->getParameter('search', pString))) {
 				$ps_search = $this->request->user->getVar('download_log_search');
 			} 
 			
 			if ($ps_search) {
 				$va_download_list = $t_download_log->search($ps_search);
 				$this->request->user->setVar('download_log_search', $ps_search);
 			}
 			if (!($ps_group_by = $this->request->getParameter('group_by', pString))) {
 				$ps_group_by = $this->request->user->getVar('download_group_by');
 			}else{
 				$this->request->user->setVar('download_group_by', $ps_group_by);
 			}
 			if(is_array($va_download_list) && sizeof($va_download_list)){
				# --- loop through all downloads and get the name of the records downloaded
				$va_row_id_by_table_num = array();
				$va_tables = array();
				$va_record_labels_by_table_num = array();
				foreach($va_download_list as $va_download){
					$va_row_id_by_table_num[$va_download["table_num"]][] = $va_download["row_id"];
				}
				foreach($va_row_id_by_table_num as $vn_table_num => $va_row_ids){
					$t_table = Datamodel::getInstanceByTableNum($vn_table_num, true);
					$va_tables[$vn_table_num]['name'] = Datamodel::getTableName($vn_table_num);
					$va_tables[$vn_table_num]['displayname'] = $t_table->getProperty('NAME_SINGULAR');
					$va_record_labels_by_table_num[$vn_table_num] = $t_table->getPreferredDisplayLabelsForIDs($va_row_ids);
				}
				$this->view->setVar('tables', $va_tables);
				$this->view->setVar('labels_by_table_num', $va_record_labels_by_table_num);
 				
				switch($ps_group_by){
					case "user":
				
					break;
					# ----------------------------
					case "record":
						$va_download_list_by_record = array();
						foreach($va_download_list as $va_download){
							$va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["info"] = $va_download;
							$va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["num_downloads"] = $va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["num_downloads"] + 1;
							if($va_download["user_id"]){
								$va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["num_logged_in_users"][$va_download["user_id"]] = $va_download["user_id"];
							}else{
								$va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["num_anon_users"] = $va_download_list_by_record[$va_download["table_num"]."-".$va_download["row_id"]]["num_anon_users"] + 1;
							}
						}
						$this->view->setVar('download_list', $va_download_list_by_record);
					break;
					# ----------------------------
					case "download":
					default:
						$this->view->setVar('download_list', $va_download_list);
					break;
					# ----------------------------
				}
			}
 			$this->view->setVar('download_list_search', $ps_search);
 			$this->view->setVar('download_list_group_by', $ps_group_by);
 			
 			$this->render('download_html.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>
