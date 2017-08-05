<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Media/BaseMediaViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2017 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */
 
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/View.php');
 
	class BaseMediaViewer {
		# -------------------------------------------------------
		/**
		 *
		 */
		static public function checkStatus() {
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		static public function getView($po_request) {
			return new View($po_request, $po_request->getViewsDirectoryPath()."/mediaViewers");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		static public function getCallbacks() {
			return self::$s_callbacks;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		static public function prepareViewerHTML($po_request, $o_view, $pa_data=null, $pa_options=null) {
			$t_instance = isset($pa_data['t_instance']) ? $pa_data['t_instance'] : null;
			$t_subject = isset($pa_data['t_subject']) ? $pa_data['t_subject'] : null;
				
			// Controls
			$vs_controls = '';
			if ($t_subject) {
				$vs_media_overlay_titlebar_text = ($vs_media_overlay_titlebar_template = $po_request->config->get('media_overlay_titlebar_template')) ? caProcessTemplateForIDs($vs_media_overlay_titlebar_template, $t_subject->tableName(), [$t_subject->getPrimaryKey()], $pa_options) : caTruncateStringWithEllipsis($t_subject->get($t_subject->tableName().'.preferred_labels'), 80)." (".$t_subject->get($t_subject->tableName().'.'.$t_subject->getProperty('ID_NUMBERING_ID_FIELD')).")";
				$vs_controls .= "<div class='objectInfo'>{$vs_media_overlay_titlebar_text}</div>";
			}
			if ($t_subject && $t_instance && is_a($t_instance, 'ca_object_representations')) {
				if (($vn_num_media = $t_subject->getRepresentationCount()) > 1) {
					$vs_controls .= "<div class='repNav'>";
				
					$va_ids = array_keys($t_subject->getRepresentationIDs());
					$vn_rep_index = array_search($t_instance->getPrimaryKey(), $va_ids);
				
					$vs_context = $po_request->getParameter('context', pString);
					if ($vn_rep_index > 0) { 
						$vs_controls .=  "<a href='#' onClick='jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($po_request, '*', '*', $po_request->getAction(), array('representation_id' => (int)$va_ids[$vn_rep_index - 1], $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'context' => $vs_context))."\");'>←</a>";
					}
				
					$vs_controls .=  ' '._t("%1 of %2", ($vn_rep_index + 1), $vn_num_media).' ';
				
					if ($vn_rep_index < ($vn_num_media - 1)) {
						$vs_controls .=  "<a href='#' onClick='jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($po_request, '*', '*', $po_request->getAction(), array('representation_id' => (int)$va_ids[$vn_rep_index + 1], $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'context' => $vs_context))."\");'>→</a>";
					}
					$vs_controls .= "</div>";	
					
					$o_view->setVar('page', $vn_rep_index);		
				}
			}
			if ($t_subject && $t_instance && $po_request->user->canDoAction('can_download_media') || $po_request->user->canDoAction('can_download_ca_object_representations')) {
					if (is_array($va_versions = $po_request->config->getList('ca_object_representation_download_versions'))) {
						$vs_controls .= "<div class='download'>";
						// -- provide user with a choice of versions to download
						$vs_controls .= caFormTag($po_request, 'DownloadMedia', 'caMediaDownloadForm', $po_request->getModulePath().'/'.$po_request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						$vs_controls .= _t('Download as %1', caHTMLSelect('version', array_combine(array_map("_t", $va_versions), $va_versions), array('style' => 'font-size: 8px; height: 16px;')));
						$vs_controls .= caFormSubmitLink($po_request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1, [], ['color' => 'white']), '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						$vs_controls .= caHTMLHiddenInput($t_subject->primaryKey(), array('value' => $t_subject->getPrimaryKey()));
						if (is_a($t_instance, 'ca_object_representations')) { $vs_controls .= caHTMLHiddenInput("representation_id", array('value' => $t_instance->getPrimaryKey())); }
						if (is_a($t_instance, 'ca_attribute_values')) { $vs_controls .= caHTMLHiddenInput("value_id", array('value' => $t_instance->getPrimaryKey())); }
						$vs_controls .= caHTMLHiddenInput("download", array('value' => 1));
						$vs_controls .= "</form>\n";
						
						if (sizeof($va_ids) > 1) {
							$vs_controls .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".caNavLink($po_request, _t('Download all')." ".caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1, [], ['color' => 'white']), 'xxx', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()]);
						}
						
						$vs_controls .= "</div>\n";
					}

			}
			$o_view->setVar('hideOverlayControls', caGetOption('hideOverlayControls', $pa_options, false));
			$o_view->setVar('controls', $vs_controls);
		
			return $o_view->render(caGetOption('viewerWrapper', $pa_options, 'viewerWrapper').'.php');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function searchViewerData($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
		    throw new ApplicationException(_t('Media search is not available'));
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function autocomplete($po_request, $ps_identifier, $pa_data=null, $pa_options=null) {
		    throw new ApplicationException(_t('Media search autocomplete is not available'));
		}
		# -------------------------------------------------------
	}