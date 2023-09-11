<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/results/display.php
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
 * @name PDF display
 * @type omit
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
 * @param showSearchTermInFooter {"type": "CHECKBOX",  "label": "Show search terms in footer?", "value": "1", "default": false}
 * @param showSearchResultCountInFooter {"type": "CHECKBOX",  "label": "Show result count in footer?", "value": "1", "default": false}
 * @param showTimestampInFooter {"type": "CHECKBOX",  "label": "Show current date/time in footer?", "value": "1", "default": false}
 *
 * ----------------------------------------------------------------------
 */
$t_display				= $this->getVar('display');
$display_list 			= $this->getVar('display_list');
$result 				= $this->getVar('result');
$items_per_page 		= $this->getVar('current_items_per_page');
$num_items				= (int)$result->numHits();
?>
<div id='body'>
<?php
	$result->seek(0);
	
	$line_count = $start = 0;
	while($result->nextHit()) {
		$object_id = $result->get('ca_objects.object_id');		
?>
		<div class="row">
			<table>
				<tr>
					<td style="width:250px;">
<?php	
						if ($rep = $result->get('ca_object_representations.media.small', ['usePath' => true, 'scaleCSSWidthTo' => '250px', 'scaleCSSHeightTo' => '200px'])) {
							print "<div class='objThumb'>".$rep."</div>";
						} else {
							print "<div class='imageTinyPlaceholder'></div> ";
							$rep = null;
						}
?>				
					</td>
					<td>
						<div class="metaBlock">
<?php				
							if (is_array($display_list)) {
								foreach($display_list as $placement_id => $display_item) {
									if (!strlen($vs_display_value = $t_display->getDisplayValue($result, $placement_id, array('forReport' => true, 'purify' => true)))) {
										if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
										$vs_display_value = "&lt;"._t('not defined')."&gt;";
									} 
									print "<div class='metadata'><span class='displayHeader'>".$display_item['display']."</span>: <span class='displayValue' >".(strlen($vs_display_value) > 1200 ? strip_tags(substr($vs_display_value, 0, 1197))."..." : $vs_display_value)."</span></div>";		
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
