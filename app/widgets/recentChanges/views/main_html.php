<?php
/* ----------------------------------------------------------------------
 * app/widgets/recentChanges/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	$po_request 			= $this->getVar('request');
	$vs_widget_id 			= $this->getVar('widget_id');
	$vn_table_num			= $this->getVar('table_num');
 	
	$t_log 						= $this->getVar('change_log');
	
	$vn_threshold_in_seconds = ($this->getVar('threshold_in_hours') * 3600);
	
	$vn_end_date_for_display = time();
	$vn_start_date_for_display = $vn_end_date_for_display - $vn_threshold_in_seconds;
	$o_tep = new TimeExpressionParser();
	$o_tep->setUnixTimestamps($vn_start_date_for_display, $vn_end_date_for_display);
	
	$vn_displayed_date_range = $o_tep->getText(array('timeOmit' => true));
	
	$va_log_entries 		= array_reverse($t_log->getRecentChanges($vn_table_num, $vn_threshold_in_seconds, 1000)); // reverse to put most recent up top
?>
<div class="dashboardWidgetContentContainer">
<?php
		if(sizeof($va_log_entries) > 0){
?>
			<div class="dashboardWidgetHeading"><?php print _t("Changes to <strong>%1</strong> from %2", $this->getVar('table_name_plural'), $vn_displayed_date_range); ?></div>
			<div class="dashboardWidgetScrollMedium" style="padding-left:10px;"><ul>
<?php
			foreach ($va_log_entries as $vs_log_key => $va_log_entry) {
				// $va_log_entry is a list of changes performed by a user as a unit (at a single instant in time)
				// We grab the date & time, user name and other stuff out of the first entry in the list (index 0) because
				// these don't vary from change to change in a unit, and the list is always guaranteed to have at least one entry
				//
				print "<li><span style='font-size:12px; font-weight:bold;'>".$va_log_entry[0]['user_fullname']." "._t('edited')." <a href='".caEditorUrl($po_request, $va_log_entry[0]['subject_table_num'], $va_log_entry[0]['subject_id'])."'>".$va_log_entry[0]['subject']."</a></span><br/>\n";
				print $va_log_entry[0]['datetime'].'<br/>';
				print "<a href='#' id='more".$vs_log_key."' onclick='jQuery(\"#more".$vs_log_key."\").hide(); jQuery(\"#changes".$vs_log_key."\").slideDown(250); return false;'>More Info &rsaquo;</a>";
				print "<div style='display:none;' id='changes".$vs_log_key."'><ul>";					// date/time of change, ready for display (don't use date() on it)
				// Print out actual content changes
				foreach($va_log_entry as $va_change_list) {
					foreach($va_change_list['changes'] as $va_change) {
						print "<li>";
						switch($va_change_list['changetype']) {
							case 'I':		// insert (aka add)
								print _t('Added %1 to \'%2\'', $va_change['description'], $va_change['label']);
								break;
							case 'U':	// update
								print _t('Updated %1 to \'%2\'', $va_change['label'], $va_change['description']);
								break;
							case 'D':	// delete	
								print _t('Deleted %1', $va_change['label']);
								break;
							default:		// unknown type - should not happen
								print _t('Unknown change type \'%1\'', $va_change['changetype']);
						}
						print "</li>\n";
						
					}
				}
				print "</ul>";
				print "<a href='#' id='hide".$vs_log_key."' style='padding-left:10px;' onclick='jQuery(\"#changes".$vs_log_key."\").slideUp(250); jQuery(\"#more".$vs_log_key."\").show(); return false;'>Hide &rsaquo;</a>";
				
				print "</div></li>";
			}
			print "</ul></div>";
		}else{
			print "<div class='dashboardWidgetHeading'>"._t("There have been no changes to <strong>%1</strong> from %2.", $this->getVar('table_name_plural'), $vn_displayed_date_range)."</div>";
		}
?>
</div>
