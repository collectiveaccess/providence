<?php
/* ----------------------------------------------------------------------
 * app/templates/checklist.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
 * @name Register
 * @type page
 * @pageSize a4
 * @pageOrientation landscape
 * @tables ca_objects
 *
 * @marginTop 0.75in
 * @marginLeft 0.25in
 * @marginBottom 0.5in
 * @marginRight 0.25in
 *
 * ----------------------------------------------------------------------
 */

$t_display = $this->getVar('t_display');
$va_display_list = $this->getVar('display_list');
$vo_result = $this->getVar('result');
$vn_items_per_page = $this->getVar('current_items_per_page');
$vs_current_sort = $this->getVar('current_sort');
$vs_default_action = $this->getVar('default_action');
$vo_ar = $this->getVar('access_restrictions');
$vo_result_context = $this->getVar('result_context');
$vn_num_items = (int) $vo_result->numHits();

$vn_start = 0;

print $this->render("../pdfStart.php");
print $this->render("../header.php");
print $this->render("../footer.php");
?>
<table id='body'>

	<?php

	$vo_result->seek(0);

	$vn_line_count = 0;

	while ($vo_result->nextHit()) {
		if ($vn_line_count === 0) {
			?>
			<thead>
			<tr>
				<?php
				foreach ($va_display_list as $vn_placement_id => $va_display_item) { ?>
					<th class='displayHeader'><?php print $va_display_item['display'] ?></th>
					<?php
				}
				?>
			</tr>
			</thead>
			<?php
		}
		?>
		<tr class="row">
			<?php
			foreach ($va_display_list as $vn_placement_id => $va_display_item) { ?>
				<td><?php
				$vs_display_value = $t_display->getDisplayValue(
					$vo_result,
					$vn_placement_id,
					array(
						'forReport' => TRUE,
						'purify' => TRUE
					)
				);
				print (strlen($vs_display_value) > 100 ? strip_tags(substr($vs_display_value, 0, 97)) . "&hellip;" : $vs_display_value);
				?></td><?php
				$vn_line_count++;
			}
			?>
		</tr>
		<?php
	}
	?>
</table>
<?php
print $this->render("../pdfEnd.php");
?>
