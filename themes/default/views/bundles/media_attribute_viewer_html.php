<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/ajax_object_representation_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
	$t_value 					= $this->getVar('t_attribute_value');
	$va_versions 				= $this->getVar('versions');	
	
	$va_display_options	 		= $this->getVar('display_options');
	$vs_show_version 			= $this->getVar('version') ? $this->getVar('version') : "medium";
	
	$vs_container_id 			= $this->getVar('containerID');
	
	// Get filename of originally uploaded file
	//$va_media_info 			= $t_rep->getMediaInfo('media');
	//$vs_original_filename 	= $va_media_info['ORIGINAL_FILENAME'];			
?>
	<!-- Controls -->
	<div class="caMediaOverlayControls">
		<table width="95%">
			<tr valign="middle">
				<td align="left">
					<form>
<?php
						print _t('Display %1 version', caHTMLSelect('version', $va_versions, array('id' => 'caMediaOverlayVersionControl', 'class' => 'caMediaOverlayControls'), array('value' => $vs_show_version)));
						$va_rep_info = $this->getVar('version_info');

						if (($this->getVar('version_type')) && ($va_rep_info['WIDTH'] > 0) && ($va_rep_info['HEIGHT'] > 0)) {
							print " (".$this->getVar('version_type')."; ". $va_rep_info['WIDTH']." x ". $va_rep_info['HEIGHT']."px)";
						}							
?>
					</form>
					
				</td>
				<td align="right" text-align="right">
<?php 
					print caFormTag($this->request, 'DownloadMedia', 'downloadMediaForm', $this->request->getModulePath().'/'.$this->request->getController(), 'get', 'multipart/form-data', null, array('disableUnsavedChangesWarning' => true));
					print caHTMLSelect('version', $va_versions, array('id' => 'caMediaOverlayVersionControl', 'class' => 'caMediaOverlayControls'), array('value' => 'original'));
					print ' '.caFormSubmitLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_DOWNLOAD__, null, array('align' => 'middle')), '', 'downloadMediaForm');
					print caHTMLHiddenInput('value_id', array('value' => $t_value->getPrimaryKey()));
					print caHTMLHiddenInput('download', array('value' => 1));
?>
					</form>
				</td>
			</tr>
		</table>
	</div><!-- end caMediaOverlayControls -->

	<div id="caMediaOverlayContent">
<?php
	// return standard tag
	$t_value->useBlobAsMediaField(true);
	print $t_value->getMediaTag('value_blob', $vs_show_version, array_merge($va_display_options, array(
		'id' => 'caMediaOverlayContentMedia', 
		'viewer_base_url' => $this->request->getBaseUrlPath()
	)));
?>
	</div><!-- end caMediaOverlayContent -->
<script type="text/javascript">
	jQuery('#caMediaOverlayVersionControl').change(
		function() {
			var containerID = jQuery(this).parents(':eq(6)').attr('id'); 
			jQuery("#<?php print $vs_container_id; ?>").load("<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'GetMediaInfo', array('value_id' => (int)$t_value->getPrimaryKey(), 'version' => '')); ?>" + this.value);
		}
	);
</script>