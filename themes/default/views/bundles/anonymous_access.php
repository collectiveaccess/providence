<?php
/* ----------------------------------------------------------------------
 * bundles/anonymous_access.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_instance 	= $this->getVar('t_instance');
$t_rel 			= $this->getVar('t_rel');			// *_x_user_groups instance (eg. ca_sets_x_user_groups)
$t_subject 		= $this->getVar('t_subject');		
$settings 		= $this->getVar('settings');
$add_label 		= $this->getVar('add_label');
$downloads		= $this->getVar('downloads');

$read_only		= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_users') == __CA_BUNDLE_ACCESS_READONLY__));

$initial_values = $this->getVar('initialValues');
if (!is_array($initial_values)) { $initial_values = []; }
$initial_values = array_values($initial_values);

$template_values = isset($initial_values[0]) ? array_keys($initial_values[0]) : ['relation_id', 'name', 'effective_date', 'guid'];
$pawtuckets = caGetPawtucketInstalltionList();


print caEditorBundleShowHideControl($this->request, $id_prefix);
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);
?>
<div id="<?= $id_prefix; ?>">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?= $id_prefix; ?>Item_{n}" class="labelInfo">
			<table class="caListItem">
				<tr>
					<td class="formLabel">
						<?= _t('Name'); ?>
						<?= caHTMLTextInput("{$id_prefix}_name{n}", ['value' => '{{name}}', 'id' => "{$id_prefix}_name{n}"], ['width' => '340px']); ?>
						<?php if ($t_rel->hasField('effective_date')) { print _t(' for period ').$t_rel->htmlFormElement('effective_date', '^ELEMENT', ['name' => $id_prefix.'_effective_date_{n}', 'no_tooltips' => true, 'value' => '{{effective_date}}', 'classname'=> 'dateBg']); } ?>
						<?= caHTMLHiddenInput("{$id_prefix}_relation_id{n}", ['value' => '{relation_id}', 'id' => "{$id_prefix}_relation_id{n}"]); ?>
<?php
	foreach($pawtuckets as $p) {
		$url = $p['base_url'].'/Lightbox/Detail/{{guid}}';
?>
						<div class="anonymousAccessUrl" id='<?= "{$id_prefix}_url_{n}";?>'><span class="anonymousAccessUrlLabel"><?= _t('%1 link', $p['name']); ?></span>: <a href="#" target="_paw" class="developerBundleCode"><?= "{$url}"; ?></a></div>
<?php
	}
	
	if(is_array($downloads) && sizeof($downloads)) {
?>
	<div class="anonymousAccessDownloads" id='<?= "{$id_prefix}_downloads_{n}";?>'>
		<span class="anonymousAccessUrlLabel"><?= _t('Download versions'); ?></span>: 
<?php
		foreach($downloads as $d => $di) {
?>
			<?= "<input type='checkbox' name='{$id_prefix}_download_version{n}[]' id='{$id_prefix}_download_version{n}' value='{$d}' {{download_".$d."}}> ".$di['label']; ?>
<?php
		}
?>
	</div>
<?php
	}
?>
					</td>
					<td>
<?php if (!$read_only) { ?>	
						<div style="float: right;"><a href="#" class="caDeleteItemButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
<?php } ?>
					</td>
				</tr>
			</table>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
		
		</div>
<?php if (!$read_only) { ?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label ? $add_label : _t("Add anonymous access link"); ?></a></div>
<?php } ?>
	</div>
</div>
			
<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initRelationBundle('#<?= $id_prefix; ?>', {
			fieldNamePrefix: '<?= $id_prefix; ?>_',
			templateValues: <?= json_encode($template_values); ?>,
			initialValues: <?= json_encode($initial_values); ?>,
			initialValueOrder: <?= json_encode(array_keys($initial_values)); ?>,
			itemID: '<?= $id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			readonly: <?= $read_only ? "true" : "false"; ?>,
			hideOnNewIDList: <?= json_encode(["{$id_prefix}_url_"]); ?>,
			autocompleteUrl: '<?= caNavUrl($this->request, 'lookup', 'User', 'Get', array()); ?>',
		});
	});
</script>
