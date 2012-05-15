<?php
/* ----------------------------------------------------------------------
 * bundles/ca_search_form_attributes.php : bundle to render attributes for "advanced" (field-level) search forms
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
	
	$va_elements =			$this->getVar('elements');
	$va_element_ids = 		$this->getVar('element_ids');
	$vs_element_set_label = $this->getVar('element_set_label');
	
?>
		<div>				
<?php
			foreach($va_elements as $vn_container_id => $va_element_list) {
				if ($vn_container_id === '_locale_id') { continue; }
?>
				<table class="attributeListItem" cellpadding="0px" cellspacing="0px">
					<tr>
<?php
						foreach($va_element_list as $vs_element) {
							print '<tr><td class="attributeListItem"><div class="searchFormLineModeElementSubLabel">'.$vs_element."</div></td></tr>\n";
						}
?>
					</tr>
				</table>
<?php
			}

			if (isset($va_elements['_locale_id'])) {
				print ($va_elements['_locale_id']['hidden']) ? $va_elements['_locale_id']['element'] : '<div class="formLabel">'._t('Locale ').$va_elements['_locale_id']['element'].'</div>';
			}
?>
		</div>