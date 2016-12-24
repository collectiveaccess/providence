<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/ca_site_pages_content.php : 
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
 	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	$va_settings 				= $this->getVar('settings');

	$vb_read_only				= (isset($va_settings['readonly']) && $va_settings['readonly']);
	
	$va_history 				= $this->getVar('checkout_history');
	$vn_checkout_count 			= $this->getVar('checkout_count');
	$va_client_list 			= $this->getVar('client_list');
	$vn_client_count 			= $this->getVar('client_count');
	
	$t_page						= $this->getVar('t_page');
	$t_template 				= $this->getVar('t_template');
	
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>
<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo">		
<?php
	if(is_array($va_fields = $t_page->getHTMLFormElements(['tagnamePrefix' => $vs_id_prefix]))) {
		foreach($va_fields as $vs_field => $va_element_info) {
			print $va_element_info['element_with_label']."\n";
		}
	}
?>
			</div>
		</div>
	</div>
</div>