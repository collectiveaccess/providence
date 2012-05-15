<?php
/* ----------------------------------------------------------------------
 * views/editor/places/summary_html.php : 
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
 	$t_item = $this->getVar('t_subject');
	
	$va_bundle_displays = $this->getVar('bundle_displays');
	$t_display = $this->getVar('t_display');
	$va_placements = $this->getVar("placements");
	
?>
<style type="text/css">
<!--
	table.page_header {width: 100%; border: none; background-color: #<?php print $this->request->config->get('summary_color')?>; color: #FFFFFF; padding: 1mm; }
	table.page_footer {width: 100%; border: none; background-color: #<?php print $this->request->config->get('summary_footer_color')?>; color: #FFFFFF; padding: 1mm}
	span.label {font-weight: bold }
	div.data {border-bottom: 1px solid #cccccc; margin-top: 10px; }
-->
</style>
	<page backtop="14mm" backbottom="14mm" backleft="10mm" backright="10mm" style="font-size: 12pt">
<?php
	if($this->request->config->get('summary_header_enabled')) {
?>			
		<page_header>
			<table class="page_header">
				<tr>
					<td style="width: 33%; text-align: left">
						<img src="<?php print $this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('summary_img')?>"/>
					</td>
					<td style="width: 67%; text-align: right">
<?php  
						if($this->request->config->get('summary_show_timestamp')) {
							print "<span class='headerText'>".caGetLocalizedDate()."</span>";
						}
					
						if($this->request->config->get('summary_show_identifier')) {
							print _t("Place identifier").": ".$t_item->get("idno"); 
						}
?>
					</td>
				</tr>
			</table>
		</page_header>
<?php
	}
	
	if($this->request->config->get('summary_footer_enabled')) {
?>		
		<page_footer>
			<table class="page_footer">
				<tr>
					<td style="width: 33%; text-align: left;">
						&nbsp;
					</td>
					<td style="width: 34%; text-align: center">
						page [[page_cu]]/[[page_nb]]
					</td>
					<td style="width: 33%; text-align: right">
						&nbsp;
					</td>
				</tr>
			</table>
		</page_footer>
<?php
	}
?>		
		<div style="width:100%; margin-top:10px; text-align: center;">
			<h1><?php print $t_item->getLabelForDisplay();?></h1>
		</div>
			<?php
				foreach($va_placements as $vn_placement_id => $va_bundle_info){
					if (!is_array($va_bundle_info)) break;
					if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $vn_placement_id, array('purify' => true)))) {
						if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
						$vs_display_value = "&lt;"._t('not defined')."&gt;";
					}
					// this is a rough estimate as to what can be fit onto one page
					// as HTML2PDF doesn't do page automatic page breaks if single
					// elements are too long, we need to cut values that are too long.
					// of course the font is not monospaced so this can only be an
					// estimate with (hopefully) enough wiggle room for everything
					// that might be thrown at us. otherwise we barf here ...
					if(strlen($vs_display_value)>4500){
						$vs_display_value = substr($vs_display_value, 0, 4495)." ...";
					}
					print "<div class=\"data\"><span class=\"label\">".$va_bundle_info['display'].":</span> ".$vs_display_value."</div>\n";
				}
			?>
	</page>