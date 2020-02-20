<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_navigation.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2019 Whirl-i-Gig
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
	$pa_ancestors 		= $this->getVar('ancestors');
	$pn_id 				= $this->getVar('id');
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$va_lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName(), array('noInline' => 1));
	$vn_items_in_hier 	= $t_subject->getHierarchySize();
	$vs_bundle_preview	= '('.$vn_items_in_hier. ') '. caProcessTemplateForIDs("^preferred_labels", $t_subject->tableName(), array($t_subject->getPrimaryKey()));
	
	$pa_bundle_settings = $this->getVar('settings');
	$hier_browser_width = ($pdim = caParseElementDimension(caGetOption('width', $pa_bundle_settings, null))) ? $pdim['expression'] : null;
	$hier_browser_height = ($pdim = caParseElementDimension(caGetOption('height', $pa_bundle_settings, null))) ? $pdim['expression'] : null;
	
	$hier_browser_dims = [];
	if ($hier_browser_width) { $hier_browser_dims[] = "width: {$hier_browser_width};"; }
	if ($hier_browser_height) { $hier_browser_dims[] = "height: {$hier_browser_height};"; }
	$hier_browser_dim_style = (sizeof($hier_browser_dims) > 0) ? "style='".join(" ", $hier_browser_dims)."'" : '';
	
	$vb_objects_x_collections_hierarchy_enabled = (bool)$t_subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled');
	
	
	if (in_array($t_subject->tableName(), array('ca_objects', 'ca_collections')) && $vb_objects_x_collections_hierarchy_enabled) {
		$va_lookup_urls = array(
			'search' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Get'),
			'levelList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyLevel'),
			'ancestorList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyAncestorList')
		);
		$vs_edit_url = caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Edit').'/id/';
		$vn_init_id = $t_subject->tableName()."-".$pn_id;
	} else {
		$va_lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName(), array('noInline' => 1));
		$vs_edit_url = caEditorUrl($this->request, $t_subject->tableName());
		$vn_init_id = $pn_id;
	}
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $pa_bundle_settings, false, $vs_bundle_preview);
	print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $va_settings);
?>	
	<div id="<?php print $vs_id_prefix; ?>">
		<div class="bundleContainer">
			<div class="hierNav">
<?php
	if ($pn_id > 0) {
?>
		<div class="hierarchyCountDisplay"><?php if($vn_items_in_hier > 0) { print _t("Number of %1 in hierarchy: %2", caGetTableDisplayName($t_subject->tableName(), true), $vn_items_in_hier); } ?></div>
		<div class="buttonPosition" <?php print (isset($pa_bundle_settings['no_close_button']) && $pa_bundle_settings['no_close_button']) ? "style='display: none;'" : ""; ?>><a href="#" id="<?php print $vs_id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?php print _t('Show in browser'); ?></span></a></div>
<?php
	}
	
	$va_object_collection_collection_ancestors = $this->getVar('object_collection_collection_ancestors');
	$vb_do_objects_x_collections_hierarchy = false;
	if ($vb_objects_x_collections_hierarchy_enabled && is_array($va_object_collection_collection_ancestors)) {
		$pa_ancestors = $va_object_collection_collection_ancestors + $pa_ancestors;
		$vb_do_objects_x_collections_hierarchy = true;
	}
	if (is_array($pa_ancestors) && sizeof($pa_ancestors) > 0) {
		$va_path = array();
		foreach($pa_ancestors as $vn_id => $va_item) {
			$vs_item_id = $vb_do_objects_x_collections_hierarchy ? ($va_item['table'].'-'.$va_item['item_id']) : $va_item['item_id'];
			if($vn_id === '') {
				$va_path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
			} else {
				$vs_label = $va_item['label'];
				if ($pn_id && $va_item[$t_subject->primaryKey()] && ($vs_item_id != $pn_id)) {
					$va_path[] = '<a href="'.caEditorUrl($this->request, $t_subject->tableName(), $vn_id).'">'.$vs_label.'</a>';
				} else {
					$va_tmp = explode("-", $vs_item_id);
					$vn_item_id = array_pop($va_tmp);
					$va_path[] = "<a href='#' onclick='jQuery(\"#{$vs_id_prefix}HierarchyBrowserContainer\").slideDown(250); o{$vs_id_prefix}HierarchyBrowser.setUpHierarchy(\"{$vn_item_id}\"); return false;'>{$vs_label}</a>";
				}
			}
		}
		print join(' ➔ ', $va_path);
	}
?>
		</div><!-- end hierNav -->
	</div><!-- end bundleContainer -->
<?php
	if ($pn_id > 0) {	
?>
		<div id="<?php print $vs_id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">
			<div  id="<?php print $vs_id_prefix; ?>HierarchyBrowserTabs">
				<ul>
						<li><a href="#<?php print $vs_id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?php print $vs_id_prefix; ?>ExploreHierarchyBrowser();'><span><?php print _t('Explore'); ?></span></a></li>
				</ul>
		
				<div id="<?php print $vs_id_prefix; ?>HierarchyBrowserTabs-explore" class="<?php print (isset($pa_bundle_settings['hierarchy_browse_tab_class']) && $pa_bundle_settings['hierarchy_browse_tab_class']) ? $pa_bundle_settings['hierarchy_browse_tab_class'] : "hierarchyBrowseTab"; ?>">	
					<div class="hierarchyBrowserMessageContainer">
						<?php print _t('Use the browser to explore the hierarchy. You may edit other hierarchy items by clicking on the arrows.'); ?>
					</div>
					<div id="<?php print $vs_id_prefix; ?>HierarchyBrowser" class="hierarchyBrowserSmall" <?php print $hier_browser_dim_style; ?>>
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
	if ($pn_id > 0) {	
?>
	<script type="text/javascript">
		var o<?php print $vs_id_prefix; ?>HierarchyBrowser;
		jQuery(document).ready(function() {
			o<?php print $vs_id_prefix; ?>HierarchyBrowser = caUI.initHierBrowser('<?php print $vs_id_prefix; ?>HierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				readOnly: false,
				initItemID: '<?php print $vn_init_id; ?>',
				indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				dontAllowEditForFirstLevel: <?php print (in_array($t_subject->tableName(), array('ca_places', 'ca_storage_locations', 'ca_list_items', 'ca_relationship_types')) ? 'true' : 'false'); ?>,

				disabledItems: '<?php print $vs_disabled_items_mode; ?>',
				
				editUrl: '<?php print $vs_edit_url; ?>',
				editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
				disabledButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
				
				currentSelectionDisplayID: 'browseCurrentSelection',

				autoShrink: <?php print (caGetOption('auto_shrink', $pa_bundle_settings, false) ? 'true' : 'false'); ?>,
				autoShrinkAnimateID: '<?php print $vs_id_prefix; ?>HierarchyBrowser'
			});
			
			jQuery("#<?php print $vs_id_prefix; ?>browseToggle").click(function(e, opts) {
				var delay = (opts && opts.delay && (parseInt(opts.delay) >= 0)) ? opts.delay :  250;
				jQuery("#<?php print $vs_id_prefix; ?>HierarchyBrowserContainer").slideToggle(delay, function() { 
					jQuery("#<?php print $vs_id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?php print '<span class="form-button">'._t('Show in browser').'</span>';?>');
				}); 
				return false;
			});
			
			jQuery('#<?php print $vs_id_prefix; ?>HierarchyBrowserContainer').hide(0);
			
			jQuery("#<?php print $vs_id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: 0 });					// Activate tabs
		});
<?php
	if (isset($pa_bundle_settings['open_hierarchy']) && (bool)$pa_bundle_settings['open_hierarchy']) {
?>
		jQuery("#<?php print $vs_id_prefix; ?>browseToggle").trigger("click", { "delay": 0 });
<?php
	}
?>
	</script>
<?php
	}
?>
