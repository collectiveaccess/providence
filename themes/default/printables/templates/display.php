<?php
/* ----------------------------------------------------------------------
 * themes/default/printables/templates/display.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2026 Whirl-i-Gig
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
 * @name PDF (generic)
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_objects
 * @contexts summary, results, sets, bundles
 *
 * @marginTop 0.75in
 * @marginLeft 0.5in
 * @marginBottom 0.5in
 * @marginRight 0.5in
 *
 * @includeHeaderFooter true
 *
 * @param includeLogo {"type": "CHECKBOX",  "label": "Include logo?", "value": "1", "default": true}
 * @param includePageNumbers {"type": "CHECKBOX",  "label": "Include page numbers?", "value": "1", "default": true}
 * @param showTimestampInFooter {"type": "CHECKBOX",  "label": "Show current date/time in footer?", "value": "1", "default": false}
 *
 * ----------------------------------------------------------------------
 */
$result = $this->getVar('result');
$bundle_displays = $this->getVar('bundle_displays');
$t_display = $this->getVar('t_display');
$display_list = $this->getVar("display_list");

while($result->nextHit()) {
	$t_item = $result->getInstance();
?>
<div class="summary"> 
	<div class="title">
		<?= $t_item->getLabelForDisplay();?>
	</div>
	<div class="representationList">		
	<?php
		
		if($t_display->getSetting("show_representations") == "primary"){
			print $t_item->get("ca_object_representations.media.medium");
		}elseif($t_display->getSetting("show_representations") == "all"){
			$reps = $t_item->getRepresentations(array("thumbnail", "medium"), null, ['usePath' => false]);

			foreach($reps as $rep) {
				if(sizeof($reps) > 1){
					# more than one rep: show thumbnail version
					$padding_top = ((120 - $rep["info"]["thumbnail"]["HEIGHT"])/2) + 5;
					print $rep['tags']['thumbnail']."\n";
				}else{
					# one rep: show medium version 
					print $rep['tags']['medium']."\n";
				}
			}
		}
	?>
	</div>		
<?php
	foreach($display_list as $placement_id => $display_item) {
		if (!is_array($display_item)) break;
		$locale = caGetOption('locale', is_array($display_item['settings']) ? $display_item['settings'] : [], null);
		if(!$locale && preg_match("!^(ca_object_representations.media|ca_objects.preferred_labels)!", $display_item['bundle_name'] ?? null)) { continue; }
									
		if (!strlen($display_value = $t_display->getDisplayValue($t_item, $placement_id, ['locale' => $locale, 'forReport' => true, 'usePath' => true, 'purify' => true]))) {
			if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
			$display_value = "&lt;"._t('not defined')."&gt;";
		}
?>
		<div class="data"><span class="label"><?= $display_item['display']; ?></span><span class='meta'> <?= trim($display_value); ?></span></div>
<?php
	}
?>
</div>
<?php
}