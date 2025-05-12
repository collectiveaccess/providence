<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_navigation.php : 
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
AssetLoadManager::register('hierBrowser');
AssetLoadManager::register('tabUI');

$t_subject 			= $this->getVar('t_subject');
$ancestors 		= $this->getVar('ancestors');
$id 				= $this->getVar('id');
$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
$lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName(), array('noInline' => 1));
$items_in_hier 	= $t_subject->getHierarchySize();
$bundle_preview	= '('.$items_in_hier. ') '. caProcessTemplateForIDs("^preferred_labels", $t_subject->tableName(), array($t_subject->getPrimaryKey()));

$bundle_settings = $this->getVar('settings');
$hier_browser_width = ($pdim = caParseElementDimension(caGetOption('width', $bundle_settings, null))) ? $pdim['expression'] : null;
$hier_browser_height = ($pdim = caParseElementDimension(caGetOption('height', $bundle_settings, null))) ? $pdim['expression'] : null;

$hier_browser_dims = [];
if ($hier_browser_width) { $hier_browser_dims[] = "width: {$hier_browser_width};"; }
if ($hier_browser_height) { $hier_browser_dims[] = "height: {$hier_browser_height};"; }
$hier_browser_dim_style = (sizeof($hier_browser_dims) > 0) ? "style='".join(" ", $hier_browser_dims)."'" : '';

$vb_objects_x_collections_hierarchy_enabled = (bool)$t_subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled');
$disabled_items_mode = $t_subject->getAppConfig()->get($t_subject->tableName() . '_hierarchy_browser_disabled_items_mode');
$disabled_items_mode = $disabled_items_mode ?: 'hide';


if (in_array($t_subject->tableName(), array('ca_objects', 'ca_collections')) && $vb_objects_x_collections_hierarchy_enabled) {
	$lookup_urls = array(
		'search' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Get'),
		'levelList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyLevel'),
		'ancestorList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyAncestorList')
	);
	$edit_url = caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Edit').'/id/';
	$init_id = $t_subject->tableName()."-".$id;
} else {
	$lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName(), array('noInline' => 1));
	$edit_url = caEditorUrl($this->request, $t_subject->tableName());
	$init_id = $id;
}

print caEditorBundleShowHideControl($this->request, $id_prefix, $bundle_settings, false, $bundle_preview);
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $bundle_settings);
?>	
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
		<div class="hierNav">
<?php
if ($id > 0) {
	$count_label = $bundle_settings['label_for_count'] ?? caGetTableDisplayName($t_subject->tableName(), true);
	?>
	<div class="hierarchyCountDisplay"><?php if($items_in_hier > 0) { print _t("Number of %1 in hierarchy: %2", $count_label, $items_in_hier); } ?></div>
	<div class="buttonPosition" <?= (isset($bundle_settings['no_close_button']) && $bundle_settings['no_close_button']) ? "style='display: none;'" : ""; ?>><a href="#" id="<?= $id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?= _t('Show in browser'); ?></span></a></div>
<?php
}

$object_collection_collection_ancestors = $this->getVar('object_collection_collection_ancestors');
$vb_do_objects_x_collections_hierarchy = false;
if ($vb_objects_x_collections_hierarchy_enabled && is_array($object_collection_collection_ancestors)) {
	$ancestors = $object_collection_collection_ancestors + $ancestors;
	$vb_do_objects_x_collections_hierarchy = true;
}
if (is_array($ancestors) && sizeof($ancestors) > 0) {
	$path = array();
	foreach($ancestors as $id => $item) {
		$item_id = $vb_do_objects_x_collections_hierarchy ? ($item['table'].'-'.$item['item_id']) : $item['item_id'];
		if($id === '') {
			$path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
		} else {
			$label = $item['label'];
			if ($id && $item[$t_subject->primaryKey()] && ($item_id != $id)) {
				$path[] = '<a href="'.caEditorUrl($this->request, $t_subject->tableName(), $id).'">'.$label.'</a>';
			} else {
				$tmp = explode("-", $item_id);
				$item_id = array_pop($tmp);
				$path[] = "<a href='#' onclick='jQuery(\"#{$id_prefix}HierarchyBrowserContainer\").slideDown(250); o{$id_prefix}HierarchyBrowser.setUpHierarchy(\"{$item_id}\"); return false;'>{$label}</a>";
			}
		}
	}
	print join(' ➔ ', $path);
}
?>
	</div><!-- end hierNav -->
</div><!-- end bundleContainer -->
<?php
if ($id > 0) {	
?>
	<div id="<?= $id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">
		<div  id="<?= $id_prefix; ?>HierarchyBrowserTabs">
			<ul>
					<li><a href="#<?= $id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?= $id_prefix; ?>ExploreHierarchyBrowser();'><span><?= _t('Explore'); ?></span></a></li>
			</ul>
	
			<div id="<?= $id_prefix; ?>HierarchyBrowserTabs-explore" class="<?= (isset($bundle_settings['hierarchy_browse_tab_class']) && $bundle_settings['hierarchy_browse_tab_class']) ? $bundle_settings['hierarchy_browse_tab_class'] : "hierarchyBrowseTab"; ?>">	
				<div class="hierarchyBrowserMessageContainer">
					<?= _t('Use the browser to explore the hierarchy. You may edit other hierarchy items by clicking on the arrows.'); ?>
				</div>
				<div id="<?= $id_prefix; ?>HierarchyBrowser" class="hierarchyBrowserSmall" <?= $hier_browser_dim_style; ?>>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
			</div>
		</div>
	</div>
<?php
}
?>
</div>
<?php
if ($id > 0) {	
?>
<script type="text/javascript">
	var o<?= $id_prefix; ?>HierarchyBrowser;
	jQuery(document).ready(function() {
		o<?= $id_prefix; ?>HierarchyBrowser = caUI.initHierBrowser('<?= $id_prefix; ?>HierarchyBrowser', {
			levelDataUrl: '<?= $lookup_urls['levelList']; ?>',
			initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
			readOnly: false,
			initItemID: '<?= $init_id; ?>',
			indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
			dontAllowEditForFirstLevel: <?= (in_array($t_subject->tableName(), array('ca_places', 'ca_storage_locations', 'ca_list_items', 'ca_relationship_types')) ? 'true' : 'false'); ?>,

			disabledItems: '<?= $disabled_items_mode; ?>',
			
			editUrl: '<?= $edit_url; ?>',
			editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
			disabledButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
			
			currentSelectionDisplayID: 'browseCurrentSelection',

			autoShrink: <?= (caGetOption('auto_shrink', $bundle_settings, false) ? 'true' : 'false'); ?>,
			autoShrinkAnimateID: '<?= $id_prefix; ?>HierarchyBrowser'
		});
		
		jQuery("#<?= $id_prefix; ?>browseToggle").click(function(e, opts) {
			var delay = (opts && opts.delay && (parseInt(opts.delay) >= 0)) ? opts.delay :  250;
			jQuery("#<?= $id_prefix; ?>HierarchyBrowserContainer").slideToggle(delay, function() { 
				jQuery("#<?= $id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?= '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?= '<span class="form-button">'._t('Show in browser').'</span>';?>');
			}); 
			return false;
		});
		
		jQuery('#<?= $id_prefix; ?>HierarchyBrowserContainer').hide(0);
		
		jQuery("#<?= $id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: 0 });					// Activate tabs
	});
<?php
	if (isset($bundle_settings['open_hierarchy']) && (bool)$bundle_settings['open_hierarchy']) {
?>
	jQuery("#<?= $id_prefix; ?>browseToggle").trigger("click", { "delay": 0 });
<?php
	}
?>
</script>
<?php
}
