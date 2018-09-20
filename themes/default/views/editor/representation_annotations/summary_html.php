<?php
/* ----------------------------------------------------------------------
 * views/editor/representation_annotations/summary_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2018 Whirl-i-Gig
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
 	$t_item 				= $this->getVar('t_subject');
	$vn_item_id 			= $this->getVar('subject_id');
	
	$t_display 				= $this->getVar('t_display');
	$va_placements 			= $this->getVar("placements");	
?>
<div id="summary" style="clear: both;">
<?php
    print caEditorPrintSummaryControls($this);
?>
	<div id="title">
		<?php print $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($va_placements as $vn_placement_id => $va_info) {
			$vs_class = "";
			if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array())))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$vs_display_value = "&lt;"._t('not defined')."&gt;";
				$vs_class = " notDefined";
			}
			print "<div class=\"unit".$vs_class."\"><span class=\"heading".$vs_class."\">".$va_info['display'].":</span> ".$vs_display_value."</div>\n";
		}
?>
			</td>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
<?php
		TooltipManager::add('#printButton', _t("Download Summary as PDF"));
		TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));