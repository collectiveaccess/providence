<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_location.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 
	JavascriptLoadManager::register('hierBrowser');
	JavascriptLoadManager::register('tabUI');
	
	$t_subject 			= $this->getVar('t_subject');
	$vs_subject_label	= $t_subject->getLabelForDisplay();
	if (($vs_priv_table = $t_subject->tableName()) == 'ca_list_items') { $vs_priv_table = 'ca_lists'; }		// actions happen to be on names for ca_lists for ca_list_items
	
	$pn_parent_id 		= $this->getVar('parent_id');
	$pa_ancestors 		= $this->getVar('ancestors');
	$pn_id 				= $this->getVar('id');
	$ps_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix').'HierLocation';
	
	switch($vs_priv_table) {
		case 'ca_relationship_types':
			$vb_has_privs = $this->request->user->canDoAction('can_configure_relationship_types');
			break;
		case 'ca_tour_stops':
			$vb_has_privs = $this->request->user->canDoAction('can_create_ca_tours');
			break;
		default:
			$vb_has_privs = $this->request->user->canDoAction('can_create_'.$vs_priv_table);
			break;
	}
	
	$vb_objects_x_collections_hierarchy_enabled = (bool)$t_subject->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled');
	$t_object = new ca_objects();
	
	$va_search_lookup_extra_params = array('noInline' => 1);
	if ($t_subject->getProperty('HIERARCHY_ID_FLD') && ($vn_hier_id = (int)$t_subject->get($t_subject->getProperty('HIERARCHY_ID_FLD')))) {
		$va_search_lookup_extra_params['currentHierarchyOnly'] = $vn_hier_id;
	}
	if (in_array($t_subject->tableName(), array('ca_objects', 'ca_collections')) && $vb_objects_x_collections_hierarchy_enabled) {
		$va_lookup_urls = array(
			'search' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Get', $va_search_lookup_extra_params),
			'levelList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyLevel'),
			'ancestorList' => caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'GetHierarchyAncestorList')
		);
		$vs_edit_url = caNavUrl($this->request, 'lookup', 'ObjectCollectionHierarchy', 'Edit').'/id/';
		$vn_init_id = $t_subject->tableName()."-".$pn_id;
	} else {
		$va_lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName(), $va_search_lookup_extra_params);
		$vs_edit_url = caEditorUrl($this->request, $t_subject->tableName());
		$vn_init_id = $pn_id;
	}
	
	$vb_strict_type_hierarchy = (bool)$this->request->config->get($t_subject->tableName().'_enforce_strict_type_hierarchy');
	$vs_type_selector 	= trim($t_subject->getTypeListAsHTMLFormElement("{$ps_id_prefix}type_id", array('id' => "{$ps_id_prefix}typeList"), array('childrenOfCurrentTypeOnly' => $vb_strict_type_hierarchy, 'includeSelf' => !$vb_strict_type_hierarchy, 'directChildrenOnly' => $vb_strict_type_hierarchy)));
	
	$pa_bundle_settings = $this->getVar('settings');
	$vb_read_only		=	((isset($pa_bundle_settings['readonly']) && $pa_bundle_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_subject->tableName(), 'hierarchy_location') == __CA_BUNDLE_ACCESS_READONLY__));
	
		
		
	$va_errors = array();
	if(is_array($va_action_errors = $this->request->getActionErrors('hierarchy_location'))) {
		foreach($va_action_errors as $o_error) {
			$va_errors[] = $o_error->getErrorDescription();
		}
	}
?>	
<script type="text/javascript">
	//
	// Handle browse header scrolling
	//
	jQuery(document).ready(function() {
		if (jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').width() > jQuery('#<?php print $ps_id_prefix; ?>HierarchyPanelHeader').width()) {
			//
			// The content is wider than the content viewer area so set up the scroll
			//
			
			jQuery('#<?php print $ps_id_prefix; ?>NextPrevControls').show(); // show controls
			
			var hierarchyHeaderContentWidth = jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').width();
			var hierarchyPanelHeaderWidth = jQuery('#<?php print $ps_id_prefix; ?>HierarchyPanelHeader').width();
			
			if (hierarchyHeaderContentWidth > hierarchyPanelHeaderWidth) {
				jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left', ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1) + "px"); // start at right side
			}
			
			//
			// Handle click on "specific" button
			//
			jQuery('#<?php print $ps_id_prefix; ?>NextControl').click(function() {		
				if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) >  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
					//
					// If we're not already at the right boundary then scroll right
					//
					var dl = (parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left')) - hierarchyPanelHeaderWidth);
					if (dl < ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) { dl = ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1); }
					jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px" }, { duration: 300, easing: 'swing', queue: false, complete: function() {			
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) <=  ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?php print $ps_id_prefix; ?>NextControl').hide();
						}
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
							jQuery('#<?php print $ps_id_prefix; ?>PrevControl').show();
						} 
					}}); 
				}
				return false;
			});
			
			//
			// Handle click on "broader" button
			// 
			jQuery('#<?php print $ps_id_prefix; ?>PrevControl').click(function() {			
				if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) < 0) {
					//
					// Only scroll if we're not showing the extreme left side of the content area already.
					//
					var dl = parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left')) + hierarchyPanelHeaderWidth;
					if (dl > 0) { dl = 0; }
					jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').stop().animate({'left': dl + "px"}, { duration: 300, easing: 'swing', queue: false, complete: function() {
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) >= 0) {
							jQuery('#<?php print $ps_id_prefix; ?>PrevControl').hide();
						} 
						if ((parseInt(jQuery('#<?php print $ps_id_prefix; ?>HierarchyHeaderContent').css('left'))) > ((hierarchyHeaderContentWidth - hierarchyPanelHeaderWidth) * -1)) {
							jQuery('#<?php print $ps_id_prefix; ?>NextControl').show();
						}
					}}); 
				}
				return false;
			});
			jQuery('#<?php print $ps_id_prefix; ?>NextControl').hide();
		} else {
			// 
			// Everything can fit without scrolling so hide the controls
			//
			jQuery('#<?php print $ps_id_prefix; ?>NextPrevControls').hide();
		}
	});
</script>
<?php
	print caEditorBundleShowHideControl($this->request, $ps_id_prefix.$t_subject->tableNum().'HierLocation');
?>
<div class="bundleContainer" id="<?php print $ps_id_prefix.$t_subject->tableNum(); ?>HierLocation">
	<div class="hierNav" >
<?php
			if(sizeof($va_errors)) {
				print "<div class='formLabel'><span class='formLabelError'>".join('; ', $va_errors)."</span></div>\n";
			}
	
			if ($pn_id > 0) {
?>
				<div class="buttonPosition">
					<a href="#" id="<?php print $ps_id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?php print _t('Show Hierarchy'); ?></span></a>
				</div>			
<?php	
			}

			print '<div id="'.$ps_id_prefix.'HierarchyPanelHeader" class="hierarchyPanelHeader"><div id="'.$ps_id_prefix.'HierarchyHeaderContent" class="hierarchyHeaderContent">';
			
			$va_path = array();
			$va_object_collection_collection_ancestors = $this->getVar('object_collection_collection_ancestors');
			$vb_do_objects_x_collections_hierarchy = false;
			if ($vb_objects_x_collections_hierarchy_enabled && is_array($va_object_collection_collection_ancestors)) {
				$pa_ancestors = $va_object_collection_collection_ancestors + $pa_ancestors;
				$vb_do_objects_x_collections_hierarchy = true;
			}
			
			if (is_array($pa_ancestors) && sizeof($pa_ancestors) > 0) {
				foreach($pa_ancestors as $vn_id => $va_item) {
					$vs_item_id = $vb_do_objects_x_collections_hierarchy ? ($va_item['table'].'-'.$va_item['item_id']) : $va_item['item_id'];
					if($vn_id === '') {
						$va_path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
					} else {
						$vs_label = $va_item['label'];
						if (($va_item['table'] != $t_subject->tableName()) || ($va_item['item_id'] && ($va_item['item_id'] != $pn_id))) {
							$va_path[] = '<a href="'.caEditorUrl($this->request, $va_item['table'], $va_item['item_id']).'">'.$vs_label.'</a>';
						} else {
							$vn_item_id = array_pop(explode("-", $vs_item_id));
							$va_path[] = "<a href='#' onclick='jQuery(\"#{$ps_id_prefix}HierarchyBrowserContainer\").slideDown(250); o{$ps_id_prefix}ExploreHierarchyBrowser.setUpHierarchy(\"{$vn_item_id}\"); return false;'>{$vs_label}</a>";
						}
					}
				}
				
			}
			print join(' ➔ ', $va_path);
			print '</div></div>';
?>
			<div id="<?php print $ps_id_prefix; ?>HierarchyHeaderScrollButtons" class="hierarchyHeaderScrollButtons">
				<div id="<?php print $ps_id_prefix; ?>NextPrevControls" class="nextPrevControls"><a href="#" id="<?php print $ps_id_prefix; ?>PrevControl" class="prevControl">&larr;</a> <a href="#" id="<?php print $ps_id_prefix; ?>NextControl" class="nextControl">&rarr;</a></div>
			</div>
	</div><!-- end hiernav -->
<?php
	if ($pn_id > 0) {
?>
		<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">		
			<div  id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs">
				<ul>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser();'><span><?php print _t('Explore'); ?></span></a></li>
<?php
	if (!$vb_strict_type_hierarchy && !$vb_read_only) {
?>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-move" onclick='_init<?php print $ps_id_prefix; ?>MoveHierarchyBrowser();'><span><?php print _t('Move'); ?></span></a></li>
<?php
	}
	if ((!$vb_read_only && $vb_has_privs) && (!$vb_strict_type_hierarchy || ($vb_strict_type_hierarchy && $vs_type_selector))) {
?>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-add" onclick='_init<?php print $ps_id_prefix; ?>AddHierarchyBrowser();'><span><?php print ($vb_objects_x_collections_hierarchy_enabled && ($t_subject->tableName() == 'ca_collections')) ? _t('Add level') : _t('Add'); ?></span></a></li>
<?php
	}
	if ((!$vb_read_only && $vb_has_privs) && $vb_objects_x_collections_hierarchy_enabled && ($t_subject->tableName() == 'ca_collections')) {
?>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-addObject" onclick='_init<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser();'><span><?php print _t('Add object'); ?></span></a></li>
<?php
	}
?>
				</ul>

				<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" class="hierarchyBrowseTab">	
					<div class="hierarchyBrowserFind">
						<?php print _t('Find'); ?>: <input type="text" id="<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
							<?php print _t('Click %1 names to explore. Click on an arrow icon to open a %1 for editing.', $t_subject->getProperty('NAME_SINGULAR')); ?>
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
				</div>
<?php
	if (!$vb_strict_type_hierarchy && !$vb_read_only) {
?>
				<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-move" class="hierarchyBrowseTab">
					<div class="hierarchyBrowserFind">
						<?php print _t('Find'); ?>: <input type="text" id="<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch" name="search" value="" size="25"/>
					</div>
					<div class="hierarchyBrowserMessageContainer">
						<?php print _t('Click on an arrow to choose the location to move this record under.', $t_subject->getProperty('NAME_SINGULAR')); ?>
						<div id='<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage' class='hierarchyBrowserNewLocationMessage'><!-- Message specifying move destination is dynamically inserted here by ca.hierbrowser --></div>	
					</div>
					<div class="clear"><!-- empty --></div>
					<div id="<?php print $ps_id_prefix; ?>MoveHierarchyBrowser" class="hierarchyBrowserSmall">
						<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
					</div><!-- end hierbrowser -->
				</div>
<?php
	}
	if ((!$vb_read_only && $vb_has_privs) && (!$vb_strict_type_hierarchy || ($vb_strict_type_hierarchy && $vs_type_selector))) {
?>
			<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-add"  class="hierarchyBrowseTab">
				<div class="hierarchyBrowserMessageContainer">
					<?php print _t('Use the controls below to create new %1 relative to this record in the hierarchy.', $t_subject->getProperty('NAME_PLURAL'), $vs_subject_label); ?>
				</div>
				
				<div id='<?php print $ps_id_prefix; ?>HierarchyBrowseTypeMenu' style="margin-top: 15px;">
					<div style="float: left; width: 700px">
<?php
						$va_add_types = array(_t('under (child)') => 'under');
						
						if (!$vb_strict_type_hierarchy) { $va_add_types[_t('above (parent)')] = 'above'; }
						if (($pn_parent_id > 0) && !$vb_strict_type_hierarchy) { $va_add_types[_t('next to (sibling)')] = 'next_to'; }
						
						if ($vs_type_selector) {
							// for items that take types
							print "<div id='{$ps_id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>", $vs_type_selector, caHTMLSelect('add_type', $va_add_types, array('id' => "{$ps_id_prefix}addType")), $vs_subject_label);
		
							// Note the jQuery(\"#{$ps_id_prefix}childTypeList\").val() which grabs the value of the type
							print " <a href='#' onclick='_navigateToNewForm(jQuery(\"#{$ps_id_prefix}typeList\").val(), jQuery(\"#{$ps_id_prefix}addType\").val(), (jQuery(\"#{$ps_id_prefix}addType\").val() == \"next_to\") ? ".intval($pn_parent_id)." : ".intval($pn_id).")'>".caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a></div>";
						} else {
							// for items without types
							print "<div id='{$ps_id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 %2 <em>%3</em>",  $t_subject->getProperty('NAME_SINGULAR'), caHTMLSelect('add_type', $va_add_types, array('id' => "{$ps_id_prefix}addType")), $vs_subject_label);
							print " <a href='#' onclick='_navigateToNewForm(0, jQuery(\"#{$ps_id_prefix}addType\").val(), (jQuery(\"#{$ps_id_prefix}addType\").val() == \"next_to\") ? ".intval($pn_parent_id)." : ".intval($pn_id).")'>".caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a></div>";
						}
?>
					</div>
				</div>
				<div class="clear"><!-- empty --></div>
				<div id="<?php print $ps_id_prefix; ?>AddHierarchyBrowser" class="hierarchyBrowserSmall">
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
		</div>
<?php
	}
		if ((!$vb_read_only && $vb_has_privs) && $vb_objects_x_collections_hierarchy_enabled && ($t_subject->tableName() == 'ca_collections')) {
?>
			<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-addObject"  class="hierarchyBrowseTab">
				<div class="hierarchyBrowserMessageContainer">
					<?php print _t('Use the controls below to create new %1 relative to this %2 in the hierarchy.', $t_object->getProperty('NAME_PLURAL'), mb_strtolower($t_subject->getTypeName())); ?>
				</div>
				
				<div id='<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowseTypeMenu' style="margin-top: 15px;">
					<div style="float: left; width: 700px">
<?php
							$vs_type_selector 	= trim($t_object->getTypeListAsHTMLFormElement("{$ps_id_prefix}object_type_id", array('id' => "{$ps_id_prefix}objectTypeList"), array('childrenOfCurrentTypeOnly' => $vb_strict_type_hierarchy, 'includeSelf' => !$vb_strict_type_hierarchy, 'directChildrenOnly' => $vb_strict_type_hierarchy)));
							
							print "<div id='{$ps_id_prefix}HierarchyBrowseAdd'>"._t("Add a new %1 under <em>%2</em>", $vs_type_selector, $vs_subject_label);
		
							// Note the jQuery(\"#{$ps_id_prefix}childTypeList\").val() which grabs the value of the type
							print " <a href='#' onclick='_navigateToNewObjectForm(jQuery(\"#{$ps_id_prefix}objectTypeList\").val(), ".intval($pn_id).")'>".caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a></div>";				
?>
					</div>
				</div>
				<div class="clear"><!-- empty --></div>
				<div id="<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser" class="hierarchyBrowserSmall">
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
		</div>
<?php
	}
?>
		</div>
	</div>
	<input type='hidden' name='<?php print $ps_id_prefix; ?>_new_parent_id' id='<?php print $ps_id_prefix; ?>_new_parent_id' value='<?php print $pn_parent_id; ?>'/>

<script type="text/javascript">
	jQuery(document).ready(function() {	
<?php
	if (!$vb_strict_type_hierarchy) {
?>
		// Set up "move" hierarchy browse search
		jQuery('#<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch').autocomplete(
			{ 
				source: '<?php print $va_lookup_urls['search']; ?>', minLength: 3, delay: 800, html: true,
				select: function( event, ui ) {
					if (ui.item.id) {
						jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
						o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
					}
					event.preventDefault();
					jQuery('#<?php print $ps_id_prefix; ?>MoveHierarchyBrowserSearch').val('');
				}
			}
		).click(function() { this.select() });
<?php
	}
?>

		jQuery("#<?php print $ps_id_prefix; ?>browseToggle").click(function(e, opts) {
			_init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser();
			var delay = (opts && opts.delay && (parseInt(opts.delay) >= 0)) ? opts.delay :  250;
			jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideToggle(delay, function() { 
				jQuery("#<?php print $ps_id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?php print '<span class="form-button">'._t('Show Hierarchy').'</span>';?>');
			}); 
			return false;
		});
		
		// Set up "explore" hierarchy browse search
		jQuery('#<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch').autocomplete(
			{
				source: '<?php print $va_lookup_urls['search']; ?>', minLength: 3, delay: 800, html: true,
				select: function( event, ui ) {
					if (ui.item.id) {
						jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideDown(350);
						o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
					}
					event.preventDefault();
					jQuery('#<?php print $ps_id_prefix; ?>ExploreHierarchyBrowserSearch').val('');
				}
			}
		).click(function() { this.select() });
		
		// Disable form change warnings to add type drop-downs
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowseAddUnder select').unbind('change');
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowseAddNextTo select').unbind('change');		
		
		jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: 0 });					// Activate tabs
		jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer').hide(0);			// Hide extended options
	});
	
	if (typeof  _navigateToNewForm != 'function') {
		function _navigateToNewForm(type_id, action, id) {
			switch(action) {
				case 'above':
					document.location = '<?php print caEditorUrl($this->request, $t_subject->tableName(), 0); ?>/type_id/' + type_id + '/above_id/' + id;
					break;
				case 'under':
				case 'next_to':
					document.location = '<?php print caEditorUrl($this->request, $t_subject->tableName(), 0); ?>/type_id/' + type_id + '/parent_id/' + id;
					break;
				default:
					alert("Invalid action!");
					break;
			}
		}
	}
	if (typeof  _navigateToNewObjectForm != 'function') {
		function _navigateToNewObjectForm(type_id, parent_collection_id) {
			document.location = '<?php print caEditorUrl($this->request, "ca_objects", 0); ?>/type_id/' + type_id + '/collection_id/' + parent_collection_id;
		}
	}
	
	// Set up "explore" hierarchy browser
	var o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				dontAllowEditForFirstLevel: <?php print (in_array($t_subject->tableName(), array('ca_places', 'ca_storage_locations', 'ca_list_items', 'ca_relationship_types')) ? 'true' : 'false'); ?>,
				
				readOnly: <?php print $vb_read_only ? 1 : 0; ?>,
				
				editUrl: '<?php print $vs_edit_url; ?>',
				editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit">',
				
				initItemID: '<?php print $vn_init_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				displayCurrentSelectionOnLoad: false
			});
		}
	}

<?php
	if (!$vb_strict_type_hierarchy && !$vb_read_only) {
?>
	// Set up "move" hierarchy browser
	var o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>MoveHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>MoveHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>MoveHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				readOnly: <?php print $vb_read_only ? 1 : 0; ?>,
				
				initItemID: '<?php print $vn_init_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="<?php print _t("Edit"); ?>">',
				
					
				currentSelectionIDID: '<?php print $ps_id_prefix; ?>_new_parent_id',
				currentSelectionDisplayID: '<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage',
				currentSelectionDisplayFormat: '<?php print addslashes(_t('Will be moved under <em>%1</em> after next save.')); ?>',
				
				allowExtractionFromHierarchy: <?php print ($t_subject->getProperty('HIERARCHY_TYPE') == __CA_HIER_TYPE_ADHOC_MONO__) ? 'true' : 'false'; ?>,
				extractFromHierarchyButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_up.gif" border="0" title="<?php print _t("Extract from hierarchy"); ?>">',
				extractFromHierarchyMessage: '<?php print addslashes(_t('Will be placed at the top of its own hierarchy after next save.')); ?>',
				
				onSelection: function(id, parent_id, name, formattedDisplay) {
					// Update "move" status message
<?php
	if (($t_subject->tableName() == 'ca_collections')) {
?>
					if (id.substr(0, 10) == 'ca_objects') {
						formattedDisplay = '<?php print addslashes(_t("Cannot move collection under object")); ?>';
						jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage").html(formattedDisplay);
						return;
					}
<?php
	}
?>
					jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserSelectionMessage").html(formattedDisplay);
					if (caUI.utils.showUnsavedChangesWarning) { caUI.utils.showUnsavedChangesWarning(true); }
				},
				
				displayCurrentSelectionOnLoad: false
			});
		}
	}
<?php
	}
	
	if ((!$vb_read_only && $vb_has_privs) && (!$vb_strict_type_hierarchy || ($vb_strict_type_hierarchy && $vs_type_selector))) {
?>
	// Set up "add" hierarchy browser
	var o<?php print $ps_id_prefix; ?>AddHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>AddHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>AddHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>AddHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>AddHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				readOnly: true,
				allowSelection: false,
				
				initItemID: '<?php print $vn_init_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				displayCurrentSelectionOnLoad: true
			});
		}
	}
<?php
	}
	
	if ((!$vb_read_only && $vb_has_privs) && $vb_objects_x_collections_hierarchy_enabled && ($t_subject->tableName() == 'ca_collections')) {
?>
	// Set up "add object" hierarchy browser
	var o<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser = null;
	
	function _init<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser() {
		if (!o<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser) {
			o<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>AddObjectHierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				
				readOnly: true,
				allowSelection: false,
				
				initItemID: '<?php print $vn_init_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				displayCurrentSelectionOnLoad: true
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

<script type="text/javascript">
	jQuery(document).ready(function() {	
		// Remove "unsaved changes" warnings from search boxes
		jQuery(".hierarchyBrowserFind input").unbind("change");
		// Remove "unsaved changes" warnings from drop-downs in "add" tab
		jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-add select").unbind("change");
		
<?php
	if (isset($pa_bundle_settings['open_hierarchy']) && (bool)$pa_bundle_settings['open_hierarchy']) {
?>
		jQuery("#<?php print $ps_id_prefix; ?>browseToggle").trigger("click", { "delay" : 0 });
<?php
	}
?>
	});
</script>
