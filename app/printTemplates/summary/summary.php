<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/summary/summary.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2024 Whirl-i-Gig
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
 * @name PDF
 * @type page
 * @pageSize letter
 * @pageOrientation portrait
 * @tables *
 * @generic 1
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
 * @param showIdentifierInFooter {"type": "CHECKBOX",  "label": "Show identifier in footer?", "value": "1", "default": false}
 * @param showTimestampInFooter {"type": "CHECKBOX",  "label": "Show current date/time in footer?", "value": "1", "default": false}
 *
 * ----------------------------------------------------------------------
 */ 
$t_item 				= $this->getVar('t_subject');
$t_display 				= $this->getVar('t_display');
$display_list 			= $this->getVar("display_list");
?>
<div class="summary">
	<div class="title">
		<?= $t_item->getLabelForDisplay();?>
	</div>
	<?php
	foreach($display_list as $placement_id => $display_item) {
		if (!is_array($display_item)) break;
		$locale = caGetOption('locale', $display_item['settings'] ?? [], null);
		if(!$locale && preg_match("!^".$t_item->tableName().".preferred_labels!", $display_item['bundle_name'] ?? null)) { continue; }
	
		if (!strlen($display_value = $t_display->getDisplayValue($t_item, $placement_id, ['locale' => $locale, 'forReport' => true, 'purify' => true]))) {
			if (!(bool)$t_display->getSetting('show_empty_values')) { continue; }
			$display_value = "&lt;"._t('not defined')."&gt;";
		} 
	?>
		<div class="data"><span class="label"><?= $display_item['display']; ?></span><span> <?= $display_value; ?></span></div>
	<?php
	}
?>
</div>
