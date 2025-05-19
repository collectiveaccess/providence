<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/ca_site_pages_content.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2025 Whirl-i-Gig
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
$id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
$table_num 				= $this->getVar('table_num');

$t_subject				= $this->getVar('t_subject');
$settings 				= $this->getVar('settings');

$read_only				= (isset($settings['readonly']) && $settings['readonly']);

$history 				= $this->getVar('checkout_history');
$checkout_count 		= $this->getVar('checkout_count');
$client_list 			= $this->getVar('client_list');
$client_count 			= $this->getVar('client_count');

$t_page					= $this->getVar('t_page');
$t_template 			= $this->getVar('t_template');

print caEditorBundleShowHideControl($this->request, $id_prefix);
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caSitePageContents">
			<div class="labelInfo">		
<?php
	if(is_array($fields = $t_page->getHTMLFormElements(['tagnamePrefix' => $id_prefix, 'lookupUrls' => $this->getVar('lookup_urls'), 'contentUrl' => caNavUrl($this->request, '*', '*', 'getMediaList', ['page_id' => $t_subject->getPrimaryKey()])]))) {
		foreach($fields as $field => $element_info) {
			print $element_info['element_with_label']."\n";
		}
	}
?>
			</div>
		</div>
	</div>
</div>
