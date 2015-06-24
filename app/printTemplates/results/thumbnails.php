<?php
/* ----------------------------------------------------------------------
 * app/templates/thumbnails.php
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
 * @name PDF (thumbnails)
 * @type page
 * @pageSize letter
 * @pageOrientation landscape
 * @tables ca_objects
 *
 * @marginTop 0.8in
 * @marginLeft 0.9in
 * @marginBottom 0.5in
 * @marginRight 0.25in
 *
 * ----------------------------------------------------------------------
 */

	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vs_default_action		= $this->getVar('default_action');
	$vo_ar					= $this->getVar('access_restrictions');
	$vo_result_context 		= $this->getVar('result_context');
	$vn_num_items			= (int)$vo_result->numHits();
	$vs_color 				= ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF";;
	
	$vn_start 				= 0;

	print $this->render("pdfStart.php");
	print $this->render("header.php");
	print $this->render("footer.php");
?>
		<div id='body'>
<?php

		$vo_result->seek(0);
		
		$vn_lines_on_page = 0;
		$vn_items_in_line = 0;
		
		$vn_left = $vn_top = 0;
		$vn_page_count = 0;
		while($vo_result->nextHit()) {
			$vn_object_id = $vo_result->get('ca_objects.object_id');		
?>
			<div class="thumbnail" style="left: <?php print $vn_left; ?>mm; top: <?php print $vn_top + 3; ?>mm;">
				<?php print "<div class='imgThumb'><img src='".$vo_result->getMediaPath('ca_object_representations.media', 'preview')."'/></div>"; ?>
				<br/>
				<?php print "<div class='caption'>".$vo_result->getWithTemplate('^ca_objects.preferred_labels.name (^ca_objects.idno)')."</div>"; ?>
			</div>
<?php

			$vn_items_in_line++;
			$vn_left += 58;
			if ($vn_items_in_line >= 4) {
				$vn_items_in_line = 0;
				$vn_left = 0;
				$vn_top += 58;
				$vn_lines_on_page++;
				print "<br class=\"clear\"/>\n";
			}
			
			if ($vn_lines_on_page >= 3) { 
				$vn_page_count++;
				$vn_lines_on_page = 0;
				$vn_left = 0; 
				
				$vn_top = ($this->getVar('PDFRenderer') === 'domPDF') ? 0 : ($vn_page_count * 183);
				
				print "<div class=\"pageBreak\" style=\"page-break-before: always;\">&nbsp;</div>\n";
			}
		}
?>
		</div>
<?php
	print $this->render("pdfEnd.php");
?>
