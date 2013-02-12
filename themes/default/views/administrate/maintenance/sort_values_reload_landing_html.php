<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/maintenance/sort_values_reload_landing_html.php :
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
 
	print "<h1>"._t("Reload sort values")."</h1>\n";

	print "<div class='searchReindexHelpText'>";
	print _t("<p>CollectiveAccess relies upon <em>sort values</em> when sorting values that should not sort alphabetically, such as titles with articles (eg. <em>The Man Who Fell to Earth</em> should sort as <em>Man Who Fell to Earth, The</em>) and alphanumeric identifiers (eg. <em>2011.001</em> and <em>2011.2</em> should sort next to each other with leading zeros in the first ignored).</p>
<p>Sort values are derived from corresponding values in your database. The internal format of sort values can vary between versions of CollectiveAccess causing erroneous sorting behavior after an upgrade. If you notice values such as titles and identifiers are sorting incorrectly, you may need to reload sort values from your data.</p> 
<p>Note that depending upon the size of your database reloading sort values can take from a few minutes to an hour or more. During the reloading process the system will remain usable but search and browse functions may return incorrectly sorted results. </p>
	");
	
	print caFormTag($this->request, 'reload', 'caSortValuesReloadForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
	print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t("Reload sort values"), 'caSortValuesReloadForm', array())."</div>";
	print "</form>";
	print "</div>\n";
?>