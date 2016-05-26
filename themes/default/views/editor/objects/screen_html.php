<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/screen_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 	$t_object 			= $this->getVar('t_subject');
	$vn_object_id 		= $this->getVar('subject_id');
	$vn_above_id 		= $this->getVar('above_id');

	$vb_can_edit	 	= $t_object->isSaveable($this->request);
	$vb_can_delete		= $t_object->isDeletable($this->request);

	$vs_rel_table		= $this->getVar('rel_table');
	$vn_rel_type_id		= $this->getVar('rel_type_id');
	$vn_rel_id			= $this->getVar('rel_id');
	
	if ($vb_can_edit) {
		$va_cancel_parameters = ($vn_object_id ? array('object_id' => $vn_object_id) : array('type_id' => $t_object->getTypeID()));
		print $vs_control_box = caFormControlBox(
			caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'ObjectEditorForm').' '.
			($this->getVar('show_save_and_return') ? caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save and return"), 'ObjectEditorForm', array('isSaveAndReturn' => true)) : '').' '.
			caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), '', 'editor/objects', 'ObjectEditor', 'Edit/'.$this->request->getActionExtra(), $va_cancel_parameters),
			'', 
			((intval($vn_object_id) > 0) && $vb_can_delete) ? caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'form-button deleteButton', 'editor/objects', 'ObjectEditor', 'Delete/'.$this->request->getActionExtra(), array('object_id' => $vn_object_id)) : ''
		);
	}
?>
	<div class="sectionBox">
<?php

			print caFormTag($this->request, 'Save/'.$this->request->getActionExtra().'/object_id/'.$vn_object_id, 'ObjectEditorForm', null, 'POST', 'multipart/form-data');
		
			$va_bundle_list = array();
			$va_form_elements = $t_object->getBundleFormHTMLForScreen($this->request->getActionExtra(), array(
									'request' => $this->request, 
									'formName' => 'ObjectEditorForm',
									'forceHidden' => array('lot_id')
								), $va_bundle_list);
			
			print join("\n", $va_form_elements);
			
			if ($vb_can_edit) { print $vs_control_box; }
?>
			<input type='hidden' name='object_id' value='<?php print $vn_object_id; ?>'/>
			<input type='hidden' name='collection_id' value='<?php print $this->request->getParameter('collection_id', pInteger); ?>'/>
			<input type='hidden' name='above_id' value='<?php print $vn_above_id; ?>'/>
			<input id='isSaveAndReturn' type='hidden' name='is_save_and_return' value='0'/>
			<input type='hidden' name='rel_table' value='<?php print $vs_rel_table; ?>'/>
			<input type='hidden' name='rel_type_id' value='<?php print $vn_rel_type_id; ?>'/>
			<input type='hidden' name='rel_id' value='<?php print $vn_rel_id; ?>'/>
<?php
			if($this->request->getParameter('rel', pInteger)) {
?>
				<input type='hidden' name='rel' value='1'/>
<?php
			}
?>
		</form>
	</div>

	<div class="editorBottomPadding"><!-- empty --></div>
	
	<?php print caSetupEditorScreenOverlays($this->request, $t_object, $va_bundle_list); ?>
	<script type="text/javascript">
		jQuery(function () {
			var parse = function (val) {
				if (typeof val === 'string') {
					val = val.trim();
					if (val) {
						var parts = val.match(/^\.?\s?(\d*)\s*(.*)$/);
						parts.shift();
						return parts;
					}
				}
				return undefined;
			};
			jQuery('#idno_accession_series_and_parts').each(function () {
				var $dot = jQuery('<span class="idno_dot">.</span>').hide(),
					$series = jQuery('<input type="number" class="idno_accession_series" title="Enter series number" step="1" min="1"/>'),
					$suffix = jQuery('<input type="text" class="idno_accession_suffix" size="10"/>'),
					$this = jQuery(this).hide().before($dot).before($series).before($suffix),
					seriesAndParts = parse($this.val()),
					events = 'keyup change',
					serialize = function () {
						var ret = '',
							series = $series.val().trim();
						if (series) {
							$dot.show();
							ret += '.' + series;
						} else {
							$dot.hide();
						}
						ret += $suffix.val().trim();
						$this.val(ret);
					};
				if (seriesAndParts) {
					if (seriesAndParts[0] >= 1) {
						$dot.show();
						$series.val(seriesAndParts[0]);
					}
					$suffix.val(seriesAndParts[1]);
				}
				jQuery('#idno_accession_year,#idno_accession_number,.idno_accession_series').on('keypress', function (evt) {
					if (
						// Space
					evt.which === 32 ||
						// Non-numeric keys
					evt.which > 57) {
						evt.preventDefault();
					}
				});
				$series.on(events, serialize);
				$suffix.on(events, serialize);
			});
		});
	</script>
