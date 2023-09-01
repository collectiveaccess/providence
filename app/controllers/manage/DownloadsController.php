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
