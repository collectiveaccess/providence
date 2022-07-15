<?php
/* ----------------------------------------------------------------------
 * views/find/quick_search_results.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2021 Whirl-i-Gig
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
 
 $o_search_config = caGetSearchConfig();
 
 $search = $this->getVar('search');
 $searches = $this->getVar('searches');
 
 
 $sort_form = caFormTag($this->request, 'Index', 'QuickSearchSortForm', null , 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true));
 $sort_form .= _t('Sort by ').caHTMLSelect('sort', array(_t('name') => 'name', _t('relevance') => 'relevance', _t('idno') => 'idno'), array('onchange' => 'jQuery("#QuickSearchSortForm").submit();'), array('value' => $this->getVar('sort')));
 $sort_form .= "</form>";
 
 $has_results = (bool)array_reduce(array_keys($searches), function($c, $i) {
 	if($o_res = $this->getVar("{$i}_results")) {
 		$c += (int)$o_res->numHits();
 	}
 	return $c;
 }, 0);
 
 print $control_box = caFormControlBox(
		'<div class="quickSearchHeader">'.($has_results ? 
			_t("Top %1 results for <em>%2</em>", $this->getVar('maxNumberResults'), $this->getVar('search'))
			: 
			_t("No results found for <em>%1</em>", $this->getVar('search'))
		).'</div>', 
		'',
		$sort_form
	);
	
	$vn_num_result_lists_to_display = 0;
	
?>

<div class="quickSearchContentArea">

<?php
	$vs_visibility = (sizeof($searches) == 1) ? 'block' : 'none';
	foreach($searches as $vs_target => $va_info) {
			$va_table = explode('/', $vs_target);
			$vs_table = $va_table[0]; $vs_type = (isset($va_table[1])) ? $va_table[1] : null;
			
			$o_res = $this->getVar($vs_target.'_results');
			$vs_target_id = str_replace("/", "-", $vs_target);

			if ($o_res->numHits() >= 1) { 
?>
				<div class="quickSearchResultHeader rounded" >
					<div class="quickSearchFullResultsLink"><?= caNavLink($this->request, caNavIcon(__CA_NAV_ICON_FULL_RESULTS__, 2)." "._t("Full Results"), null, $va_info['searchModule'], $va_info['searchController'], $va_info['searchAction'], array("search" => caEscapeSearchForURL($search), 'type_id' => $vs_type ? $vs_type : '*')); ?></div>
					<a href='#' style="text-decoration:none; color:#333;" id='show<?= $vs_target_id; ?>' onclick='return caQuickSearchShowHideResults("show", "<?= $vs_target_id; ?>");'><?= $va_info['displayname']." (".$o_res->numHits().")"; ?> <?= caNavIcon(__CA_NAV_ICON_EXPAND__, '18px'); ?></a>
					<a href='#' id='hide<?= $vs_target_id; ?>' style='display:none; text-decoration:none; color:#333;' onclick='return caQuickSearchShowHideResults("hide", "<?= $vs_target_id; ?>");'><?= $va_info['displayname']." (".$o_res->numHits().")"; ?> <?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, '18px'); ?></a>
				</div>
				<div class="quickSearchHalfWidthResults" id='<?= $vs_target_id; ?>_results' style="display:none;">
					<ul class='quickSearchList'>
<?php
						$t_instance = Datamodel::getInstanceByTableName($vs_table, true);
						$va_type_list = $t_instance->getTypeList();
						
						$vb_show_labels = !(($vs_table === 'ca_objects') && ($t_instance->getAppConfig()->get('ca_objects_dont_use_labels')));
						
						while($o_res->nextHit()) {
							$vs_type = $t_instance->getTypeCode((int)$o_res->get($vs_table.'.type_id'));
							if (!($vs_template = $o_search_config->get($vs_table.'_'.$vs_type.'_quicksearch_result_display_template'))) {
								$vs_template = $o_search_config->get($vs_table.'_quicksearch_result_display_template');
							}
							
							if ($vs_template) {
								print '<li class="quickSearchList">'.$o_res->getWithTemplate($vs_template)."</li>\n";
							} else {
								$vs_idno_display = trim($o_res->get($va_info['displayidno']));
							
								if ($vb_show_labels) {
									$vs_label = $o_res->get($vs_table.'.preferred_labels');
								} else {
									$vs_label = $vs_idno_display;
									$vs_idno_display = '';
								}
								$vs_type_display = '';
								if (($vn_type_id = trim($o_res->get($vs_table.'.type_id'))) && $va_type_list[$vn_type_id]) {
									$vs_type_display = ' ['.$va_type_list[$vn_type_id]['name_singular'].']';
								}
								
								print '<li class="quickSearchList">' .
									caEditorLink($this->request, $vs_label, null, $vs_table, $o_res->get($va_info['primary_key'])) .
									" ".($vs_idno_display ? "({$vs_idno_display})" : "") .
									" {$vs_type_display}</li>\n";
							}
						}
	?>
					</ul>
					<div class="quickSearchResultHide"><a href='#' id='hide<?= $vs_target_id; ?>' onclick='jQuery("#<?= $vs_target_id; ?>_results").slideUp(250); jQuery("#show<?= $vs_target_id; ?>").slideDown(1); jQuery("#hide<?= $vs_target_id; ?>").hide(); return false;'> <?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, 2); ?></a></div>
				</div>
<?php	
			} else {
				print "<div class='quickSearchNoResults rounded'>".$va_info['displayname']." (".$o_res->numHits().")"."</div>";
			}
	}
?>
</div>

<script type="text/javascript">
	function caQuickSearchShowHideResults(m, t) {
		if (m == 'show') {
			jQuery("#" + t + "_results").slideDown(250); 
			jQuery("#show" + t).hide(); 
			jQuery("#hide" + t).show(); 
		} else {
			jQuery("#" + t + "_results").slideUp(250); 
			jQuery("#show" + t).show(); 
			jQuery("#hide" + t).hide(); 
		}	
		return false;
	}
<?php
	if (sizeof($searches) > 0) {
?>
		jQuery(document).ready(function() {
			caQuickSearchShowHideResults('show', '<?= str_replace("/", "-", array_shift(array_keys($searches))); ?>');
		});
<?php
	}
?>
</script>

<div class="editorBottomPadding"><!-- empty --></div>
