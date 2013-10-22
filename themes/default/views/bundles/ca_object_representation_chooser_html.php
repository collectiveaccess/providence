<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representation_chooser_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_subject 			= $this->getVar('t_subject');	// the object-to-whatever relationship
	$t_object 			= $this->getVar('t_object');	// the object
	$va_settings		= $this->getVar('settings');
	$va_reps 			= $t_object->getRepresentations();
	
	$vb_read_only		= (bool)caGetOption('readonly', $va_settings, false);
	
	if ($vs_element_code	= $this->getVar('element_code')) {
		if(!is_array($va_selected_rep_ids = $t_subject->get($vs_element_code, array('returnAsArray' => true, 'idsOnly' => true)))) { $va_selected_rep_ids = array(); }
		$va_selected_rep_ids = caExtractValuesFromArrayList($va_selected_rep_ids, $vs_element_code, array('preserveKeys' => false));
?>

	<div class="caObjectRepresentationChooserContainer" id="<?php print $vs_id_prefix; ?>caObjectRepresentationChooserContainer">
<?php
		foreach($va_reps as $va_rep) {
			$va_attributes = array('value' => $va_rep['representation_id']);
			if (in_array($va_rep['representation_id'], $va_selected_rep_ids)) { $va_attributes['checked'] = 1; }
?>
		<div class="caObjectRepresentationChooserRepresentation">
			<?php print $va_rep['tags']['preview170']; ?>
			<?php if (!$vb_read_only) { print caHTMLCheckboxInput("{$vs_id_prefix}[]", $va_attributes); } ?>
		</div>
<?php
		}
?>
		<br class="clear"/>
	</div>
<?php
	} else {
?>
	<div class="caObjectRepresentationChooserContainer" id="<?php print $vs_id_prefix; ?>caObjectRepresentationChooserContainer">
		<?php print _t("No metadata element is configured"); ?>
	</div>	
<?php
	}
?>