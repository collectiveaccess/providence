<?php
/* ----------------------------------------------------------------------
 * trackProcessingWidget.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/ca/IWidget.php');
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/Db.php');
	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 
	class trackProcessingWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_db;
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Processing status');
			$this->description = _t('View the current status of queued processing tasks');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/trackProcessing.conf');
			$this->opo_db = new Db();
			
			JavascriptLoadManager::register('prettyDate');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_track_processing_widget")){
				$vb_available = false;
			}

			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => $vb_available,
			);
		}
		# -------------------------------------------------------
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);
			$this->opo_view->setVar('request', $this->getRequest());
			$this->opo_view->setVar('hours', $pa_settings['hours']);
			
			$vo_tq = new TaskQueue();
			$qr_completed = $this->opo_db->query("
				SELECT tq.*, u.fname, u.lname 
				FROM ca_task_queue tq 
				LEFT JOIN ca_users u ON u.user_id = tq.user_id 
				WHERE tq.completed_on > ? 
				ORDER BY tq.completed_on desc
			", time() - (60*60*$pa_settings['hours']));
			$va_completed = array();
			while($qr_completed->nextRow()){
				$va_row = $qr_completed->getRow();
				$va_params = caUnserializeForDatabase($va_row["parameters"]);
				$va_completed[$va_row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($va_row['handler']);
				$va_completed[$va_row["task_id"]]["created"] = $va_row["created_on"];
				$va_completed[$va_row["task_id"]]["by"] = $va_row["fname"].' '.$va_row['lname'];
				$va_completed[$va_row["task_id"]]["completed_on"] = $va_row["completed_on"];
				$va_completed[$va_row["task_id"]]["error_code"] = $va_row["error_code"];
				
				if ((int)$va_row["error_code"] > 0) {
					$o_e = new Error((int)$va_row["error_code"], '', '', '', false, false);
					$va_row["error_message"] = $o_e->getErrorMessage();
				} else {
					$va_row["error_message"] = '';
				}
				$va_completed[$va_row["task_id"]]["error_message"] = $va_row["error_message"];
				
				if (is_array($va_report = caUnserializeForDatabase($va_row["notes"]))) {
					$va_completed[$va_row["task_id"]]["processing_time"] = (float)$va_report['processing_time'];
				}
				
				
				$va_completed[$va_row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($va_row);
			}
			$this->opo_view->setVar('jobs_done',$qr_completed->numRows());
			$this->opo_view->setVar('jobs_done_data',$va_completed);

			$qr_qd = $this->opo_db->query("
				SELECT * 
				FROM ca_task_queue tq
				LEFT JOIN ca_users AS u ON tq.user_id = u.user_id
				WHERE tq.completed_on is NULL
			");
			$this->opo_view->setVar('jobs_queued_processing',$qr_qd->numRows());
			$va_qd_jobs = array();
			$va_pr_jobs = array();
			while($qr_qd->nextRow()){
				$va_row = $qr_qd->getRow();
				$va_params = caUnserializeForDatabase($va_row["parameters"]);
				
				if(!$vo_tq->rowKeyIsBeingProcessed($va_row["row_key"])){
					$va_qd_jobs[$va_row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($va_row['handler']);
					$va_qd_jobs[$va_row["task_id"]]["created"] = $va_row["created_on"];
					$va_qd_jobs[$va_row["task_id"]]["by"] = $va_row["fname"].' '.$va_row['lname'];
					$va_qd_jobs[$va_row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($va_row);
				} else {
					$va_pr_jobs[$va_row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($va_row['handler']);
					$va_pr_jobs[$va_row["task_id"]]["created"] = $va_row["created_on"];
					$va_pr_jobs[$va_row["task_id"]]["by"] = $va_row["fname"].' '.$va_row['lname'];
					$va_pr_jobs[$va_row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($va_row);
				}
			}
			$this->opo_view->setVar('qd_job_data',$va_qd_jobs);
			$this->opo_view->setVar('pr_job_data',$va_pr_jobs);
			
			$this->opo_view->setVar('update_frequency', ($vn_freq = (int)$this->opo_config->get('update_frequency')) ? $vn_freq : 60);

			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_trackProcessing'] = array(
				'label' => _t('Track processing widget'),
				'description' => _t('Actions for track processing widget'),
				'actions' => trackProcessingWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_track_processing_widget' => array(
					'label' => _t('Can use track processing widget'),
					'description' => _t('User can use dashboard widget that lists processing jobs.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	BaseWidget::$s_widget_settings['trackProcessingWidget'] = array(
		'hours' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 6, 'height' => 1,
			'takesLocale' => false,
			'default' => '72',
			'label' => _t('Show jobs completed less than ^ELEMENT hours ago'),
			'description' => _t('Threshold (in hours) to display completed jobs')
		)
	);
?>