<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/maintenance/export_configuration_landing_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
	print "<h1>"._t("Export system configuration as installation profile")."</h1>\n";

	print "<div class='searchReindexHelpText'>";
	print _t("<p>You can export the current configuration of this system as an <em>installation profile</em>. A profile is an XML document fully describing all of the metadata elements, lists, user interfaces, locales, search forms and displays present in your system. The <em>CollectiveAccess</em> installer can use installation profiles to create new, empty instances with specific configurations for use in new projects.</p>
	<p>Click on the \"Export\" button below to export and download the current system configuration as an installation profile.</p> 
	<p>Note that for systems with complex configurations, generating the profile may take up to two minutes.</p>
	");
	
	print caFormTag($this->request, 'export', 'caExportConfigurationForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
	print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_BUTTON_GO__, _t("Export and download system configuration"), 'caExportConfigurationForm', array())."</div>";
	print caHTMLHiddenInput('download', array('value' => 1));
	print "</form>";
	print "</div>\n";
?>