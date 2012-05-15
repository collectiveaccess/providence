<?php
/* ----------------------------------------------------------------------
 * app/views/admin/access/role_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2010 Whirl-i-Gig
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

	$t_locale = $this->getVar('t_locale');
	$vn_locale_id = $this->getVar('locale_id');
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'LocalesForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'administrate/setup', 'Locales', 'ListLocales', array('locale_id' => 0)), 
		'', 
		caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'administrate/setup', 'Locales', 'Delete', array('locale_id' => $vn_locale_id))
	);
?>
<?php
	print caFormTag($this->request, 'Save', 'LocalesForm');

		foreach($t_locale->getFormFields() as $vs_f => $va_locale_info) {
			print $t_locale->htmlFormElement($vs_f, null, array('field_errors' => $this->request->getActionErrors('field_'.$vs_f)));
		}
?>
	</form>
</div>

<div class="editorBottomPadding"><!-- empty --></div>