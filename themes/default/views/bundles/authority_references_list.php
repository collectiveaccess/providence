<?php
/* ----------------------------------------------------------------------
 * bundles/authority_references_list.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 
	AssetLoadManager::register('tabUI');
 
	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_instance					= $this->getVar('t_instance');
	$va_settings 				= $this->getVar('settings');
	$va_errors 					= $this->getVar('errors');
		
	$va_references				= $this->getVar('references');
	$va_search_strings			= $this->getVar('search_strings');
	$vn_max_items				= caGetOption('maxReferencesToDisplay', $va_settings, 100);

	print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
?>

<div id="<?php print $vs_id_prefix; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div class="labelInfo authorityReferenceList">	
<?php
	if (is_array($va_errors) && sizeof($va_errors)) {
		print "<h2>".join("; ", $va_errors)."</h2>\n";
	} else {
		if (sizeof($va_references) > 0) {
?>
			<div id="<?php print $vs_id_prefix; ?>AuthorityReferenceTabs" class="authorityReferenceListContainer">
				<ul>
<?php
				foreach($va_references as $vn_table_num => $va_rows) {
					if ($t_ref_instance = Datamodel::getInstance($vn_table_num, true)) {
						$vs_ref_table_name = $t_ref_instance->tableName();
?>
						<li><a href="#<?php print $vs_id_prefix; ?>AuthorityReferenceTabs-<?php print $vs_ref_table_name; ?>"><span><?php print _t('%1 (%2)',caUcFirstUTF8Safe($t_ref_instance->getProperty('NAME_PLURAL')), sizeof($va_rows)); ?></span></a></li>
<?php
					}
				}
?>
				</ul>
<?php
			foreach($va_references as $vn_table_num => $va_rows) {
				if ($t_ref_instance = Datamodel::getInstance($vn_table_num, true)) {
					$vs_ref_table_name = $t_ref_instance->tableName();
?>
				<div id="<?php print $vs_id_prefix; ?>AuthorityReferenceTabs-<?php print $vs_ref_table_name; ?>" class="authorityReferenceListTab">	
<?php
					print "<ul class='authorityReferenceList'>\n";
				
					if (!($vs_template = caGetOption("{$vs_ref_table_name}_displayTemplate", $va_settings, null))) {
						if (is_array($vs_template = $t_instance->getAppConfig()->getList("{$vs_ref_table_name}_lookup_settings"))) {
							$vs_template = join($t_instance->getAppConfig()->get("{$vs_ref_table_name}_lookup_delimiter"), $vs_template);
						} elseif(!($vs_template = $t_instance->getAppConfig()->get("{$vs_ref_table_name}_lookup_settings"))) {
							$vs_template = "<l>^{$vs_ref_table_name}.preferred_labels</l>";
						}
					}
					
					if (strpos($vs_template, "<l>") === false) { $vs_template = "<l>{$vs_template}</l>"; }
					$va_items = caProcessTemplateForIDs($vs_template, $t_ref_instance->tableName(), array_keys($va_rows), array('returnAsArray' => true));
					
					$vn_c = 0;
					foreach($va_items as $vs_item) {
						if ($vn_c >= $vn_max_items) { break; }
						
						print "<li class='authorityReferenceList'>{$vs_item}</li>\n";
						$vn_c++;
					}
					if ((sizeof($va_items) >= $vn_max_items) && is_array($va_search_strings[$vs_ref_table_name])) {
						print "<li>".caSearchLink($this->request, _t('... and %1 more', sizeof($va_items) - $vn_max_items), '', $vs_ref_table_name, join(" OR ", $va_search_strings[$vs_ref_table_name]))."</li>\n";
					}
				
					print "</ul>\n";
?>				
				</div>
<?php
				
				
				//print "<h3>"._t('%1 (%2)', caUcFirstUTF8Safe($t_ref_instance->getProperty('NAME_PLURAL')), sizeof($va_rows))."</h3>\n";
					
				}
			}
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#<?php print $vs_id_prefix; ?>AuthorityReferenceTabs").tabs({ selected: 0 });			// Activate tabs
		});
	</script>
<?php
		} else {
?>
			<div><?php print _t('No references'); ?></div>
<?php
		}	
	}	
?>
			</div>
		</div>
	</div>
</div>