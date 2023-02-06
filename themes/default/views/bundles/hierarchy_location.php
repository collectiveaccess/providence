<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_location.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2022 Whirl-i-Gig
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
	$vs_subject_label	= $t_subject->getLabelForDisplay();
	$subject_table 		= $t_subject->tableName();
	
	if (($vs_priv_table = $subject_table) == 'ca_list_items') { $vs_priv_table = 'ca_lists'; }		// actions happen to be on names for ca_lists for ca_list_items
	
	$vb_batch 			= $this->getVar('batch');
	$pn_parent_id 		= $this->getVar('parent_id');
	$pa_ancestors 		= $this->getVar('ancestors');
	$pn_id 				= $this->getVar('id');
	$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_items_in_hier 	= $t_subject->getHierarchySize();
	$vs_bundle_preview	= '('.$vn_items_in_hier. ') '. caProcessTemplateForIDs("^preferred_labels", $subject_table, array($t_subject->getPrimaryKey()));
	
	$config = $t_subject->getAppConfig();
	
	if(!($min_autocomplete_search_length = (int)$config->get(["{$vs_priv_table}_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]))) { $min_autocomplete_search_length = 3; }

	if (!$pn_id && $vb_batch) {
		switch($t_subject->getProperty('HIERARCHY_TYPE')) {
			case __CA_HIER_TYPE_ADHOC_MONO__:
				// For batching on ad-hoc hierarchies we need to load something, so we pick the first record we can find
				if($t_subject = $subject_table::findAsInstance('*', ['limit' => 1, 'returnAs' => 'ids'])) {
					$pn_id = $t_subject->getPrimaryKey();
				}
				break;
			case __CA_HIER_TYPE_SIMPLE_MONO__:
				$pn_id = $t_subject->getHierarchyRootID();
				break;
		}
	}
	
	switch($vs_priv_table) {
		case 'ca_relationship_types':
			$vb_has_privs = $this->request->user->canDoAction('can_configure_relationship_types');
			break;
		case 'ca_tour_stops':
			$vb_has_privs = $this->request->user->canDoAction('can_create_ca_tours');
			break;
		case 'ca_sets':
			$vb_has_privs = $this->request->user->canDoAction('can_create_sets');
			break;
		default:
			$vb_has_privs = $this->request->user->canDoAction('can_create_'.$vs_priv_table);
			break;
	}

	$vb_objects_x_collections_hierarchy_enabled = (bool)$config->get('ca_objects_x_collections_hierarchy_enabled');
	$vs_disabled_items_mode = $config->get($subject_table . '_hierarchy_browser_disabled_items_mode');
	$vs_disabled_items_mode = $vs_disabled_items_mode ? $vs_disabled_items_mode : 'hide';
	$t_object = new ca_objects();

	$pa_bundle_settings = $this->getVar('settings');

	$va_search_lookup_extra_params = array('noInline' => 1);
	if ($t_subject->getProperty('HIERARCHY_ID_FLD') && ($vn_hier_id = (int)$t_subject->get($t_subject->getProperty('HIERARCHY_ID_FLD')))) {
		$va_search_lookup_extra_params['currentHierarchyOnly'] = $vn_hier_id;
	}
	if (isset($pa_bundle_settings['restrict_to_types'])) {
		$va_search_lookup_extra_params['types'] = $pa_bundle_settings['restrict_to_types'];
	}

	if (in_array($subject_table, array('ca_objects', 'ca_collections')) && $vb_objects_x_collections_hierarchy_enabled) {
		$lookup_urls_for_move = $lookup_urls = array(
			'search' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Get', $va_search_lookup_extra_params),
			'levelList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyLevel'),
			'ancestorList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyAncestorList'),
			'sortSave' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'SetSortOrder')
		);
		$lookup_urls_for_move['search'] = caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Get', array_merge($va_search_lookup_extra_params, ['currentHierarchyOnly' => null]));
		
		$vs_edit_url = caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Edit').'/id/';
		$vn_init_id = $subject_table."-".$pn_id;
	} else {
		$lookup_urls 			= caJSONLookupServiceUrl($this->request, $subject_table, $va_search_lookup_extra_params);
		$lookup_urls_for_move 	= caJSONLookupServiceUrl($this->request, $subject_table, array_merge($va_search_lookup_extra_params, ['currentHierarchyOnly' => null]));
		$vs_edit_url = caEditorUrl($this->request, $subject_table);
		$vn_init_id = $pn_id;
	}

	$strict_type_hierarchy = $this->request->config->get($subject_table.'_enforce_strict_type_hierarchy');
	$vs_type_selector 	= trim($t_subject->getTypeListAsHTMLFormElement("{$id_prefix}type_id", array('id' => "{$id_prefix}typeList"), array('childrenOfCurrentTypeOnly' => (bool)$strict_type_hierarchy, 'includeSelf' => !(bool)$strict_type_hierarchy, 'directChildrenOnly' => ((bool)$strict_type_hierarchy && ($strict_type_hierarchy !== '~')), 'restrictToTypes' => $pa_bundle_settings['restrict_to_types'] ?? null)));

	$vb_read_only		=	((isset($pa_bundle_settings['readonly']) && $pa_bundle_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($subject_table, 'hierarchy_location') == __CA_BUNDLE_ACCESS_READONLY__));
	
	$show_add = (!$vb_read_only && $vb_has_privs && !$vb_batch) && (!$strict_type_hierarchy || ($strict_type_hierarchy && $vs_type_selector));
	$show_move = ((!$strict_type_hierarchy || ($strict_type_hierarchy === '~') || $vb_batch) && !$vb_read_only);
	
	$show_add_object = (!$vb_read_only && $vb_has_privs && !$vb_batch) && $vb_objects_x_collections_hierarchy_enabled && ($subject_table == 'ca_collections') && (((!$strict_type_hierarchy || ($strict_type_hierarchy === '~'))) || (($strict_type_hierarchy && ($strict_type_hierarchy !== '~')) && (!sizeof($t_subject->getTypeInstance()->get('ca_list_items.children.item_id', ['returnAsArray' => true])))));
	
	// Tab to open on load?
	$default_tab_index = 0;
	
	$movement_action_errors = $this->request->getActionErrors();
	if($subject_table === 'ca_storage_locations') {
		$movement_action_errors = array_filter($movement_action_errors, function($v) {
			return preg_match("!^ca_movements\.!", $v->getErrorSource());
		});
		
		if((sizeof($movement_action_errors) > 0) && !$vb_batch && $show_move) {
			$default_tab_index = 1;	
		}
		
		// Set parent to previously selected
		if($new_parent_id = $this->request->getParameter($id_prefix.'_new_parent_id', pInteger)) {
			$pn_parent_id = $new_parent_id;
			$vn_init_id = $new_parent_id;
		}
	}
	
	$hier_browser_width = ($pdim = caParseElementDimension(caGetOption('width', $pa_bundle_settings, null))) ? $pdim['expression'] : null;
	$hier_browser_height = ($pdim = caParseElementDimension(caGetOption('height', $pa_bundle_settings, null))) ? $pdim['expression'] : null;
	
	$hier_browser_dims = [];
	if ($hier_browser_width) { $hier_browser_dims[] = "width: {$hier_browser_width};"; }
	if ($hier_browser_height) { $hier_browser_dims[] = "height: {$hier_browser_height};"; }
	$hier_browser_dim_style = (sizeof($hier_browser_dims) > 0) ? "style='".join(" ", $hier_browser_dims)."'" : '';
		
	$va_errors = array();
	if(is_array($va_action_errors = $this->request->getActionErrors('hierarchy_location'))) {
		foreach($va_action_errors as $o_error) {
			$va_errors[] = $o_error->getErrorDescription();
		}
	}
	
	$path = [];
	$va_object_collection_collection_ancestors = $this->getVar('object_collection_collection_ancestors');
	$vb_do_objects_x_collections_hierarchy = false;
	if ($vb_objects_x_collections_hierarchy_enabled && is_array($va_object_collection_collection_ancestors)) {
		$pa_ancestors = $va_object_collection_collection_ancestors + $pa_ancestors;
		$vb_do_objects_x_collections_hierarchy = true;
		$show_move = true;
	}

	if (is_array($pa_ancestors) && sizeof($pa_ancestors) > 0) {
		$template_values = null;
		if(!($template = $pa_bundle_settings['headerDisplayTemplate'] ?? null)) { 
			$template = $config->get("{$subject_table}_hierarchy_browser_header_settings");
		}
		if($template) {
			$template_values = caProcessTemplateForIDs($template, $subject_table, array_keys($pa_ancestors), ['returnAsArray' => true, 'indexWithIDs' => true]);
		}
		foreach($pa_ancestors as $vn_id => $va_item) {
			$va_item['table'] = $va_item['table'] ?? null;
			
			$vs_item_id = $vb_do_objects_x_collections_hierarchy ? ($va_item['table'].'-'.($va_item['item_id'] ?? null)) : ($va_item['item_id'] ?? null);
			if($vn_id === '') {
				$path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
			} else {
				$vs_label = $template_values[$vn_id] ?? $va_item['label'];
				if (($va_item['table'] != $subject_table) || ($va_item['item_id'] && ($va_item['item_id'] != $pn_id))) {
					$path[] = '<a href="'.caEditorUrl($this->request, $va_item['table'], $va_item['item_id']).'">'.$vs_label.'</a>';
				} else {
					$path[] = "<a href='#' onclick='jQuery(\"#{$id_prefix}HierarchyBrowserContainer\").slideDown(250); o{$id_prefix}ExploreHierarchyBrowser.setUpHierarchy(\"{$vs_item_id}\"); return false;'>{$vs_label}</a>";
				}
			}
		}
	}
?>	
<script type="text/javascript">
	//
	// Handle browse header scrolling
	//
	jQuery(document).ready(function() {
		if (jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').width() > jQuery('#<?= $id_prefix; ?>HierarchyPanelHeader').width()) {
			//
			// The content is wider than the content viewer area so set up the scroll
			//
			
			jQuery('#<?= $id_prefix; ?>NextPrevControls').show(); // show controls
			
			var hierarchyHeaderContentWidth = jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').width();
			var hierarchyPanelHeaderWidth = jQuery('#<?= $id_prefix; ?>HierarchyPanelHeader').width();
			
			if (hierarchyHeaderContentWidth > hierarchyPanelHeaderWidth) {
				jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left', ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1) + "px"); // start at right side
			}
			
			//
			// Handle click on "specific" button
			//
			jQuery('#<?= $id_prefix; ?>NextControl').click(function() {		
				if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) >  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
					//
					// If we're not already at the right boundary then scroll right
					//
					var dl = (parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left')) - hierarchyPanelHeaderWidth);
					if (dl < ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) { dl = ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1); }
					jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px" }, { duration: 300, easing: 'swing', queue: false, complete: function() {			
						if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) <=  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?= $id_prefix; ?>NextControl').hide();
						}
						if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
							jQuery('#<?= $id_prefix; ?>PrevControl').show();
						} 
					}}); 
				}
				return false;
			});
			
			//
			// Handle click on "broader" button
			// 
			jQuery('#<?= $id_prefix; ?>PrevControl').click(function() {			
				if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
					//
					// Only scroll if we're not showing the extreme left side of the content area already.
					//
					var dl = parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left')) + hierarchyPanelHeaderWidth;
					if (dl > 0) { dl = 0; }
					jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px"}, { duration: 300, easing: 'swing', queue: false, complete: function() {
						if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) >= 0) {
							jQuery('#<?= $id_prefix; ?>PrevControl').hide();
						} 
						if ((parseInt(jQuery('#<?= $id_prefix; ?>HierarchyHeaderContent').css('left'))) > ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?= $id_prefix; ?>NextControl').show();
						}
					}}); 
				}
				return false;
			});
			jQuery('#<?= $id_prefix; ?>NextControl').hide();
		} else {
			// 
			// Everything can fit without scrolling so hide the controls
			//
			jQuery('#<?= $id_prefix; ?>NextPrevControls').hide();
		}
	});
</script>
<?php
	if ($vb_batch) {
		print caBatchEditorIntrinsicModeControl($t_subject, $id_prefix);
	} else {
		print caEditorBundleShowHideControl($this->request, $id_prefix, $pa_bundle_settings, false, $vs_bundle_preview);
	}
	print caEditorBundleMetadataDictionary($this->request, $id_prefix, $pa_bundle_settings);
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer">
<?php	
	if (!$vb_batch) {
?>
		<div class="hierNav" >
<?php
			if(sizeof($va_errors)) {
				print "<div class='formLabel'><span class='formLabelError'>".join('; ', $va_errors)."</span></div>\n";
			}
	
			if (!$vb_batch && ($pn_id > 0)) {
				$vs_count_label = caExtractSettingsValueByUserLocale('label_for_count', $pa_bundle_settings) ?? caGetTableDisplayName($subject_table, true);

?>
				<div class="hierarchyCountDisplay"><?php if($vn_items_in_hier > 0) { print _t("Number of %1 in hierarchy: %2", $vs_count_label, $vn_items_in_hier); } ?></div>
				<div class="buttonPosition">
					<a href="#" id="<?= $id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?= _t('Show Hierarchy'); ?></span></a>
				</div>			
<?php	
			}

				print '<div id="'.$id_prefix.'HierarchyPanelHeader" class="hierarchyPanelHeader"><div id="'.$id_prefix.'HierarchyHeaderContent" class="hierarchyHeaderContent">';
			
				
				print join(' ➔ ', $path);
				print '</div></div>';
?>
				<div id="<?= $id_prefix; ?>HierarchyHeaderScrollButtons" class="hierarchyHeaderScrollButtons">
					<div id="<?= $id_prefix; ?>NextPrevControls" class="nextPrevControls"><a href="#" id="<?= $id_prefix; ?>PrevControl" class="prevControl">&larr;</a> <a href="#" id="<?= $id_prefix; ?>NextControl" class="nextControl">&rarr;</a></div>
				</div>
	</div><!-- end hiernav -->
<?php
	}
	if (($pn_id > 0) || $vb_batch) {
?>
		<div id="<?= $id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">		
			<div  id="<?= $id_prefix; ?>HierarchyBrowserTabs">
				<ul>
<?php
	if (!$vb_batch) {
?>
					<li><a href="#<?= $id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?= $id_prefix; ?>ExploreHierarchyBrowser();'><span><?= _t('Explore'); ?></span></a></li>
<?php	
	}
	if ($show_move) {
?>
					<li><a href="#<?= $id_prefix; ?>HierarchyBrowserTabs-move" onclick='_init<?= $id_prefix; ?>MoveHierarchyBrowser();'><span><?= _t('Move'); ?></span></a></li>
<?php
	}
	if ($show_add) {
?>
					<li><a href="#<?= $id_prefix; ?>HierarchyBrowserTabs-add" onclick='_init<?= $id_prefix; ?>AddHierarchyBrowser();'><span><?= ($vb_objects_x_collections_hierarchy_enabled && ($subject_table == 'ca_collections')) ? _t('Add level') : _t('Add'); ?></span></a></li>
<?php
	}
	if ($show_add_object) {
?>
					<li><a href="#<?= $id_prefix; ?>HierarchyBrowserTabs-addObject" onclick='_init<?= $id_prefix; ?>AddObjectHierarchyBrowser();'><span><?= _t('Add object'); ?></span></a></li>
<?php
	}
?>
				</ul>
<?php
	if (!$vb_batch) {
?>
				<div id="<?= $id_prefix; ?>HierarchyBrowserTabs-explore" class="hierarchyBrowseTab">	
					<div class="hierarchyBrowserFind">
						<?= _t('Find'); ?>: <input type="text" id="<?= $id_prefix; ?>ExploreHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
							<?= _t('Click %1 names to explore. Click on an arrow icon to open a %1 for editing.', $t_subject->getProperty('NAME_SINGULAR')); ?>
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?= $id_prefix; ?>ExploreHierarchyBrowser" class="hierarchyBrowserSmall" <?= $hier_browser_dim_style; ?>>
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
				</div>
<?php
	}
	if ($show_move) {
?>
				<div id="<?= $id_prefix; ?>HierarchyBrowserTabs-move" class="hierarchyBrowseTab">
					<div class="hierarchyBrowserFind">
						<?= _t('Find'); ?>: <input type="text" id="<?= $id_prefix; ?>MoveHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
						<?= _t('Click on an arrow to choose the location to move this record under.', $t_subject->getProperty('NAME_SINGULAR')); ?>
						<div id='<?= $id_prefix; ?>HierarchyBrowserSelectionMessage' class='hierarchyBrowserNewLocationMessage'><!-- Message specifying move destination is dynamically inserted here by ca.hierbrowser --></div>	
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?= $id_prefix; ?>MoveHierarchyBrowser" class="hierarchyBrowserSmall" <?= $hier_browser_dim_style; ?>>
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->				
<?php

		// TODO:
		//	
		//	Make this form conditional on current location policy
		//
		if (($subject_table == 'ca_storage_locations') && (bool)$config->get('record_movement_information_when_moving_storage_location')) {
			if ($t_ui = ca_editor_uis::loadDefaultUI('ca_movements', $this->request, null, array('editorPref' => 'quickadd'))) {
				//
				// Add movement form
				//
?>
			<div id="<?= $id_prefix; ?>StorageLocationMovementForm" style="width: 98%; margin: 5px 0px 2px 6px;">
				<h3><?= _t('Movement details'); ?></h3>
<?php
				$va_nav = $t_ui->getScreensAsNavConfigFragment($this->request, null, $this->request->getModulePath(), $this->request->getController(), $this->request->getAction(),
					[],
					[]
				);
	
				$t_movement = new ca_movements();
				$va_form_elements = $t_movement->getBundleFormHTMLForScreen($va_nav['defaultScreen'], array(
						'request' => $this->request, 
						'formName' => $id_prefix.'StorageLocationMovementForm',
						'omit' => ['ca_storage_locations', 'ca_objects']
				));
				print caHTMLHiddenInput($id_prefix.'_movement_screen', array('value' => $va_nav['defaultScreen']));
				print caHTMLHiddenInput($id_prefix.'_movement_form_name', array('value' => $id_prefix.'StorageLocationMovementForm'));
			
				print join("\n", $va_form_elements);
?>
			</div>
			<script type="text/javascript">
				jQuery("#<?= $id_prefix; ?>StorageLocationMovementForm textarea, #<?= $id_prefix; ?>StorageLocationMovementForm input").css("max-width", "600px");
			</script>
<?php
			}
		}
?>
		</div>
<?php
	}
	
	if ($show_add) {
?>
			<div id="<?= $id_prefix; ?>HierarchyBrowserTabs-add"  class="hierarchyBrowseTab">
				<div class="hierarchyBrowserMessageContainer">
					<?= _t('Use the controls below to create new %1 relative to this record in the hierarchy.', $t_subject->getProperty('NAME_PLURAL'), $vs_subject_label); ?>
				</div>
				
				<div id='<?= $id_prefix; ?>HierarchyBrowseTypeMenu' style="margin-top: 15px;">
					<div style="float: left; width: 700px">
<?php
						$va_add_types = array(_t('under (child)') => 'under');
						
						if (!$strict_type_hierarchy) { $va_add_types[_t('above (parent)')] = 'above'; }
						if (($pn_parent_id > 0) && !$strict_type_hierarchy) { $va_add_types[_t('next to (sibling)')] = 'next_to'; }
						
						if ($vs_type_selector) {
							// for items that take types
							print "<div id='{$id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>", $vs_type_selector, caHTMLSelect('add_type', $va_add_types, array('id' => "{$id_prefix}addType"), ['value' => Session::getVar('default_hierarchy_add_mode')]), $vs_subject_label);
		
							// Note the jQuery(\"#{$id_prefix}childTypeList\").val() which grabs the value of the type
							print " <a href='#' onclick='_navigateToNewForm(jQuery(\"#{$id_prefix}typeList\").val(), jQuery(\"#{$id_prefix}addType\").val(), (jQuery(\"#{$id_prefix}addType\").val() == \"next_to\") ? ".intval($pn_parent_id)." : ".intval($pn_id).",".intval($pn_id).")'>".caNavIcon(__CA_NAV_ICON_ADD__, '15px')."</a></div>";
						} else {
							// for items without types
							print "<div id='{$id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>",  $t_subject->getProperty('NAME_SINGULAR'), caHTMLSelect('add_type', $va_add_types, array('id' => "{$id_prefix}addType"), ['value' => Session::getVar('default_hierarchy_add_mode')]), $vs_subject_label);
							print " <a href='#' onclick='_navigateToNewForm(0, jQuery(\"#{$id_prefix}addType\").val(), (jQuery(\"#{$id_prefix}addType\").val() == \"next_to\") ? ".intval($pn_parent_id)." : ".intval($pn_id).", ".intval($pn_id).")'>".caNavIcon(__CA_NAV_ICON_ADD__, '15px')."</a></div>";
						}
?>
					</div>
				</div>
				<div class="clear"><!-- empty --></div>
				<div id="<?= $id_prefix; ?>AddHierarchyBrowser" class="hierarchyBrowserSmall" <?= $hier_browser_dim_style; ?>>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
		</div>
<?php
	}
	if ($show_add_object) {
?>
			<div id="<?= $id_prefix; ?>HierarchyBrowserTabs-addObject"  class="hierarchyBrowseTab">
				<div class="hierarchyBrowserMessageContainer">
					<?= _t('Use the controls below to create new %1 relative to this %2 in the hierarchy.', $t_object->getProperty('NAME_PLURAL'), mb_strtolower($t_subject->getTypeName())); ?>
				</div>
				
				<div id='<?= $id_prefix; ?>AddObjectHierarchyBrowseTypeMenu' style="margin-top: 15px;">
					<div style="float: left; width: 700px">
<?php
							print "<div id='{$id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 under <em>%2</em>", $this->getVar('objectTypeList'), $vs_subject_label);
		
							// Note the jQuery(\"#{$id_prefix}childTypeList\").val() which grabs the value of the type
							print " <a href='#' onclick='_navigateToNewObjectForm(jQuery(\"#{$id_prefix}objectTypeList\").val(), ".intval($pn_id).")'>".caNavIcon(__CA_NAV_ICON_ADD__, '15px')."</a></div>";				
?>
					</div>
				</div>
				<div class="clear"><!-- empty --></div>
				<div id="<?= $id_prefix; ?>AddObjectHierarchyBrowser" class="hierarchyBrowserSmall" <?= $hier_browser_dim_style; ?>>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
		</div>
<?php
	}
?>
		</div>
	</div>
	<input type='hidden' name='<?= $id_prefix; ?>_new_parent_id' id='<?= $id_prefix; ?>_new_parent_id' value='<?= $pn_parent_id; ?>'/>
	<input type='hidden' name='<?= $id_prefix; ?>_move_selection' id='<?= $id_prefix; ?>_move_selection' value=''/>

<script type="text/javascript">
	jQuery(document).ready(function() {	
<?php
	if ($show_move) {
?>
		// Set up "move" hierarchy browse search
		jQuery('#<?= $id_prefix; ?>MoveHierarchyBrowserSearch').autocomplete(
			{ 
				source: '<?= $lookup_urls_for_move['search']; ?>', minLength: <?= $min_autocomplete_search_length; ?>, delay: 800, html: true, noInline: true,
				select: function( event, ui ) {
					if (ui.item.id) {
						jQuery("#<?= $id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
						o<?= $id_prefix; ?>MoveHierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
					}
					event.preventDefault();
					jQuery('#<?= $id_prefix; ?>MoveHierarchyBrowserSearch').val('');
				}
			}
		).click(function() { this.select() });
<?php
	}
?>

		jQuery("#<?= $id_prefix; ?>browseToggle").click(function(e, opts) {
			_init<?= $id_prefix; ?>ExploreHierarchyBrowser();
			var delay = (opts && opts.delay && (parseInt(opts.delay) >= 0)) ? opts.delay :  250;
			jQuery("#<?= $id_prefix; ?>HierarchyBrowserContainer").slideToggle(delay, function() { 
				jQuery("#<?= $id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?= '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?= '<span class="form-button">'._t('Show Hierarchy').'</span>';?>');
			}); 
			return false;
		});

<?php
	if (!$vb_batch) {
?>
		// Set up "explore" hierarchy browse search
		jQuery('#<?= $id_prefix; ?>ExploreHierarchyBrowserSearch').autocomplete(
			{
				source: '<?= $lookup_urls['search']; ?>', minLength: <?= $min_autocomplete_search_length; ?>, delay: 800, html: true, noInline: true,
				select: function( event, ui ) {
					if (ui.item.id) {
						jQuery("#<?= $id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
						o<?= $id_prefix; ?>ExploreHierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
					}
					event.preventDefault();
					jQuery('#<?= $id_prefix; ?>ExploreHierarchyBrowserSearch').val('');
				}
			}
		).click(function() { this.select() });
		
		// Disable form change warnings to add type drop-downs
		jQuery('#<?= $id_prefix; ?>HierarchyBrowseAddUnder select').unbind('change');
		jQuery('#<?= $id_prefix; ?>HierarchyBrowseAddNextTo select').unbind('change');		
<?php
	}
?>		
		jQuery("#<?= $id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: <?= (int)$default_tab_index; ?> });		// Activate tabs
		jQuery('#<?= $id_prefix; ?>HierarchyBrowserContainer').hide(0);												// Hide extended options
	});

<?php
	if (!$vb_batch) {
?>	
	if (typeof  _navigateToNewForm != 'function') {
		function _navigateToNewForm(type_id, action, id, after_id) {
			if(!type_id) { type_id = ''; }
			switch(action) {
				case 'above':
					if(!id) { break; }
					document.location = '<?= caEditorUrl($this->request, $subject_table, 0); ?>' + (type_id ? '/type_id/' + type_id : '') + '/above_id/' + id;
					break;
				case 'under':
					if(!id) { break; }
					document.location = '<?= caEditorUrl($this->request, $subject_table, 0); ?>' + (type_id ? '/type_id/' + type_id : '') + '/parent_id/' + id;
					break;
				case 'next_to':
					if(!after_id) { break; }
					document.location = '<?= caEditorUrl($this->request, $subject_table, 0); ?>' + (type_id ? '/type_id/' + type_id : '') + '/parent_id/' + id + '/after_id/' + after_id;
					break;
				default:
					alert("Invalid action!");
					break;
			}
		}
	}
	if (typeof  _navigateToNewObjectForm != 'function') {
		function _navigateToNewObjectForm(type_id, parent_collection_id) {
			document.location = '<?= caEditorUrl($this->request, "ca_objects", 0); ?>/type_id/' + type_id + '/collection_id/' + parent_collection_id;
		}
	}
	
	// Set up "explore" hierarchy browser
	var o<?= $id_prefix; ?>ExploreHierarchyBrowser = null;
	
	function _init<?= $id_prefix; ?>ExploreHierarchyBrowser() {
		if (!o<?= $id_prefix; ?>ExploreHierarchyBrowser) {
			o<?= $id_prefix; ?>ExploreHierarchyBrowser = caUI.initHierBrowser('<?= $id_prefix; ?>ExploreHierarchyBrowser', {
				levelDataUrl: '<?= $lookup_urls['levelList']; ?>',
				initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
				
				dontAllowEditForFirstLevel: <?= (in_array($subject_table, array('ca_places', 'ca_storage_locations', 'ca_list_items', 'ca_relationship_types')) ? 'true' : 'false'); ?>,
				
				readOnly: false, //<?= $vb_read_only ? 1 : 0; ?>,
				disabledItems: '<?= $vs_disabled_items_mode; ?>',
				
				editUrl: '<?= $vs_edit_url; ?>',
				editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
				disabledButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
				
				allowDragAndDropSorting: <?= caDragAndDropSortingForHierarchyEnabled($this->request, $subject_table, $t_subject->getPrimaryKey()) ? "true" : "false"; ?>,
				sortSaveUrl: '<?= $lookup_urls['sortSave']; ?>',
				dontAllowDragAndDropSortForFirstLevel: true,

				initItemID: '<?= $vn_init_id; ?>',
				indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				displayCurrentSelectionOnLoad: false,
				autoShrink: <?= (caGetOption('auto_shrink', $pa_bundle_settings, false) ? 'true' : 'false'); ?>,
				autoShrinkAnimateID: '<?= $id_prefix; ?>ExploreHierarchyBrowser'
			});
		}
	}

<?php
	}
	if ($show_move) {
?>
	// Set up "move" hierarchy browser
	var o<?= $id_prefix; ?>MoveHierarchyBrowser = null;
	function _init<?= $id_prefix; ?>MoveHierarchyBrowser() {
		if (!o<?= $id_prefix; ?>MoveHierarchyBrowser) {
			o<?= $id_prefix; ?>MoveHierarchyBrowser = caUI.initHierBrowser('<?= $id_prefix; ?>MoveHierarchyBrowser', {
				levelDataUrl: '<?= $lookup_urls['levelList']; ?>',
				initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
				
				readOnly: <?= $vb_read_only ? 1 : 0; ?>,
				disabledItems: '<?= $vs_disabled_items_mode; ?>',
				
				initItemID: '<?= $vn_init_id; ?>',
				indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
				disabledButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",
						
				allowDragAndDropSorting: <?= caDragAndDropSortingForHierarchyEnabled($this->request, $subject_table, $t_subject->getPrimaryKey()) ? "true" : "false"; ?>,
				sortSaveUrl: '<?= $lookup_urls['sortSave']; ?>',
				dontAllowDragAndDropSortForFirstLevel: true,
		
				currentSelectionIDID: '<?= $id_prefix; ?>_new_parent_id',
				currentSelectionDisplayID: '<?= $id_prefix; ?>HierarchyBrowserSelectionMessage',
				currentSelectionDisplayFormat: <?= json_encode(_t('Will be moved under <em>^current</em> after next save.')); ?>,
				
				allowExtractionFromHierarchy: <?= ($t_subject->getProperty('HIERARCHY_TYPE') == __CA_HIER_TYPE_ADHOC_MONO__) ? 'true' : 'false'; ?>,
				extractFromHierarchyButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_EXTRACT__, 1); ?>",
				extractFromHierarchyMessage: <?= json_encode(_t('Will be placed at the top of its own hierarchy after next save.')); ?>,
				
				allowSecondarySelection: true,
				secondarySelectionID: '<?= $id_prefix; ?>_move_selection',
				defaultSecondarySelection: [<?= $t_subject->getPrimaryKey() ?>],
				
				onSelection: function(id, parent_id, name, formattedDisplay) {
					// Update "move" status message
<?php
	if (($subject_table == 'ca_collections')) {
?>
					if (id.substr(0, 10) == 'ca_objects') {
						formattedDisplay = <?= json_encode(_t("Cannot move collection under object")); ?>;
						jQuery("#<?= $id_prefix; ?>HierarchyBrowserSelectionMessage").html(formattedDisplay);
						return;
					}
<?php
	}
?>
					jQuery("#<?= $id_prefix; ?>HierarchyBrowserSelectionMessage").html(formattedDisplay);
					if (caUI.utils.showUnsavedChangesWarning) { caUI.utils.showUnsavedChangesWarning(true); }
				},
				
				displayCurrentSelectionOnLoad: false,
				autoShrink: <?= (caGetOption('auto_shrink', $pa_bundle_settings, false) ? 'true' : 'false'); ?>,
				autoShrinkAnimateID: '<?= $id_prefix; ?>MoveHierarchyBrowser'
			});
		}
	}
<?php
	}
	
	if ($show_add) {
?>
	// Set up "add" hierarchy browser
	var o<?= $id_prefix; ?>AddHierarchyBrowser = null;
	
	function _init<?= $id_prefix; ?>AddHierarchyBrowser() {
		if (!o<?= $id_prefix; ?>AddHierarchyBrowser) {
			o<?= $id_prefix; ?>AddHierarchyBrowser = caUI.initHierBrowser('<?= $id_prefix; ?>AddHierarchyBrowser', {
				levelDataUrl: '<?= $lookup_urls['levelList']; ?>',
				initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
				
				readOnly: true,
				allowSelection: false,
				disabledItems: '<?= $vs_disabled_items_mode; ?>',
				
				initItemID: '<?= $vn_init_id; ?>',
				indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				displayCurrentSelectionOnLoad: true,
				autoShrink: <?= (caGetOption('auto_shrink', $pa_bundle_settings, false) ? 'true' : 'false'); ?>,
				autoShrinkAnimateID: '<?= $id_prefix; ?>AddHierarchyBrowser'
			});
		}
	}
<?php
	}
	
	if ($show_add_object) {
?>
	// Set up "add object" hierarchy browser
	var o<?= $id_prefix; ?>AddObjectHierarchyBrowser = null;
	
	function _init<?= $id_prefix; ?>AddObjectHierarchyBrowser() {
		if (!o<?= $id_prefix; ?>AddObjectHierarchyBrowser) {
			o<?= $id_prefix; ?>AddObjectHierarchyBrowser = caUI.initHierBrowser('<?= $id_prefix; ?>AddObjectHierarchyBrowser', {
				levelDataUrl: '<?= $lookup_urls['levelList']; ?>',
				initDataUrl: '<?= $lookup_urls['ancestorList']; ?>',
				
				readOnly: true,
				allowSelection: false,
				
				initItemID: '<?= $vn_init_id; ?>',
				indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
				displayCurrentSelectionOnLoad: true,
				autoShrink: <?= (caGetOption('auto_shrink', $pa_bundle_settings, false) ? 'true' : 'false'); ?>,
				autoShrinkAnimateID: '<?= $id_prefix; ?>AddObjectHierarchyBrowser'
			});
		}
	}
<?php
	}
?>
</script>
<?php
}
?>
	</div><!-- bundleContainer -->
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {	
		// Remove "unsaved changes" warnings from search boxes
		jQuery(".hierarchyBrowserFind input").unbind("change");
		// Remove "unsaved changes" warnings from drop-downs in "add" tab
		jQuery("#<?= $id_prefix; ?>HierarchyBrowserTabs-add select").unbind("change");
		
<?php
	if ($vb_batch) {
?>
		jQuery("#<?= $id_prefix; ?>HierarchyBrowserContainer").show();
		jQuery("#<?= $id_prefix; ?>").hide();
<?php
		if($show_move) { 
?>
		_init<?= $id_prefix; ?>MoveHierarchyBrowser();
<?php
		}
	} elseif (isset($pa_bundle_settings['open_hierarchy']) && (bool)$pa_bundle_settings['open_hierarchy']) {
?>
		jQuery("#<?= $id_prefix; ?>browseToggle").trigger("click", { "delay" : 0 });
<?php
	}
	if(($default_tab_index === 1) && $show_move) {
		// Send event to move tab to trigger load of hierarchy
?>
		_init<?= $id_prefix; ?>MoveHierarchyBrowser();
<?php
	}
?>
	});
</script>
