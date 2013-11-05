<?php
/* ----------------------------------------------------------------------
 * views/editor/storage_locations/summary_html.php : 
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
	
?>
<div id="summary" style="clear: both;">
	<div id="printButton">
	<a href="<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "PrintSummary", array($t_item->PrimaryKey() => $t_item->getPrimaryKey()))?>" target="_blank">
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
			<input type="hidden" name="<?php print $t_item->PrimaryKey(); ?>" value="<?php print $vn_item_id; ?>"/>
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
			$vs_class = "";
			if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array('request' => $this->request)))) {
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