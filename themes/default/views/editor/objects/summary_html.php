<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/summary_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 	$t_item 						= $this->getVar('t_subject');
	$vn_item_id 					= $this->getVar('subject_id');
	
	$va_bundle_displays 	= $this->getVar('bundle_displays');
	$t_display 					= $this->getVar('t_display');
	$va_placements 			= $this->getVar("placements");
	$va_reps 						= $t_item->getRepresentations(array("thumbnail", "medium"));
	
?>
<div id="summary" style="clear: both;">
    <div id="printButton">
	<a href="<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "PrintSummary", array("object_id" => $t_item->getPrimaryKey()))?>" target="_blank">
	    <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/print.gif" width="15" height="18" border="0" title="<?php print _t("print page"); ?>">
	</a>
    </div>

<?php
	if ($vs_display_select_html = $t_display->getBundleDisplaysAsHTMLSelect('display_id', array('onchange' => 'jQuery("#caSummaryDisplaySelectorForm").submit();',  'class' => 'searchFormSelector'), array('table' => $t_item->tableNum(), 'value' => $t_display->getPrimaryKey(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'user_id' => $this->request->getUserID(), 'restrictToTypes' => array($t_item->getTypeID())))) {
		print caFormTag($this->request, 'Summary', 'caSummaryDisplaySelectorForm');
?>
			<div class='searchFormSelector' style='float: right; margin-bottom: 3px; font-size: 9px;'>
<?php
					print _t('Display').': '.$vs_display_select_html; 
?>
			</div>
			<input type="hidden" name="object_id" value="<?php print $vn_item_id; ?>"/>
		</form>
<?php
	}
?>
	<div id="title">
		<?php print $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
<?php
		foreach($va_placements as $vn_placement_id => $va_info) {
			$vs_class="";
			$va_tmp = explode('.', $va_info['bundle_name']);
			if (in_array($va_info['bundle_name'], array('ca_objects.preferred_labels', 'ca_object_labels.name'))) { continue; }		// skip preferred labels because we always output it above
			if (in_array($va_tmp[0], array('ca_object_representations'))) { continue; } // skip object representations
			
			if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array('request' => $this->request)))) {
				if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
				$vs_display_value = "<"._t('not defined').">";
				$vs_class = " notDefined";
			}
			print "<div class=\"unit".$vs_class."\"><span class=\"heading".$vs_class."\">".$va_info['display'].":</span> ".$vs_display_value."</div>\n";
		}
?>
			</td>
<?php
	$vn_left_col_width = "";
	if(sizeof($va_reps) > 2){
		$vn_left_col_width = " width='430px'";
	}elseif(sizeof($va_reps) > 1){
		$vn_left_col_width = " width='290px'";
	}else{
		$vn_left_col_width = " width='420px'";
	}
?>
			<td valign="top" align="left"<?php print $vn_left_col_width; ?>>
<?php
	if (is_array($va_reps)) {
		foreach($va_reps as $va_rep) {
			if(sizeof($va_reps) > 1){
				# --- more than one rep show thumbnails
				$vn_padding_top = ((120 - $va_rep["info"]["thumbnail"]["HEIGHT"])/2) + 5;
				print "<table style='float:left; margin:5px;".$vs_clear."' cellpadding='0' cellspacing='0'><tr><td align='center' valign='middle'><div class='thumbnailsImageContainer' style='padding: ".$vn_padding_top."px 5px ".$vn_padding_top."px 5px;'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('object_id' => $vn_item_id, 'representation_id' => $va_rep['representation_id']))."\");'>".$va_rep['tags']['thumbnail']."</a>\n";
				
				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div style='float: right; margin-top: 5px;'>".caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_DOWNLOAD__), '', 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('object_id' => $vn_item_id, 'representation_id' => $va_rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div></td></tr></table>\n";
			}else{
				# --- one rep - show medium rep
				print "<div style='padding: 0px 0px 0px 20px;'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('object_id' => $vn_item_id, 'representation_id' => $va_rep['representation_id']))."\");'>".$va_rep['tags']['medium']."</a>\n";
				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div style='float: right; margin-top: 5px;'>".caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_DOWNLOAD__), '', 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('object_id' => $vn_item_id, 'representation_id' => $va_rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div>";
			}
		}
	}
	
	print "<div class=\"unit\">".$va_rep['tags']['preview170']."</div>\n";
?>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
