<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/ajax_elements_settings_form_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 
	$t_element = $this->getVar('t_element');
	$vn_element_id = $t_element->getPrimaryKey();
	
	if(is_array($va_available_settings = $this->getVar('available_settings')) && sizeof($va_available_settings)) {
?>
		<div class='formLabel'><span id="_ca_metadata_element_labels_"><?php print _t("Datatype-specific options"); ?></span><br/></div>
		<div style="margin-left: 20px;">
<?php
		foreach($va_available_settings as $vs_code => $va_properties) {
			print $t_element->settingHTMLFormElement($vs_code, array('label_id' => "setting_{$vs_code}_datatype_label_{$vn_element_id}"));
		}	
?>
	</div>
<?php
	}
	
	print TooltipManager::getLoadHTML();
?>