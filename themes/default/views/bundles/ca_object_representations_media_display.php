<?php
/* ----------------------------------------------------------------------
 * bundles/ca_object_representations_media_display.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2024 Whirl-i-Gig
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
$id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
$t_subject 			= $this->getVar('t_subject');		// object_representation
$settings			= $this->getVar('settings');

$num_multifiles 	= $this->getVar('representation_num_multifiles');
$num_multifiles 	= (($num_multifiles > 0) ? (($num_multifiles == 1) ? _t('+ 1 additional preview') : _t('+ %1 additional previews', $num_multifiles)) : '');

$allow_fetching_from_urls = $this->request->getAppConfig()->get('allow_fetching_of_media_from_remote_urls');
$media_is_set		= is_array($t_subject->getMediaInfo('media'));

print caEditorBundleShowHideControl($this->request, $id_prefix);
print caEditorBundleMetadataDictionary($this->request, $id_prefix, $settings);
?>
<div id="<?= $id_prefix; ?>">
	<div class="bundleContainer" style="padding-bottom: 5px;">
		<div class='bundleSubLabel'>
			<table  style="width: 100%;">
				<tr>
					<td>
						<div id="<?= "{$id_prefix}"; ?>_media_upload_control">
							<?= $t_subject->htmlFormElement('media', null, array('displayMediaVersion' => null, 'name' => "{$id_prefix}_media", 'id' => "{$id_prefix}_media", "value" => "", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_object_representations_media_display')); ?>
<?php
	if ($allow_fetching_from_urls) {
?>		
							<a href='#' onclick='jQuery("#<?= "{$id_prefix}"; ?>_media_url_control").slideDown(200); jQuery("#<?= "{$id_prefix}"; ?>_media_upload_control").slideUp(200); return false'><?= _t('or fetch from a URL'); ?></a>
<?php
	}
?>
						</div>
<?php
	if ($allow_fetching_from_urls) {
?>
						<div id="<?= "{$id_prefix}"; ?>_media_url_control" style="display: none;">
							<?= _t('Fetch from URL').':<br/>'.caHTMLTextInput("{$id_prefix}_url", array('id' => "{$id_prefix}_url"), array('width' => 40, 'height' => '3')); ?>
							<br/>
							<a href='#' onclick='jQuery("#<?= "{$id_prefix}"; ?>_media_url_control").slideUp(200); jQuery("#<?= "{$id_prefix}"; ?>_media_upload_control").slideDown(200); return false'><?= _t('or upload a file'); ?></a>
						</div>
<?php
	}
?>
					</td>
					<td>
						<?= $this->getVar("representation_typename"); ?>
						<?= caGetRepresentationDimensionsForDisplay($t_subject, 'original', array()); ?>
						<?= $num_multifiles; ?>
					</td>
					<td class="objectRepresentationListItemImage">
						<div class="objectRepresentationListItemImageThumb">
<?php
	if ($media_is_set) {
?>						
							<div style="float:right; margin-left: 5px;">
								<?= urldecode(caNavButton($this->request, __CA_NAV_ICON_DOWNLOAD__, _t('Download'), '', '*', '*', 'DownloadMedia', array('version' => 'original', 'representation_id' => $t_subject->getPrimaryKey(), 'download' => 1), array('id' => "{$id_prefix}download"), array('no_background' => true, 'dont_show_content' => true))); ?>
							</div>
<?php
	}
?>
							<a href="#" onclick="caMediaPanel.showPanel('<?= urldecode(caNavUrl($this->request, '*', '*', 'GetMediaOverlay', array('representation_id' => $t_subject->getPrimaryKey()))); ?>'); return false;"><?= $t_subject->getMediaTag('media', 'preview170'); ?></a>
						</div>
					</td>
				</tr>
			</table>
<?php
	if ($media_is_set) {
?>
			<div class="objectRepresentationMediaDisplayDerivativeOptionsContainer" id="<?= "{$id_prefix}_derivative_options_container"; ?>">
		
<?php
			print caHTMLCheckboxInput("{$id_prefix}_derivative_options_selector", array('value' => '1', 'onclick' => "jQuery('#{$id_prefix}_derivative_options').slideToggle(250);")).' '._t('Modify preview images');
?>
				<div class="objectRepresentationMediaDisplayDerivativeOptions rounded" id="<?= "{$id_prefix}_derivative_options"; ?>">
					<div class="objectRepresentationMediaDisplayDerivativeHeader"><?= _t("Update source").":"; ?></div>
	
					<table>
						<tr>
							<td><?php
								print caHTMLRadioButtonInput("{$id_prefix}_derivative_options_mode", array('value' => 'file', 'checked' => '1', 'id' => "{$id_prefix}_derivative_options_mode_file")).' '._t('Update with uploaded media');
							?></td>
							<td>
							
							</td>
						</tr>
<?php
				switch($t_subject->getAnnotationType()) {
					case 'TimeBasedVideo':
?>
						<tr>
							<td><?php
								print caHTMLRadioButtonInput("{$id_prefix}_derivative_options_mode", array('value' => 'timecode', 'id' => "{$id_prefix}_derivative_options_mode_timecode")).' '._t('Update using frame at timecode').' ';
								print caHTMLTextInput("{$id_prefix}_derivative_options_mode_timecode_value", array('value' => $t_subject->getMediaInfo('media', '_START_AT_TIME'), 'id' => "{$id_prefix}_derivative_options_mode_timecode_value", 'class' => 'timecodeBg', 'onclick' => "jQuery('#{$id_prefix}_derivative_options_mode_timecode').attr('checked', '1');"), array("width" => 30, "height" => 1));
							?></td>
							<td>
							
							</td>
						</tr>
<?php
					break;
					case 'Document':
?>
						<tr>
							<td><?php
								print caHTMLRadioButtonInput("{$id_prefix}_derivative_options_mode", array('value' => 'page', 'id' => "{$id_prefix}_derivative_options_mode_page")).' '._t('Update using page #').' ';
								print caHTMLTextInput("{$id_prefix}_derivative_options_mode_page_value", array('value' => $t_subject->getMediaInfo('media', '_START_AT_PAGE'), 'id' => "{$id_prefix}_derivative_options_mode_page_value", 'onclick' => "jQuery('#{$id_prefix}_derivative_options_mode_page').attr('checked', '1');"), array("width" => 4, "height" => 1));
							?></td>
							<td>
							
							</td>
						</tr>
<?php
						break;
				}
?>
				</table>
		
				<div class="objectRepresentationMediaDisplayDerivativeHeader"><?= _t("Update preview versions").":"; ?></div>
			
				<table width="100%">
<?php	
				$i = 0;
				TooltipManager::setNamespaceCSSClass("{$id_prefix}_media_tooltips", "resizeableTooltip");
				foreach($t_subject->getMediaVersions('media') as $version) {
					if($t_subject->getMediaInputTypeForVersion('media', $version) != 'image') { continue; }	// skip non-image versions
					if (!$i) { print "<tr>"; }
					print "<td>".caHTMLCheckboxInput($id_prefix.'_set_versions[]', array('value' => $version, 'checked' => '1'));
					print "<span id='{$id_prefix}_media_{$version}_label'>{$version} (".$t_subject->getMediaInfo('media', $version, 'WIDTH').'x'.$t_subject->getMediaInfo('media', $version, 'HEIGHT').")</span>";
					print "</td>";
					
					TooltipManager::add("#{$id_prefix}_media_{$version}_label", $t_subject->getMediaTag('media', $version), "{$id_prefix}_media_tooltips");
					
					$i++;
					if ($i > 2) {
						print "</tr>\n";
						$i = 0;
					}
				}
				
				if ($i > 0) {
					print "</tr>\n";
				}
?>
				</table>
				<div class="objectRepresentationMediaDisplayDerivativeHelpText" id="<?= "{$id_prefix}_derivative_options_help_text"; ?>">
					<?= _t("Use the controls above to replace existing preview images for this representation. If <em>Update with uploaded media</em> is checked then the media you have selected for upload will be used to generate the replacement previews. For PDF and video representations you may alternatively elect to generate new previews from a specific page or frame using the <em>Update using page</em> and <em>Update using frame at timecode</em> options. You can control which preview versions are generated by checking or unchecking options in the <em>Update preview versions</em> section. Note that replacement of preview images will be performed only if the master <em>Modify preview images</em> checkbox is checked. If unchecked the uploaded media will completely replace <strong>all</strong> media and previews associated with this representation."); ?>
				</div>
			</div>
		</div>
<?php
	}
?>
		</div>
	</div>
</div>
<?php
print TooltipManager::getLoadHTML("{$id_prefix}_media_tooltips");
