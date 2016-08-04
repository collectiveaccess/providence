<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/bookviewer_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2014 Whirl-i-Gig
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
 	
 	$t_subject	 				= $this->getVar('t_subject');
 	$vn_subject_id 				= (int)$t_subject->getPrimaryKey();
 	
 	$t_rep				 		= $this->getVar('t_representation');
 	$vn_representation_id		= $t_rep ? (int)$t_rep->getPrimaryKey() : null;
 	
 	$t_value				 	= $this->getVar('t_attribute_value');
 	$vn_value_id		 		= $t_value ? (int)$t_value->getPrimaryKey() : null;
 	
 	$vs_content_mode 			= $this->getVar('content_mode');
 	$va_sections	 			= $this->getVar('sections');
 	$vs_display_type 			= $this->getVar('display_type');
 	$va_display_options 		= $this->getVar('display_options');
 	if (($vn_initial_page = $this->getVar('initial_page')) <= 0) {
 		$vn_initial_page = 1;
 	}
?>
<div id="BookReader_<?php print $vn_subject_id.'_'.($vn_representation_id ? $vn_representation_id : $vn_value_id).'_'.$vs_display_type; ?>">
    <noscript>
    	<p><?php print _t('The BookReader requires JavaScript to be enabled. Please check that your browser supports JavaScript and that it is enabled in the browser settings.'); ?></p>
    </noscript>
</div>
<script type="text/javascript">
<?php
	$va_url = caEditorUrl($this->request, $t_subject->tableName(), $vn_subject_id, true);
	if ($vn_representation_id > 0) {
		// displaying representation
?>
	var caBookReader = caUI.initBookReader({
		containerID: 'BookReader_<?php print $vn_subject_id.'_'.$vn_representation_id.'_'.$vs_display_type; ?>',	
		docURL: '<?php print caNavUrl($this->request, $va_url['module'], $va_url['controller'], 'GetPageListAsJSON', array($va_url['_pk'] => $vn_subject_id, 'representation_id' => $vn_representation_id, 'content_mode' => $vs_content_mode, 'download' => 1)); ?>/data/documentData.json',
		page: <?php print $vn_initial_page; ?>,
		sidebar: <?php print ((sizeof($va_sections) > 0) && !isset($va_display_options['no_overlay'])) ? "true" : "false"; ?>,
		closeButton: '<?php print (!isset($va_display_options['no_overlay'])) ? '<img src="'.$this->request->getThemeUrlPath().'/graphics/buttons/x.png" alt="'._t('Close').'"/>' : ''; ?>',
		editButton: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/edit.png" alt="<?php print _t('Edit'); ?>"/>',
<?php
		if($this->request->getUser()->canDoAction('can_download_ca_object_representations')) {
?>
		downloadButton: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/download.png" alt="<?php print _t('Download'); ?>"/>',
<?php
		}
?>
		sectionsAreSelectable: <?php print ((sizeof($va_sections) > 0) && isset($va_display_options['sectionsAreSelectable']) && ($va_display_options['sectionsAreSelectable'])) ? "true" : "false"; ?>

	});
<?php
	} elseif ($vn_value_id > 0) {
		// displaying media attribute
?>
	var caBookReader = caUI.initBookReader({
		containerID: 'BookReader_<?php print $vn_subject_id.'_'.$vn_value_id.'_'.$vs_display_type; ?>',	
		docURL: '<?php print caNavUrl($this->request, $va_url['module'], $va_url['controller'], 'GetPageListAsJSON', array($va_url['_pk'] => $vn_subject_id, 'value_id' => $vn_value_id, 'content_mode' => $vs_content_mode, 'download' => 1)); ?>/data/documentData.json',
		page: <?php print $vn_initial_page; ?>,
		sidebar: <?php print ((sizeof($va_sections) > 0) && !isset($va_display_options['no_overlay'])) ? "true" : "false"; ?>,
		closeButton: '<?php print (!isset($va_display_options['no_overlay'])) ? '<img src="'.$this->request->getThemeUrlPath().'/graphics/buttons/x.png" alt="'._t('Close').'"/>' : ''; ?>',
		editButton: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/edit.png" alt="<?php print _t('Edit'); ?>"/>',
		downloadButton: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/download.png" alt="<?php print _t('Download'); ?>"/>',
		sectionsAreSelectable: <?php print ((sizeof($va_sections) > 0) && isset($va_display_options['sectionsAreSelectable']) && ($va_display_options['sectionsAreSelectable'])) ? "true" : "false"; ?>

	});
<?php
	}
?>
</script>