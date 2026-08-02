<?php
/* ----------------------------------------------------------------------
 * bundles/authority_attribute.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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
$field_name_prefix 		= $this->getVar('field_name_prefix');
$quickadd_url 			= $this->getVar('quickadd_url');
$url 					= $this->getVar('lookup_url');
$options 				= $this->getVar('options');
$settings 				= $this->getVar('settings');
$element_info 			= $this->getVar('element_info');
$class					= $this->getVar('class');
$table					= $this->getVar('table');
$for_search				= $this->getVar('forSearch');

if (!$for_search) {
?>
<div id='<?= $field_name_prefix; ?>_display{n}' style='float: right;'> </div>
<?php
}
print caHTMLTextInput(
	"{$field_name_prefix}_autocomplete{n}",
	array(
		'size' => (isset($options['width']) && $options['width'] > 0) ? $options['width'] : $settings['fieldWidth'],
		'height' => (isset($options['height']) && $options['height'] > 0) ? $options['height'] : 1,
		'value' => '{{'.$element_info['element_id'].'}}',
		'maxlength' => 512,
		'id' => "{$field_name_prefix}_autocomplete{n}",
		'class' => $class
	)
);
print caHTMLHiddenInput(
	"{$field_name_prefix}_{n}",
	array(
		'value' => '{{'.$element_info['element_id'].'}}',
		'id' => "{$field_name_prefix}_{n}"
	)
);

print ' '.caNavIcon(__CA_NAV_ICON_DELETE__, 12, ['id' => "{$field_name_prefix}_clear_{n}"]);
?>

<?php if(!$for_search) { ?>
<div id='caRelationQuickAddPanel<?= $field_name_prefix; ?>_{n}' class='caRelationQuickAddPanel'>
	<div id='caRelationQuickAddPanel<?= $field_name_prefix; ?>ContentArea_{n}'>
		<div class='dialogHeader'><?= _t('Quick Add'); ?></div>
	</div>
</div>
<?php } ?>
<script type='text/javascript'>
	jQuery(document).ready(function() {
<?php if(!$for_search) { ?>
		if (caUI.initPanel) {
			var caRelationQuickAddPanel<?= $field_name_prefix; ?>_{n};
			caRelationQuickAddPanel<?= $field_name_prefix; ?>_{n} = caUI.initPanel({
				panelID: 'caRelationQuickAddPanel<?= $field_name_prefix; ?>_{n}',
				panelContentID: 'caRelationQuickAddPanel<?= $field_name_prefix; ?>ContentArea_{n}',
				exposeBackgroundColor: '#000000',
				exposeBackgroundOpacity: 0.7,
				panelTransitionSpeed: 400,
				closeButtonSelector: '.close',
				center: true,
				onOpenCallback: function() {
					jQuery('#topNavContainer').hide(250);
				},
				onCloseCallback: function() {
					jQuery('#topNavContainer').show(250);
				}
			});
		}
<?php } ?>
		var v = jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val();
		v=v.replace(/(<\/?[^>]+>)/gi, function(m, p1, offset, val) {
			jQuery('#<?= $field_name_prefix; ?>_display{n}').html(p1);
			return '';
		});
		v=v.replace(/\[([\d]+)\]$/gi, function(m, p1, offset, val) {
			jQuery('#<?= $field_name_prefix; ?>_{n}').val(parseInt(p1));
			return '';
		});
		jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val(v.trim());

		jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').autocomplete({
			minLength: <?= (int)Configuration::load()->get(["{$table}_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>, delay: 800, html: true,
			source: function( request, response ) {
				$.ajax({
					url: '<?= $url; ?>',
					dataType: 'json',
					data: { term: request.term, quickadd: <?= $this->getVar('allowQuickadd') ? 1 : 0; ?>, noInline: <?= $this->getVar('allowQuickadd') ? 0 : 1; ?> },
					success: function( data ) {
						response(data);
					}
				});
			},
			select: function( event, ui ) {
<?php if(!$for_search) { ?>
				var quickaddPanel = caRelationQuickAddPanel<?= $field_name_prefix; ?>_{n};
				var quickaddUrl = '<?= $quickadd_url; ?>';
				
				if(!parseInt(ui.item.id) || (ui.item.id == 0)) {
					var panelUrl = quickaddUrl;
					//if (ui.item._query) { panelUrl += '/q/' + escape(ui.item._query); }

					quickaddPanel.showPanel(panelUrl, null, null, { q: ui.item._query, field_name_prefix: '<?= $field_name_prefix; ?>' });
					var quickAddPanelContent = jQuery('#' + quickaddPanel.getPanelContentID());
					quickAddPanelContent.data('panel', quickaddPanel);
					quickAddPanelContent.data('autocompleteInput', jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val());
					quickAddPanelContent.data('autocompleteInputID', '<?= $field_name_prefix; ?>_autocomplete{n}');
					quickAddPanelContent.data('autocompleteItemIDID', '<?= $field_name_prefix; ?>_{n}');
					event.preventDefault();
					return;
				} else {
					if(ui.item.id == -1) {
						event.preventDefault();
						return;
					}
				}
				
				jQuery('#<?= $field_name_prefix; ?>_{n}').val(ui.item.id);
				jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
<?php } else { ?>		
				var label = jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, ''));
				jQuery('#<?= $field_name_prefix; ?>_{n}').val(label);
				jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val(label);
<?php } ?>				
				event.preventDefault();
			},
			change: function( event, ui ) {
				// If nothing has been selected remove all content from  text input
				if(!jQuery('#<?= $field_name_prefix; ?>_{n}').val()) {
					jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val('');
				}
			}
		}).click(function() { this.select(); });
		
		jQuery('#<?= $field_name_prefix; ?>_clear_{n}').on('click', function(e) {
			jQuery('#<?= $field_name_prefix; ?>_{n}').val('');
			jQuery('#<?= $field_name_prefix; ?>_autocomplete{n}').val('');
		});
	});
</script>
