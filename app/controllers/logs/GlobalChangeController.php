<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/GlobalChangeController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2021 Whirl-i-Gig
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

require_once(__CA_MODELS_DIR__.'/ca_change_log.php');

class GlobalChangeController extends ActionController {
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	/**
	 *
	 */
	private static $log_entries_per_page = 25;
	
	# -------------------------------------------------------
	public function Index() {
		if(!$this->request->getUser()->canDoAction('can_view_my_change_logs') && !$this->request->getUser()->canDoAction('can_view_change_logs')) { 
			$this->response->setRedirect(
				$this->request->getAppConfig()->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath())
			);
			return;
		}

		AssetLoadManager::register('tableList');

		$this->view->setVar('table_list', $table_list = caGetPrimaryTablesForHTMLSelect());
		$filter_table = $this->request->getParameter('filter_table', pInteger);
		if (!$filter_table && !isset($_REQUEST['filter_table'])) { $filter_table = Session::getVar('global_change_log_filter_table'); }
		Session::setVar('global_change_log_filter_table', $filter_table);
		$this->view->setVar('filter_table', $filter_table);
		
		$filter_change_type = $this->request->getParameter('filter_change_type', pString);
		if (!$filter_change_type && !isset($_REQUEST['filter_change_type'])) { $filter_change_type = Session::getVar('global_change_log_filter_change_type'); }
		Session::setVar('global_change_log_filter_change_type', $filter_change_type);
		$this->view->setVar('filter_change_type', $filter_change_type);
		
		$filter_daterange = $this->request->getParameter('filter_daterange', pString);
		if (!$filter_daterange && !isset($_REQUEST['filter_daterange'])) { $filter_daterange = Session::getVar('global_change_log_filter_daterange'); }
		Session::setVar('global_change_log_filter_daterange', $filter_daterange);
		$this->view->setVar('filter_daterange', $filter_daterange);
		
		$this->view->setVar('user_list', $user_list = ApplicationChangeLog::getChangeLogUsersForSelect(['daterange' => $filter_daterange]));
		
		if($can_filter_by_user = $this->request->user->canDoAction('can_view_change_logs')) {
			$filter_user_id = $this->request->getParameter('filter_user', pInteger);
			if (!$filter_user_id && !isset($_REQUEST['filter_user'])) { 
				$filter_user_id = Session::varExists('global_change_log_filter_user_id') ? Session::getVar('global_change_log_filter_user_id') : null; 
			}
			if (!in_array($filter_user_id, $user_list)) { $filter_user_id = null; }
		} else {
			$filter_user_id = $this->request->getUserID();
		}
		Session::setVar('global_change_log_filter_user_id', $filter_user_id);
		$this->view->setVar('filter_user_id', $filter_user_id);
		$this->view->setVar('can_filter_by_user', $can_filter_by_user);
		
		$this->view->setVar('params_set', $params_set = ($params_set = $filter_user_id || $filter_change_type || $filter_table || ($filter_daterange && ($filter_daterange != _t('any time')))));
		
		if (!($page = $this->request->getParameter('page', pInteger))) { $page = 0; }
		$this->view->setVar('page', $page);
		
		$start = (int)($page * self::$log_entries_per_page);

		$log_entries = $params_set ? ApplicationChangeLog::getChangeLog(['limitByUnit' => true, 'groupBySubject' => true, 'tables' => $filter_table ? $filter_table : array_values($table_list), 'start' => $start, 'limit' => self::$log_entries_per_page, 'daterange' => ($filter_daterange !== _t('any time')) ? $filter_daterange : null, 'user_id' => $filter_user_id, 'changetype' => $filter_change_type]) : [];
		$this->view->setVar('change_log_list', $log_entries);

		$this->render('global_change_log_html.php');
	}
	# -------------------------------------------------------
}
