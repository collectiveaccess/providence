<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/GlobalChangeController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2019 Whirl-i-Gig
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
	public function Index() {

		if(!$this->request->getUser()->canDoAction('can_view_my_change_logs')) { // can view everything
			$this->response->setRedirect(
				$this->request->getAppConfig()->get('error_display_url').'/n/2320?r='.urlencode($this->getRequest()->getFullUrlPath())
			);
			return;
		}

		AssetLoadManager::register('tableList');

		$vn_filter_table = $this->request->getParameter('filter_table', pInteger);
		$this->getView()->setVar('filter_table', $vn_filter_table);

		if($vn_filter_table) {
			$va_log_tables = [$vn_filter_table];
		} else {
			$va_log_tables = [57,51,20,72,67,13,89,56,133,137,33,153,155];
		}

		$this->getView()->setVar('filter_change_type', $vs_filter_change_type = $this->request->getParameter('filter_change_type', pString));
		$this->getView()->setVar('change_log_search', $vs_filter_date = $this->request->getParameter('change_log_search', pString));
		$this->getView()->setVar('filter_user_id', $filter_user_id = $this->request->getParameter('filter_user', pInteger));

		$va_log_entries = [];
		foreach($va_log_tables as $vn_table_num) {

			$va_table_log_entries = ApplicationChangeLog::getChangeLogForTable($vn_table_num, ['limit' => 100, 'daterange' => ($vs_filter_date !== _t('any time')) ? $vs_filter_date : null, 'user_id' => $filter_user_id]);
			foreach($va_table_log_entries as $vs_unit_id => $va_log) {
// 				if($vs_filter_change_type) {
// 					if($va_log[0]['changetype'] != $vs_filter_change_type) { continue; }
// 				}
				$va_log_entries[$vn_table_num . $vs_unit_id] = $va_log;

				// the ui table doesn't have paging, so we should impose some kind of limit here
				//if(sizeof($va_log_entries) > 1000) { break 2; }
			}
		}

		$this->getView()->setVar('change_log_list', $va_log_entries);

		$this->render('global_change_log_html.php');
	}
	# -------------------------------------------------------
}
