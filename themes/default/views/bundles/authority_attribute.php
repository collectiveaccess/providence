<?php
/* ----------------------------------------------------------------------
 * bundles/authority_attribute.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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


$vs_field_name_prefix 		= $this->getVar('field_name_prefix');
$vs_quickadd_url 			= $this->getVar('quickadd_url');
$vs_url 					= $this->getVar('lookup_url');
$pa_options 				= $this->getVar('options');
$va_settings 				= $this->getVar('settings');
$pa_element_info 			= $this->getVar('element_info');
$vs_class					= $this->getVar('class');

?>
<div id='<?php print $vs_field_name_prefix; ?>_display{n}' style='float: right;'> </div>
<?php
print caHTMLTextInput(
	"{$vs_field_name_prefix}_autocomplete{n}",
	array(
		'size' => (isset($pa_options['width']) && $pa_options['width'] > 0) ? $pa_options['width'] : $va_settings['fieldWidth'],
		'height' => (isset($pa_options['height']) && $pa_options['height'] > 0) ? $pa_options['height'] : 1,
		'value' => '{{'.$pa_element_info['element_id'].'}}',
		'maxlength' => 512,
		'id' => "{$vs_field_name_prefix}_autocomplete{n}",
		'class' => $vs_class
	)
);
print caHTMLHiddenInput(
	"{$vs_field_name_prefix}_{n}",
	array(
		'value' => '{{'.$pa_element_info['element_id'].'}}',
		'id' => "{$vs_field_name_prefix}_{n}"
	)
);
?>

<div id='caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>_{n}' class='caRelationQuickAddPanel'>
	<div id='caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>ContentArea_{n}'>
		<div class='dialogHeader'><?php print _t('Quick Add'); ?></div>
	</div>
</div>
<script type='text/javascript'>
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			var caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>_{n};
			caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>_{n} = caUI.initPanel({
				panelID: 'caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>_{n}',
				panelContentID: 'caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>ContentArea_{n}',
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

		var v = jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').val();
		v=v.replace(/(<\/?[^>]+>)/gi, function(m, p1, offset, val) {
			jQuery('#<?php print $vs_field_name_prefix; ?>_display{n}').html(p1);
			return '';
		});
		v=v.replace(/\[([\d]+)\]$/gi, function(m, p1, offset, val) {
			jQuery('#<?php print $vs_field_name_prefix; ?>_{n}').val(parseInt(p1));
			return '';
		});
		jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').val(v.trim());

		jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').autocomplete({
			minLength: 3, delay: 800, html: true,
			source: function( request, response ) {
				$.ajax({
					url: '<?php print $vs_url; ?>',
					dataType: 'json',
					data: { term: request.term, quickadd: <?php print $this->getVar('allowQuickadd') ? 1 : 0; ?>, noInline: <?php print $this->getVar('allowQuickadd') ? 0 : 1; ?> },
					success: function( data ) {
						response(data);
					}
				});
			},
			select: function( event, ui ) {
				var quickaddPanel = caRelationQuickAddPanel<?php print $vs_field_name_prefix; ?>_{n};
				var quickaddUrl = '<?php print $vs_quickadd_url; ?>';

				if(!parseInt(ui.item.id) || (ui.item.id == 0)) {
					var panelUrl = quickaddUrl;
					//if (ui.item._query) { panelUrl += '/q/' + escape(ui.item._query); }

					quickaddPanel.showPanel(panelUrl, null, null, { q: ui.item._query, field_name_prefix: '<?php print $vs_field_name_prefix; ?>' });
					var quickAddPanelContent = jQuery('#' + quickaddPanel.getPanelContentID());
					quickAddPanelContent.data('panel', quickaddPanel);
					quickAddPanelContent.data('autocompleteInput', jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').val());
					quickAddPanelContent.data('autocompleteInputID', '<?php print $vs_field_name_prefix; ?>_autocomplete{n}');
					quickAddPanelContent.data('autocompleteItemIDID', '<?php print $vs_field_name_prefix; ?>_{n}');
					event.preventDefault();
					return;
				} else {
					if(ui.item.id == -1) {
						event.preventDefault();
						return;
					}
				}

				jQuery('#<?php print $vs_field_name_prefix; ?>_{n}').val(ui.item.id);
				jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').val(jQuery.trim(ui.item.label.replace(/<\/?[^>]+>/gi, '')));
				event.preventDefault();
			},
			change: function( event, ui ) {
				//If nothing has been selected remove all content from  text input
				if(!jQuery('#<?php print $vs_field_name_prefix; ?>_{n}').val()) {
					jQuery('#<?php print $vs_field_name_prefix; ?>_autocomplete{n}').val('');
				}
			}
		}).click(function() { this.select(); });
	});
</script>
