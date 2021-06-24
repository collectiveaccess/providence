<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/batch_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 
 	$t_subject 			= $this->getVar('t_subject');
 	$o_result_context 	= $this->getVar('result_context');
	$t_list 			= new ca_lists();
	

	if (true) {
?>
<div class='batchTools'>
	<a href="#" id='searchBatchToolsShow' onclick="return caShowBatchTools();"><?php print caNavIcon(__CA_NAV_ICON_SETS__, 2).' '._t("Batch Tools"); ?></a>
</div><!-- end setTools -->

<div id="batchTools">
<?php
	if(true) {
?>
		<div class="col">
<?php
			print "<span class='header'>"._t("Batch edit").":</span><br/>";
?>
			<form id="xxx">
<?php
				print caHTMLSelect('content', 
					array(
						_t('all results') => 'results',
						_t('checked items') => 'checked'
					), 
					array('id' => 'caBatchEditContentSelect', 'class' => 'searchSetsSelect'),
					array('value' => null, 'width' => '140px')
				);
?>
				<a href='#' onclick="return caBatchEditResults();" class="button"><?= caNavIcon(__CA_NAV_ICON_GO__, 1); ?> <?php print _t('Batch edit'); ?></a>
			</form>
		</div>
<?php
		}
?>

		<a href='#' id='hideBatchTools' onclick='return caHideBatchTools();'><?php print caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
		<br/>
		<div class="clear">&nbsp;</div>
</div><!-- end searchSetTools -->
<?php
	}
?>
<script type="text/javascript">
	function caShowBatchTools() {
		jQuery('.batchTools').hide(); 
		jQuery('#searchBatchToolsShow').hide();
		jQuery('#batchTools').slideDown(250);
		
		jQuery("input.addItemToSetControl").show(); 
		return false;
	}
	
	function caHideBatchTools() {
		jQuery('#searchBatchToolsShow').show();
		jQuery('#batchTools').slideUp(250);
		
		jQuery("input.addItemToSetControl").hide(); 
		jQuery('.batchTools').slideDown(250); 
		return false;
	}
	
	function caBatchEditResults() {
		let content = jQuery("#caBatchEditContentSelect").val();
		window.location = <?= json_encode(caNavUrl($this->request, 'batch', 'Editor', 'Edit', ['contextTable' => $o_result_context->tableName(), 'contentType' => $o_result_context->findType(), 'contentSubType' => $o_result_context->findSubType()])); ?>
	}
	
	//
	// Find and return list of checked items to be added to set
	// item_ids are returned in a simple array
	//
	// function caGetSelectedItemIDs() {
// 		var selectedItemIDS = [];
// 		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
// 			if (jQuery(j).prop('checked')) {
// 				selectedItemIDS.push(jQuery(j).val());
// 			}
// 		});
// 		return selectedItemIDS;
// 	}
// 	
// 	function caToggleChecked() {
// 		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
// 			jQuery(j).prop('checked', !jQuery(j).prop('checked'));
// 		});
// 		return false;
// 	}
	
</script>
