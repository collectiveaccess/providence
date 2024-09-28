<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/results/checklist.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name PDF (checklist)
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_objects
 *
 * @marginTop 0.75in
 * @marginLeft 0.25in
 * @marginBottom 0.5in
 * @marginRight 0.25in
 *
 * @includeHeaderFooter true
 *
 * @param includeLogo {"type": "CHECKBOX",  "label": "Include logo?", "value": "1", "default": true}
 * @param includePageNumbers {"type": "CHECKBOX",  "label": "Include page numbers?", "value": "1", "default": true}
 * @param showSearchTermInFooter {"type": "CHECKBOX",  "label": "Show search terms?", "value": "1", "default": false}
 * @param showSearchResultCountInFooter {"type": "CHECKBOX",  "label": "Show result count?", "value": "1", "default": false}
 * @param showTimestampInFooter {"type": "CHECKBOX",  "label": "Show current date?", "value": "1", "default": false}
 *
 * ----------------------------------------------------------------------
 */
$t_display				= $this->getVar('t_display');
$display_list 			= $this->getVar('display_list');
$result 				= $this->getVar('result');
$items_per_page 		= $this->getVar('current_items_per_page');
$current_sort 			= $this->getVar('current_sort');
$default_action			= $this->getVar('default_action');
$ar						= $this->getVar('access_restrictions');
$result_context 		= $this->getVar('result_context');
$num_items				= (int)$result->numHits();
?>
<div class='body checklist'>
<?php
	$result->seek(0);
	
	$line_count = $start = 0;
	while($result->nextHit()) {
		$object_id = $result->get('ca_objects.object_id');		
?>
		<div class="row">
			<table width="100%">
				<tr valign="top">
					<td width="20%">
<?php 
						if (($path = $result->getMediaPath('ca_object_representations.media', 'thumbnail')) && file_exists($path)) {
							print "<div class=\"imageTiny\"><img src='{$path}'/></div>";
						} else {
?>
							<div class="imageTinyPlaceholder">&nbsp;</div>
<?php					
						}	
?>	
					</td>
					<td>
						<div class="metaBlock">
<?php				
							print "<div class='title'>".$result->getWithTemplate('^ca_objects.preferred_labels.name')."</div>"; 
							if (is_array($display_list)) {
								foreach($display_list as $placement_id => $display_item) {
									$locale = caGetOption('locale', $display_item['settings'] ?? [], null);
									if(!$locale && preg_match("!^(ca_object_representations.media|ca_objects.preferred_labels)!", $display_item['bundle_name'] ?? null)) { continue; }
									if (!strlen($display_value = $t_display->getDisplayValue($result, $placement_id, ['locale' => $locale, 'forReport' => true, 'purify' => true]))) {
										if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
										$display_value = "&lt;"._t('not defined')."&gt;";
									} 
					
									print "<div class='metadata'><span class='displayHeader'>".$display_item['display']."</span>: <span class='displayValue' >".(strlen($display_value) > 1200 ? strip_tags(substr($display_value, 0, 1197))."..." : strip_tags($display_value))."</span></div>";		
								}	
							}						
?>
						</div>				
					</td>	
				</tr>
			</table>	
		</div>
<?php
	}
?>
</div>
