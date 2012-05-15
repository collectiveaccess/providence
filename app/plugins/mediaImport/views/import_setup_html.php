<?php
/* ----------------------------------------------------------------------
 * plugins/mediaImport/views/import_setup_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
 	if (!sizeof($this->getVar('directory_list'))) {
 ?>
 	<h1><?php print _t("There is no media available for import"); ?></h1>
 	<h2><?php print _t("Upload media for <em>%1</em> and then retry", $this->getVar('directory_path')); ?></h2>
 <?php
 		return;
 	}
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Start import"), 'mediaImporterForm').' '.
		caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), $this->request->getModulePath(), $this->request->getController(), 'Index', array()), 
		'', 
		''
	);
 ?>
<div class="sectionBox">
<?php
	print caFormTag($this->request, 'Import', 'mediaImporterForm', null, 'POST', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));												
?>
		<h1><?php print _t('Batch media importer'); ?></h1>
		
		<div class="formLabel">
			<?php print _t('Import mode'); ?><br/>
			<select name="import_mode">				
				<option value="no_new_objects"><?php print _t("Skip media that do not match an existing object record"); ?></option>
				<option value="new_objects_as_needed"><?php print _t("Create new object record for media if a matching one does not already exist"); ?></option>				
			</select>
		</div>
		<div class="formLabel">
			<?php print _t('Directory to import media from')."<br/>".caHTMLSelect('directory', $this->getVar('directory_list')); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Delete media from directory after it is imported')."<br/>".caHTMLCheckboxInput('delete_media_after_import', array('checked' => '1', 'value' => '1')); ?>
		</div>
		<hr/>
		<div class="formLabel">
			<?php print _t('Object identifer-to-filename matching mode')."<br/>".caHTMLSelect('matching_mode', array_merge(array(_t('[Try each mode until match is found]') => '*'), $this->getVar('regex_list'))); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create new object records with type')."<br/>".$this->getVar('object_type_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create new object records with status')."<br/>".$this->getVar('object_status_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create new object records with access')."<br/>".$this->getVar('object_access_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create new object records with locale')."<br/>".$this->getVar('object_locale_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Set identifier for newly created objects to')."<br/>".$this->getVar('object_idno_control'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Option for object identifer'); ?><br/>
			<select name="object_idno_option">
				<option value=""><?php print _t("None"); ?></option>
				<option value="use_filename_as_identifier"><?php print _t("Use filename as identifier rather than set value"); ?></option>
			</select>
		</div>
		<hr/>
		<div class="formLabel">
			<?php print _t('Create object representation (media) records with type')."<br/>".$this->getVar('object_representation_type_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create object representation (media) records with status')."<br/>".$this->getVar('object_representation_status_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create object representation (media) records with access')."<br/>".$this->getVar('object_representation_access_list'); ?>
		</div>
		<div class="formLabel">
			<?php print _t('Create object representation (media) records with locale')."<br/>".$this->getVar('object_representation_locale_list'); ?>
		</div>
		
	</form>
</div>

<div class="editorBottomPadding"><!-- empty --></div>