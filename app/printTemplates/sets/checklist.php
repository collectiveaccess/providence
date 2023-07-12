<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/sets/checklist.php
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
 * @param showSearchTermInFooter {"type": "CHECKBOX",  "label": "Show search terms in footer?", "value": "1", "default": false}
 * @param showSearchResultCountInFooter {"type": "CHECKBOX",  "label": "Show result count in footer?", "value": "1", "default": false}
 * @param showTimestampInFooter {"type": "CHECKBOX",  "label": "Show current date/time in footer?", "value": "1", "default": false}
 *
 * ----------------------------------------------------------------------
 */
$result 				= $this->getVar('result');
$num_items			= (int)$result->numHits();
$t_set					= $this->getVar("t_set");
?>
<div id='body'>
	<div class="row">
		<table>
		<tr><td>
			<div class='title'><?php print $t_set->get("ca_sets.preferred_labels.name"); ?></div>
<?php
			if($t_set->get("description")){
				print "<p>".$t_set->get("description")."</p>";
			}
?>
		</td></tr>
		</table>
	</div>
<?php

$result->seek(0);

$c = 0;
while($result->nextHit()) {
	$c++;
	$object_id = $result->get('ca_objects.object_id');		
?>
	<div class="row">
	<table>
	<tr>
		<td><b><?php print $c; ?></b>&nbsp;&nbsp;</td>
		<td>
<?php 
			if ($vs_path = $result->getMediaPath('ca_object_representations.media', 'thumbnail')) {
				print "<div class=\"imageTiny\"><img src='{$vs_path}'/></div>";
			} else {
?>
				<div class="imageTinyPlaceholder">&nbsp;</div>
<?php					
			}	
?>								

		</td><td>
			<div class="metaBlock">
<?php				
			print "<div class='title'>".$result->getWithTemplate('^ca_objects.preferred_labels.name (^ca_objects.idno)')."</div>"; 							
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
