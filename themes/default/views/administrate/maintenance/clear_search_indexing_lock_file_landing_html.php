<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/maintenance/clear_search_indexing_lock_file_landing_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
	print "<h1>"._t("Clear search indexing queue lock file")."</h1>\n";

	print "<div class='searchReindexHelpText'>";
	
	print _t("<p>The search indexing queue is a task run periodically, usually via cron, to process pending indexing tasks. Simultaneous instances of the queue processor are prevented by means of a lock file. The lock file is created when the queue starts and deleted when it completed. While it is present new queue processing instances will refuse to start. In some cases, when a queue processing instance is killed or crashes, the lock file may not be removed and the queue will refuse to re-start. Lingering lock files may be removed by clicking the button below.</p>");
	if (true) { //ca_search_indexing_queue::lockExists()) {
	    if (true) {//ca_search_indexing_queue::lockCanBeRemoved()) {
            print caFormTag($this->request, 'RemoveLock', 'caClearSearchIndexingLockFileForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
            print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_ICON_GO__, _t("Clear search indexing queue lock file"), 'caClearSearchIndexingLockFileForm', array())."</div>";
            print "</form>";
        } else {
            print _t("<p>You cannot clear the lock file as <em>it is not writeable by the web server user.</em> Check file permissions and try again.</p>");
        }
    } else {
        print _t("<p>You cannot clear the lock file as <em>no lock file is present at this time.</em></p>");
    }
	print "</div>\n";
?>
