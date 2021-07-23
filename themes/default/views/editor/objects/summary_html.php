<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/summary_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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
	$item_id 				= $this->getVar('subject_id');
	
	$t_display 				= $this->getVar('t_display');
	$placements 			= $this->getVar("placements");
	$reps 					= $t_item->getRepresentations(array("thumbnail", "small", "medium"));
?>
    <div id="summary" style="clear: both;">
<?php
    print caEditorPrintSummaryControls($this);
?>
	<div id="title">
		<?= $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr class='summaryImages'>
			<td valign="top" align="center" width="744">
<?php
	if (is_array($reps)) {
		foreach($reps as $rep) {
			if(sizeof($reps) > 1){
				# --- more than one rep show thumbnails
				$padding_top = ((120 - $rep["info"]["thumbnail"]["HEIGHT"])/2) + 5;
				print "<table style='float:left; margin: 0px 16px 10px 0px; ".$clear."' cellpadding='0' cellspacing='0'><tr><td align='center' valign='middle'><div class='thumbnailsImageContainer' id='container".$rep['representation_id']."' style='padding: ".$padding_top."px 5px ".$padding_top."px 5px;' onmouseover='$(\".download".$rep['representation_id']."\").show();' onmouseout='$(\".download".$rep['representation_id']."\").hide();'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $item_id, 'representation_id' => $rep['representation_id']))."\");'>".$rep['tags']['thumbnail']."</a>\n";
				
				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div class='download".$rep['representation_id']." downloadMediaContainer'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1), 'downloadMedia', 'editor/objects', 'ObjectEditor', 'DownloadMedia', array('object_id' => $item_id, 'representation_id' => $rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div></td></tr></table>\n";
			}else{
				# --- one rep - show medium rep
				print "<div id='container".$rep['representation_id']."' class='oneThumbContainer' onmouseover='$(\".download".$rep['representation_id']."\").show();' onmouseout='$(\".download".$rep['representation_id']."\").hide();'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $item_id, 'representation_id' => $rep['representation_id']))."\");'>".$rep['tags']['medium']."</a>\n";
				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div class='download".$rep['representation_id']." downloadMediaContainer'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1), 'downloadMedia', 'editor/objects', 'ObjectEditor', 'DownloadMedia', array('object_id' => $item_id, 'representation_id' => $rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div>";
			}
		}
	}
	
?>
			</td>
		</tr>
		<tr>			
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($placements as $placement_id => $info) {
			$class="";
			$tmp = explode('.', $info['bundle_name']);
			if ((in_array($tmp[0], array('ca_object_representations')) && ($tmp[1] === 'media'))) { continue; } // skip object representations because we always output it above
			
			if (!strlen($display_value = $t_display->getDisplayValue($t_item, ($placement_id > 0) ? $placement_id : $info['bundle_name'], array_merge(['request' => $this->request], is_array($info['settings']) ? $info['settings'] : [])))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$display_value = "<"._t('not defined').">";
				$class = " notDefined";
			}
			print "<div class=\"unit{$class}\"><span class=\"heading{$class}\">".caTruncateStringWithEllipsis($info['display'], 26)."</span><span class='summaryData'> {$display_value}</span></div>\n";
		}
?>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
<?php
TooltipManager::add('#printButton', _t("Download Summary as PDF"));
TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));
