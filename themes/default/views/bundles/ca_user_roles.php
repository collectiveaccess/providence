<?php
/* ----------------------------------------------------------------------
 * bundles/ca_user_roles.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2025 Whirl-i-Gig
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
$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_instance 		= $this->getVar('t_instance');
$t_item 			= $this->getVar('t_role');			// role
$t_rel 				= $this->getVar('t_rel');			// *_x_roles instance (eg. ca_editor_uis_x_roles)
$t_subject 			= $this->getVar('t_subject');		
$settings 			= $this->getVar('settings');

$vb_read_only		= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_users') == __CA_BUNDLE_ACCESS_READONLY__));


$va_initial_values = $this->getVar('initialValues');
if (!is_array($va_initial_values)) { $va_initial_values = array(); }

print caEditorBundleShowHideControl($this->request, $vs_id_prefix);
print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $settings);

$va_role_list = $t_item->getRoleList();
?>
<div id="<?= $vs_id_prefix; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<span class="formLabelError">{error}</span>
			<table class="objectRepresentationListItem" width="90%"><?php 
					$vn_c = 0;
					if (sizeof($va_role_list)) {
						foreach($va_role_list as $vn_role_id => $va_role_info) {
							if ($vn_c == 0) { print "<tr>"; }
							print "<td width='33%'><div class='formLabel'>\n";
							print "{$va_role_info['name']}<br/>".$t_rel->htmlFormElement('access', '^ELEMENT', array('name' => $vs_id_prefix."_access_{$vn_role_id}", 'id' => "{$vs_id_prefix}_access_{$vn_role_id}", 'no_tooltips' => true, 'value' => '{{access}}'));
							print "</div></td>";
							$vn_c++;
							
							if ($vn_c == 3) {
								print "</tr>\n"; 
								$vn_c = 0;
							}
						}
						
						if ($vn_c > 0) {
							print "</tr>\n";
						}
					} else {
						print "<tr><td>"._t('No roles are available')."</td></tr>\n";
					}
				?>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class='bundleSubLabel'>
			<div class='editorBundleSortControl' style='float: right;'>	
				<?= _t('Set access: '); ?>
				<a href="#" id='<?= $vs_id_prefix; ?>ToggleEdit'><?= _t('All'); ?></a>
				<a href="#" id='<?= $vs_id_prefix; ?>ToggleNoAccess'><?= _t('None'); ?></a>
			</div>
		</div>
		<div class="caItemList">
		
		</div>
	</div>
</div>

<script type="text/javascript">
	caUI.initrolelistbundle('#<?= $vs_id_prefix; ?>', {
		fieldNamePrefix: '<?= $vs_id_prefix; ?>_',
		templateValues: ['role_id'],
		initialValues: <?= json_encode($va_initial_values); ?>,
		initialValueOrder: <?= json_encode(array_keys($va_initial_values)); ?>,
		itemID: '<?= $vs_id_prefix; ?>Item_',
		templateClassName: 'caItemTemplate',
		itemListClassName: 'caItemList',
		readonly: <?= $vb_read_only ? "true" : "false"; ?>
	});
	jQuery(document).ready(function() {
		jQuery('#<?= $vs_id_prefix; ?>ToggleEdit').on('click', function(e) {
			jQuery('#<?= $vs_id_prefix; ?> select').val(2);
			e.preventDefault();
		});
		jQuery('#<?= $vs_id_prefix; ?>ToggleNoAccess').on('click', function(e) {
			jQuery('#<?= $vs_id_prefix; ?> select').val(0);
			e.preventDefault();
		});
	});
</script>
