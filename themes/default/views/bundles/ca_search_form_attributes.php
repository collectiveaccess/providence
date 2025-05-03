<?php
/* ----------------------------------------------------------------------
 * bundles/ca_search_form_attributes.php : bundle to render attributes for "advanced" (field-level) search forms
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
$elements =			$this->getVar('elements');
$element_ids = 		$this->getVar('element_ids');
$element_set_label = $this->getVar('element_set_label');	
?>
<div>				
<?php
	foreach($elements as $vn_container_id => $element_list) {
		if ($vn_container_id === '_locale_id') { continue; }
?>
		<table class="attributeListItem" cellpadding="0px" cellspacing="0px">
			<tr>
<?php
				foreach($element_list as $element) {
					print '<tr><td class="attributeListItem"><div class="searchFormLineModeElementSubLabel">'.$element."</div></td></tr>\n";
				}
?>
			</tr>
		</table>
<?php
	}

	if (isset($elements['_locale_id'])) {
		print ($elements['_locale_id']['hidden']) ? $elements['_locale_id']['element'] : '<div class="formLabel">'._t('Locale ').$elements['_locale_id']['element'].'</div>';
	}
?>
</div>
