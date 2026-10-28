<?php
/* ----------------------------------------------------------------------
 * bundles/duplication_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
AssetLoadManager::register("panel");
AssetLoadManager::register("select2");
$t_item = $this->getVar('t_item');

$last_duplication_mode = $this->getVar('last_duplication_mode');
?>
<script type="text/javascript">
	var caDuplicationSettingsPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caDuplicationSettingsPanel = caUI.initPanel({ 
				panelID: "caDuplicationSettingsPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caDuplicationSettingsPanelContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
				jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		
		jQuery('#caDuplicateItemFormSetList').select2({
			'dropdownAutoWidth': true, 
			'allowClear': true,
			'placeholder': <?= json_encode("Search sets"); ?>,
			'ajax': {
        		'url': <?= json_encode(caJSONLookupServiceUrl($this->request, 'ca_sets')['search']); ?>,
				'data': function(p) {
					let query = {
						'term': p.term,
						'noInline': '1'
					}
            		return query;
       			},
       			'processResults': function(d) {
       				let acc = [];
       				for(let i in d) {
       					let item = d[i];
       					acc.push({
       						'id': item['id'],
       						'text': item['label']
       					});
       				}
       				
       				return {'results': acc};
       			}
   			}
		});
		jQuery("form#caDuplicateItemForm input[name='duplication_mode']").on('change', caUpdateDuplicationForm);
		caUpdateDuplicationForm();
	});
	function caUpdateDuplicationForm() {
		const duplication_mode = jQuery("form#caDuplicateItemForm input[name='duplication_mode']:checked").val();
		if(duplication_mode === 'pair') {
			jQuery('#caDuplicationQuantity').attr('disabled', true);
			jQuery('#caDuplicateItemFormSetList').prop('disabled', false);
		} else {
			jQuery('#caDuplicationQuantity').attr('disabled', false);
			jQuery('#caDuplicateItemFormSetList').prop('disabled', true);
		}
	}
</script>
<div id="caDuplicationSettingsPanel" class="caDuplicationSettingsPanel"> 
	<div class='dialogHeader'><?= _t('Duplicate %1', $this->getVar('type_name')); ?></div>
	<div id="caDuplicationSettingsPanelContentArea">
		<?= caFormTag($this->request, 'Edit', 'caDuplicateItemForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true]); ?>
			<p><input type="radio" name="duplication_mode" value="create" <?= ($last_duplication_mode === 'create') ? ' CHECKED="CHECKED"' : ''; ?>><?= _t('Duplicate count: %1', caHTMLTextInput('duplication_quantity', ['value' => $this->request->user->getVar('last_duplication_quantity') ?? 1, 'class' => '', 'id' => 'caDuplicationQuantity', 'size' => 4])); ?></p>
			<p><input type="radio" name="duplication_mode" value="pair" <?= ($last_duplication_mode === 'pair') ? ' CHECKED="CHECKED"' : ''; ?>><?= _t('Pair duplicates with items in set: %1', $this->getVar('set_select')); ?>
			</p>
			<div id="caDuplicationSettingsPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?= caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caDuplicateItemForm'); ?></td>
						<td align="left"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caDuplicateItemFormCancelButton', ['onclick' => 'caDuplicationSettingsPanel.hidePanel(); return false;'], ['size' => '30px']); ?></td>
					</tr>
				</table>
			</div>
			
			<?= caHTMLHiddenInput($t_item->primaryKey(), ['value' => $t_item->getPrimaryKey()]); ?>
			<?= caHTMLHiddenInput('mode', ['value' => 'dupe']); ?>
		</form>
	</div>
</div>
