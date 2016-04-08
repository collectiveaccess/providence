<?php
/* ----------------------------------------------------------------------
 * bundles/ca_attributes_elements_only.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 
	$vs_id_prefix 				= 	$this->getVar('placement_code').$this->getVar('id_prefix');
	$vs_render_mode 			=	$this->getVar('render_mode');

	/** @var BaseModelWithAttributes $t_instance */
	$t_instance 				=	$this->getVar('t_instance');
	/** @var ca_metadata_elements $t_element */
	$t_element					=	$this->getVar('t_element');
	$va_elements 				=	$this->getVar('elements');
	$va_element_ids 			= 	$this->getVar('element_ids');
	$va_element_info 			= 	$this->getVar('element_info');
	
	$va_settings 				= 	$this->getVar('settings');
	$vb_read_only				=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($this->getVar('t_instance')->tableName(), $this->getVar('element_code')) == __CA_BUNDLE_ACCESS_READONLY__));
	
	$va_element_settings 		=	$t_element->getSettings();

	foreach($va_elements as $vn_container_id => $va_element_list) {
		if ($vn_container_id === '_locale_id') { continue; }
?>
		<table class="attributeListItem">
			<tr>
<?php
				foreach($va_element_list as $vs_element) {
					print '<td class="attributeListItem">'.preg_replace("!{{[\d]+}}!", "", str_replace("textarea", "textentry", $vs_element)).'</td>';
				}
?>
			</tr>
		</table>
<?php
	}

	if (isset($va_elements['_locale_id'])) {
		print ($va_elements['_locale_id']['hidden']) ? $va_elements['_locale_id']['element'] : '<div class="formLabel">'._t('Locale').' '.$va_elements['_locale_id']['element'].'</div>';
	}