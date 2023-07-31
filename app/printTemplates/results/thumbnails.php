<?php
/* ----------------------------------------------------------------------
 * app/prinTemplates/results/thumbnails.php
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
$result 				= $this->getVar('result');
$items_per_page 		= $this->getVar('current_items_per_page');
$result_context 		= $this->getVar('result_context');
$num_items				= (int)$result->numHits();
?>
<div id='body'>
<?php
	$result->seek(0);
	$start = $lines_on_page = $items_in_line = $left = $top = $page_count = 0;
	
	while($result->nextHit()) {
		$object_id = $result->get('ca_objects.object_id');		
?>
		<div class="thumbnail" style="left: <?= $left; ?>mm; top: <?= $top + 3; ?>mm;">
			<?= "<div class='imgThumb'><img src='".$result->getMediaPath('ca_object_representations.media', 'preview')."'/></div>"; ?>
			<br/>
			<?= "<div class='caption'>".$result->getWithTemplate('^ca_objects.preferred_labels.name (^ca_objects.idno)')."</div>"; ?>
		</div>
<?php
		$items_in_line++;
		$left += 58;
		if ($items_in_line >= 4) {
			$items_in_line = 0;
			$left = 0;
			$top += 58;
			$lines_on_page++;
			print "<br class=\"clear\"/>\n";
		}
		
		if ($lines_on_page >= 3) { 
			$page_count++;
			$lines_on_page = 0;
			$left = 0; 
			
			$top = ($this->getVar('PDFRenderer') === 'domPDF') ? 0 : ($page_count * 183);
			
			print "<div class=\"pageBreak\" style=\"page-break-before: always;\">&nbsp;</div>\n";
		}
	}
?>
</div>
