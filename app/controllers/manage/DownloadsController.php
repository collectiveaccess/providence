<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/DownloadsController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 	
class DownloadsController extends ActionController {
	# -------------------------------------------------------
	public function List() {
		AssetLoadManager::register('tableList');
		
		$t_download = new ca_user_export_downloads();
		$this->view->setVar('t_download', $t_download);
		$this->view->setVar('download_list', $t_download->getDownloads(['user_id' => $this->request->getUserID()]));
		
		$this->render('download_list_html.php');
	}
	# -------------------------------------------------------
	public function Download() {
		$download_id = $this->request->getParameter('download_id', pInteger);
		$t_download = ca_user_export_downloads::findAsInstance(['download_id' => $download_id, 'user_id' => $this->request->getUserID()]);
		if($t_download) {
			$md = $t_download->get('ca_user_export_downloads.metadata');
			$this->view->setVar('t_download', $t_download);
			$this->view->setVar('file_path', $file_path = $t_download->getFilePath('export_file'));
			$this->view->setVar('download_name', preg_replace("![^A-Za-z0-9_\-\.]+!", '', $md['searchExpressionForDisplay']).'.'.$md['extension']);
			
			$t_download->set([
				'downloaded_on' => _t('now'),
				'status' => 'DOWNLOADED'
			]);
			$t_download->update();
		}
		$this->render('download_export_binary.php');
	}
	# -------------------------------------------------------
	public function Delete() {
		$ids_to_delete = $this->request->getParameter('delete_id', pArray);
		$downloaded = $this->request->getParameter('downloadedOnly', pInteger);
	
		$delete_count = $failed_deletes = 0;
		if($downloaded) {
			if(is_array($downloads_to_delete =  ca_user_export_downloads::find(['user_id' => $this->request->getUserID(), 'downloaded_on' => ['>', 0]], ['returnAs' => 'modelInstances']))) {
				foreach($downloads_to_delete as $t_download) {
					if($t_download->delete(true)) {
						$delete_count++;
					} else {
						$failed_deletes++;
					}
				}
			}
		} elseif(is_array($ids_to_delete)) {
			foreach($ids_to_delete as $download_id) {
				if($t_download = ca_user_export_downloads::findAsInstance(['download_id' => $download_id, 'user_id' => $this->request->getUserID()])) {
					if($t_download->delete(true)) {
						$delete_count++;
					} else {
						$failed_deletes++;
					}
				}
			}
		}
		
		if($delete_count) {
			$this->notification->addNotification($delete_count == 1 ? _t("Deleted %1 download", $delete_count) : _t("Deleted %1 downloads", $delete_count), __NOTIFICATION_TYPE_INFO__);
		}
		if($failed_deletes) {
			$this->notification->addNotification($delete_count == 1 ? _t("Could not delete %1 download", $failed_deletes) : _t("Could not delete %1 downloads", $failed_deletes), __NOTIFICATION_TYPE_ERROR__);
		}
		
		$this->List();
	}
	# -------------------------------------------------------
	/**
	 * 
	 */
	public function Info() {
		$t_download = new ca_user_export_downloads();
		$this->view->setVar('download_count', ca_user_export_downloads::getDownloadCount(['generatedOnly' => true, 'user_id' => $this->request->getUserID()]));
		
		
		return $this->render('widget_download_info_html.php', true);
	}
	# -------------------------------------------------------
}
