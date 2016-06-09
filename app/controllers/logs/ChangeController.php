<?php
/* ----------------------------------------------------------------------
 * app/controllers/logs/ChangeController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

class ChangeController extends ActionController {
	# -------------------------------------------------------
	#
	# -------------------------------------------------------
	public function Index() {
		AssetLoadManager::register('tableList');

		$o_change_log = new ApplicationChangeLog();

		$va_log_entries = [];

		foreach([57,51,20,72,67,13,89,56,133,137,33,153,155] as $vn_table_num) {

			$va_table_log_entries = $o_change_log->getRecentChanges($vn_table_num);
			foreach($va_table_log_entries as $vs_unit_id => $va_log) {
				$va_log_entries[$vn_table_num . $vs_unit_id] = $va_log;
			}
		}

		$this->getView()->setVar('change_log_list', $va_log_entries);

		$this->render('change_log_html.php');
	}
	# -------------------------------------------------------
}
