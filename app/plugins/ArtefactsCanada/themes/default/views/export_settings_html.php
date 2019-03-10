<?php
/* ----------------------------------------------------------------------
 * app/plugins/ArtefactsCanada/controllers/ExportController.php :
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
 
 	
 	$va_sets_list = $this->getVar('sets_list');
 	$vb_sets_available = (is_array($va_sets_list) && sizeof($va_sets_list));
 	

	print "<h1>"._t("Export data to Artefacts Canada")."</h1>\n";

	print "<div class='searchReindexHelpText'>";
	print _t("<p>To export data for selected objects to Artefacts Canada create a set containing the objects, then select that set from the list below. A ZIP file containing an Artefacts Canada-compatible data file as well as all related media will be created and made available for download.</p>
	");
	if (!$vb_sets_available) {
		print _t('<h2><em>You must create a set before you can export to Artefacts Canada.</em></h2>');
	}

	if ($vb_sets_available) {
		print caFormTag($this->request, 'Run', 'caArtefactsCanadaExportForm', null, 'post', 'multipart/form-data', '_top', ['noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true]);
		print "<p>"._t('Export set %1', $this->getVar('sets_list_select'))."</p>\n";
		print "<div style='text-align: center'>".caFormSubmitButton($this->request, __CA_NAV_ICON_GO__, _t("Export"), 'caArtefactsCanadaExportForm', [])."</div>";
		print "</form>";
	}

?>
 	</div>
 	
 	<br style="clear"/>
 	
 	<div class="caArtefactsCanadaResultsContainer">
		<div id="caArtefactsCanadaResults" class="bundleContainer">
			<div class="caArtefactsCanadaResultsMessage">
			</div>
		</div>
	</div>
</form>

<div class="editorBottomPadding"><!-- empty --></div>
 
 <script type="text/javascript">
 
 </script>
