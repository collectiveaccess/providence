<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/maintenance/search_reindex_landing_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
	print "<h1>"._t("Rebuild hierarchical indices")."</h1>\n";

	print "<div class='searchReindexHelpText'>";
	print _t("<p>CollectiveAccess relies upon special <em>indices</em> to speed retrieval of hierarchical data such as multi-level lists and storage locations. Occasionally these indices can get out of sync with the actual hierarchical structure of your data. When this occurs you may experience odd behavior adding, moving or deleting items in a hierarchy. If you are experiencing issues with hierarchies you can rebuild the indices using this tool.</p> 
<p>Note that depending upon the size of your database rebuilding can take from a few seconds to several minutes. During the rebuilding process the system will remain usable but hierarchical functions may return inconsistent results.</p>
	");
	
	print caFormTag($this->request, 'reindex', 'caHierarchicalReindexForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
	print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t("Rebuild hierarchical indices"), 'caHierarchicalReindexForm', array())."</div>";
	print "</form>";
	print "</div>\n";
?>