<?php
/* ----------------------------------------------------------------------
 * app/templates/display.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * @pageOrientation landscape
 * @tables ca_objects
 *
 * ----------------------------------------------------------------------
 */

	$t_display				= $this->getVar('display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vn_num_items			= (int)$vo_result->numHits();
	
	$vn_start 				= 0;

	print $this->render("pdfStart.php");
	print $this->render("header.php");
	print $this->render("footer.php");
?>
		<div id='body'>
			<table class="listtable" width="100%" border="0" cellpadding="0" cellspacing="0">
<?php

		$vo_result->seek(0);
		
		$i = 0;
		$vn_line_count = 0;
		while($vo_result->nextHit()) {
			$vn_movement_id = $vo_result->get('object_id');
			
			($i == 2) ? $i = 0 : "";
?>
			<tr <?php print ($i ==1) ? "class='odd'" : ""; ?>>
			<td>
				<table  width="100%"  cellpadding="0" cellspacing="0" class='summary' style='border:1px solid #ccc;'>
					<tr style='background-color:#eeeeee;'>
<?php
				$i_field = 0;
				$va_end_array = end($va_display_list);
				foreach($va_display_list as $vn_placement_id => $va_display_item) {

					$vs_display_value = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('forReport' => true, 'purify' => true));
					if (($header_has_ended == "yes") | (($header_has_ended != "yes") && ($i_field == 1))) {
						print "<td class='label'>".$va_display_item['display']."</td>";
					} 
					if (($header_has_ended != "yes") && ($i_field == 0)) {
						$va_colspan = "colspan='2'";
					} else {
						$va_colspan = "";
					}
					print "<td style='width:250px;' class='value' ".$va_colspan.">".(strlen($vs_display_value) > 1200 ? strip_tags(substr($vs_display_value, 0, 1197))."..." : $vs_display_value)."</td>";
					$i_field++;
					if ($i_field == 2) {
						$header_has_ended = "yes";
					}
					if (($header_has_ended == "yes") && ($i_field == 2)) {
						$i_field = 0;
					}
					if (($header_has_ended == "yes") && ($i_field == 0)) {
						print "</tr><tr>";
					}
					if ($va_end_array == $va_display_item) {
						print "</tr>";
					}
					
				}
				$header_has_ended = "no";
?>		
					
				</table>
				<hr>
			</td>	
			</tr>
<?php
			$i++;			
			
		}
?>
			</table>
		</div><!-- end body -->
		</div>
<?php
	print $this->render("pdfEnd.php");
?>