<?php
/* ----------------------------------------------------------------------
 * bundles/hierarchy_navigation.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
	$pa_ancestors 		= $this->getVar('ancestors');
	$pn_id 				= $this->getVar('id');
	$ps_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix').'HierNavigation';
	$va_lookup_urls 	= caJSONLookupServiceUrl($this->request, $t_subject->tableName());
	
	$pa_bundle_settings = $this->getVar('settings');
?>	
	<div class="bundleContainer">
		<div class="hierNav">
<?php
	if ($pn_id > 0) {
?>
			<div class="buttonPosition"><a href="#" id="<?php print $ps_id_prefix; ?>browseToggle" class="form-button"><span class="form-button"><?php print _t('Show in browser'); ?></span></a></div>
<?php
	}
	if (is_array($pa_ancestors) && sizeof($pa_ancestors) > 0) {
		$va_path = array();
		foreach($pa_ancestors as $vn_id => $va_item) {
			if($vn_id === '') {
				$va_path[] = "<a href='#'>"._t('New %1', $t_subject->getTypeName())."</a>";
			} else {
				$vs_name = htmlspecialchars($va_item['name'], ENT_QUOTES, 'UTF-8');
				if ($pn_id && $va_item[$t_subject->primaryKey()] && ($va_item[$t_subject->primaryKey()] != $pn_id)) {
					$va_path[] = '<a href="'.caEditorUrl($this->request, $t_subject->tableName(), $va_item[$t_subject->primaryKey()]).'">'.$vs_name.'</a>';
				} else {
					$va_path[] = "<a href='#' onclick='jQuery(\"#".$ps_id_prefix."HierarchyBrowserContainer\").slideDown(250); o".$ps_id_prefix."HierarchyBrowser.setUpHierarchy(".intval($va_item[$t_subject->primaryKey()])."); return false;'>".$vs_name."</a>";
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
	<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserContainer" class="editorHierarchyBrowserContainer">
		<div  id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs">
			<ul>
					<li><a href="#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" onclick='_init<?php print $ps_id_prefix; ?>ExploreHierarchyBrowser();'><span>Explore</span></a></li>
			</ul>
			
			<div id="<?php print $ps_id_prefix; ?>HierarchyBrowserTabs-explore" class="hierarchyBrowseTab">	
				<div class="hierarchyBrowserMessageContainer">
					<?php print _t('Use the browser to explore the hierarchy. You can edit other hierarchy items by clicking on the arrow.'); ?>
				</div>
				<div id="<?php print $ps_id_prefix; ?>HierarchyBrowser" class="hierarchyBrowserSmall">
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierbrowser -->
			</div>
		</div>
	</div>

	<script type="text/javascript">
		var o<?php print $ps_id_prefix; ?>HierarchyBrowser;
		jQuery(document).ready(function() {		
			o<?php print $ps_id_prefix; ?>HierarchyBrowser = caUI.initHierBrowser('<?php print $ps_id_prefix; ?>HierarchyBrowser', {
				levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
				initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
				readOnly: false,
				initItemID: '<?php print $pn_id; ?>',
				indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
				
				editUrl: '<?php print caEditorUrl($this->request, $t_subject->tableName()); ?>',
				editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit">',
				
				currentSelectionDisplayID: 'browseCurrentSelection'
			});
			
			jQuery("#<?php print $ps_id_prefix; ?>browseToggle").click(function() {
				jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer").slideToggle(350, function() { 
					jQuery("#<?php print $ps_id_prefix; ?>browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'._t('Close browser').'</span>';?>' : '<?php print '<span class="form-button">'._t('Show in browser').'</span>';?>');
				}); 
				return false;
			});
			
			jQuery('#<?php print $ps_id_prefix; ?>HierarchyBrowserContainer').hide(0);
			
			jQuery("#<?php print $ps_id_prefix; ?>HierarchyBrowserTabs").tabs({ selected: 0 });					// Activate tabs
		});
<?php
	if (isset($pa_bundle_settings['open_hierarchy']) && (bool)$pa_bundle_settings['open_hierarchy']) {
?>
		jQuery("#<?php print $ps_id_prefix; ?>browseToggle").trigger("click");
<?php
	}
?>
	</script>
<?php
	}
?>
