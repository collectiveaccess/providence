<?php
/* ----------------------------------------------------------------------
 * trackProcessingWidget.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BaseWidget.php');
require_once(__CA_LIB_DIR__.'/IWidget.php');

class trackProcessingWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $config;
	private $db;
	# -------------------------------------------------------
	/**
	 * @var int
	 */
	private $limit;

	public function __construct($ps_widget_path, $pa_settings) {
		$this->title = _t('Processing status');
		$this->description = _t('View the current status of queued processing tasks');
		parent::__construct($ps_widget_path, $pa_settings);
		
		$this->config = Configuration::load($ps_widget_path.'/conf/trackProcessing.conf');
		$this->db = new Db();
		
		$this->getLimit($pa_settings);
		
		AssetLoadManager::register('prettyDate');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		$available = ((bool)$this->config->get('enabled'));

		if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_track_processing_widget")){
			$available = false;
		}

		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => $available,
		);
	}
	# -------------------------------------------------------
	private function getLimit($settings) {
		$this->limit = $settings['display_limit'] ?? ((int)$this->config->getScalar('display_limit') ?: 100);
		if($this->limit <= 0) { $this->limit = 100; }
		return $this->limit;
	}
	# -------------------------------------------------------
	public function renderWidget($ps_widget_id, &$pa_settings) {
		parent::renderWidget($ps_widget_id, $pa_settings);
		$this->opo_view->setVar('request', $this->getRequest());
		$this->opo_view->setVar('hours', $pa_settings['hours']);
		
		$this->getLimit($pa_settings);
		
		$vo_tq = new TaskQueue();
		$qr_completed = $this->db->query("
			SELECT tq.task_id, tq.user_id, tq.row_key, tq.created_on, tq.started_on, tq.completed_on,
			tq.priority, tq.handler, tq.error_code, tq.parameters, tq.notes, u.fname, u.lname 
			FROM ca_task_queue tq 
			LEFT JOIN ca_users u ON u.user_id = tq.user_id 
			WHERE tq.completed_on > ? 
			ORDER BY tq.completed_on desc
		", time() - (60*60*$pa_settings['hours']));

		$completed = [];
		$vn_reported = 0;
		while($qr_completed->nextRow() && $vn_reported < $this->limit){
			$row = $qr_completed->getRow();
			$created_on_display = caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp($row['created_on']));
			
			$completed[$row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($row['handler']);
			$completed[$row["task_id"]]["created"] = _t('%1 by %2', $created_on_display, caFormatPersonName( $row["fname"], $row['lname'], _t('Command line or job')));
			$completed[$row["task_id"]]["completed_on"] = $row["completed_on"];
			$completed[$row["task_id"]]["error_code"] = $row["error_code"];
			
			if ((int)$row["error_code"] > 0) {
				$o_e = new ApplicationError((int)$row["error_code"], '', '', '', false, false);
				$row["error_message"] = $o_e->getErrorMessage();
			} else {
				$row["error_message"] = '';
			}
			$completed[$row["task_id"]]["error_message"] = $row["error_message"];
			if (is_array($report = caUnserializeForDatabase($row["notes"]))) {
				$completed[$row["task_id"]]["processing_time"] = (float)$report['processing_time'];
			}
			
			
			$completed[$row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($row);
			$vn_reported ++;
		}
		$this->opo_view->setVar('count_jobs_done', $qr_completed->numRows());
		$this->opo_view->setVar('additional_jobs_done', max($qr_completed->numRows() - $this->limit, 0));
		$this->opo_view->setVar('data_jobs_done', $completed);

		$qr_qd = $this->db->query("
			SELECT tq.task_id, tq.user_id, tq.row_key, tq.created_on, tq.started_on, tq.completed_on,
			tq.priority, tq.handler, tq.error_code, tq.parameters, tq.notes, u.fname, u.lname 
			FROM ca_task_queue tq
			LEFT JOIN ca_users AS u ON tq.user_id = u.user_id
			WHERE tq.completed_on is NULL
		");
		
		$qd_jobs = $pr_jobs = $stuck_jobs = [];
		$vn_reported = 0;
		while($qr_qd->nextRow() && $vn_reported < $this->limit){
			$row = $qr_qd->getRow();
			$created_on_display = caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp($row['created_on']));

			if(!$vo_tq->rowKeyIsBeingProcessed($row["row_key"])){
				if(!$row["completed_on"] && ($row["started_on"] > 0)) {
					$stuck_jobs[$row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($row['handler']);
					$stuck_jobs[$row["task_id"]]["created"] = _t('%1 by %2', $created_on_display, caFormatPersonName( $row["fname"], $row['lname'], _t('Command line or job')));
					$stuck_jobs[$row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($row);
				} else {
					$qd_jobs[$row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($row['handler']);
					$qd_jobs[$row["task_id"]]["created"] = _t('%1 by %2', $created_on_display, caFormatPersonName( $row["fname"], $row['lname'], _t('Command line or job')));
					$qd_jobs[$row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($row);
				}
			} else {
				$pr_jobs[$row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($row['handler']);
				$pr_jobs[$row["task_id"]]["created"] = _t('%1 by %2', $created_on_display, caFormatPersonName( $row["fname"], $row['lname'], _t('Command line or job')));
				$pr_jobs[$row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($row);
			}
			$vn_reported ++;
		}
		
		$this->opo_view->setVar('count_jobs_queued',sizeof($qd_jobs));
		$this->opo_view->setVar('count_jobs_processing',sizeof($pr_jobs));
		$this->opo_view->setVar('count_jobs_stuck',sizeof($stuck_jobs));
		$this->opo_view->setVar('data_jobs_queued',$qd_jobs);
		$this->opo_view->setVar('data_jobs_processing',$pr_jobs);
		$this->opo_view->setVar('data_jobs_stuck',$stuck_jobs);
		$this->opo_view->setVar('additional_jobs_queued', max(sizeof($qd_jobs) - $this->limit, 0));
		$this->opo_view->setVar('additional_jobs_processing', max(sizeof($pr_jobs) - $this->limit, 0));
		$this->opo_view->setVar('additional_jobs_stuck', max(sizeof($stuck_jobs) - $this->limit, 0));
		
		$vn_freq = (int)($pa_settings['refresh_interval'] ?? 60);
		$this->opo_view->setVar('update_frequency', ($vn_freq > 0) ? $vn_freq : 60);
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
	/**
	 * Returns a string to render status information in html.
	 *
	 * @param $status
	 * @param $view
	 */
	public static function getStatusForDisplay($status, $view){
		$result = "";
		foreach($status as $code => $info) {
			if(!($info['value'] ?? null)) { continue; }
			switch($code) {
				case 'table':
					$tmp = explode(':', $status['table']['value']);
					if ($link = caEditorLink($view->request, caGetTableDisplayName($tmp[0], true).' ➜ '.join(' ➜ ', array_slice($tmp, 1)), 'link', $tmp[0], $tmp[2], array(), array(), array('verifyLink' => true))) {
						$result .= "<strong>".$info['label']."</strong>: ".$link."<br/>\n";
					} else {
						$result .=  "<strong>".$info['label']."</strong>: ".$info['value']." [<em>"._t('DELETED')."</em>]<br/>\n";
					}
					break;
				default:
					$result .= "<strong>".$info['label']."</strong>: ".$info['value']."<br/>\n";
					break;
			}
		}

		return $result;
	}
	# -------------------------------------------------------
	/**
	 * Retry incomplete job
	 */
	public function methodRetryJob(string $widget_id, ?array $options) : string  {
		$resp = [];
		$ret = null;
		
		$o_tq = new TaskQueue();
		if($task_id = caGetOption('task_id', $options, null)) {
			$ret = $o_tq->resetIncompleteTasks([$task_id]);	
		}	
		$resp['OK'] = $ret ? 1 : 0;
		return json_encode($resp);	
	}
	# -------------------------------------------------------
}

BaseWidget::$s_widget_settings['trackProcessingWidget'] = array(
	'hours' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 6, 'height' => 1,
		'takesLocale' => false,
		'default' => 72,
		'label' => _t('Show jobs completed less than ^ELEMENT hours ago'),
		'description' => _t('Threshold (in hours) to display completed jobs')
	),
	'display_limit' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 6, 'height' => 1,
		'takesLocale' => false,
		'default' => 50,
		'label' => _t('Show up to ^ELEMENT jobs per category'),
		'description' => _t('Maximum number of jobs to display per category')
	),
	'refresh_interval' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_FIELD,
		'width' => 6, 'height' => 1,
		'takesLocale' => false,
		'default' => 60,
		'label' => _t('Refresh display every ^ELEMENT seconds'),
		'description' => _t('Frequency (in seconds) to refresh display')
	)
);
