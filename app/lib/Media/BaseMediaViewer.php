<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/BaseMediaViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2022 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/View.php');

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
	static public function getView($request) {
		return new View($request, $request->getViewsDirectoryPath()."/mediaViewers");
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
	static public function prepareViewerHTML($request, $o_view, $data=null, $options=null) {
		$t_instance = isset($data['t_instance']) ? $data['t_instance'] : null;
		$t_subject = isset($data['t_subject']) ? $data['t_subject'] : null;
		$t_media = isset($data['t_media']) ? $data['t_media'] : $t_subject;
		$check_access = caGetOption('checkAccess', $options, null);
		
		$display_version = caGetOption('display_version', $data['display'], null);
		$subject_table = $t_subject ? $t_subject->tableName() : null;
		
		// Controls
		$controls = '';
		if ($t_subject) {
			$media_overlay_titlebar_text = null;
			if (($media_overlay_titlebar_template = $request->config->get('media_overlay_titlebar_template')) && (is_a($t_instance, 'BundlableLabelableBaseModelWithAttributes'))) { 
				// for everything except ca_site_page_media when a template is defined
				$media_overlay_titlebar_text = caProcessTemplateForIDs($media_overlay_titlebar_template, $t_instance->tableName(), [$t_instance->getPrimaryKey()], $options);
			} elseif(is_a($t_instance, 'BundlableLabelableBaseModelWithAttributes')) {
				// for everything except ca_site_page_media
				$media_overlay_titlebar_text = caTruncateStringWithEllipsis($t_subject->get($t_instance->tableName().'.preferred_labels'), 80)." (".$t_instance->get($t_subject->tableName().'.'.$t_subject->getProperty('ID_NUMBERING_ID_FIELD')).")";
			} else {
				// for ca_site_page_media 
				$media_overlay_titlebar_text = caTruncateStringWithEllipsis($t_instance->get($t_instance->tableName().'.'.array_shift($t_instance->getProperty('LIST_FIELDS'))), 80)." (".$t_instance->get($t_instance->tableName().'.'.$t_instance->getProperty('ID_NUMBERING_ID_FIELD')).")";
			}
			$controls .= "<div class='objectInfo'>{$media_overlay_titlebar_text}</div>";
		}
		if ($t_subject && $t_instance && is_a($t_instance, 'ca_object_representations')) {
			$rep_ids = is_a($t_media, 'ca_object_representations') ? [$t_media->getPrimaryKey()] : $t_media->getRepresentationIDs(['requireMedia' => true, 'checkAccess' => $check_access]);
			$media_count = is_array($rep_ids) ? sizeof($rep_ids) : 0;
			$context = $request->getParameter('context', pString) ?? caGetOption('context', $options, null);
			if ($media_count > 1) {
				$controls .= "<div class='repNav'>";
			
				$rep_ids = array_keys($rep_ids);
				
				$rep_index = array_search($t_instance->getPrimaryKey(), $rep_ids);
			
				if ($rep_index > 0) { 
					$controls .=  "<a href='#' onClick='jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($request, '*', '*', $request->getAction(), array('representation_id' => (int)$rep_ids[$rep_index - 1], $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'context' => $context))."\");'>←</a>";
				}
			
				$controls .=  ' '._t("%1 of %2", ($rep_index + 1), $media_count).' ';
			
				if ($rep_index < ($media_count - 1)) {
					$controls .=  "<a href='#' onClick='jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($request, '*', '*', $request->getAction(), array('representation_id' => (int)$rep_ids[$rep_index + 1], $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'context' => $context))."\");'>→</a>";
				}
				$controls .= "</div>";	
				
				$o_view->setVar('page', $rep_index);		
			}
			
			$show_next_prev_links = caGetOption('showRepresentationViewerNextPreviousLinks', $options, false);		
			if ($show_next_prev_links && ($o_context = $t_subject ? ResultContext::getResultContextForLastFind($request, $subject_table) : null)) {
				$controls .= "<div class='nextPreviousLinks'>";
				$ids = [];
				if($previous_id = $o_context->getPreviousID($t_subject->getPrimaryKey())) { $ids[] = $previous_id; }
				if($next_id = $o_context->getNextID($t_subject->getPrimaryKey())) { $ids[] = $next_id; }

				$qr = sizeof($ids) ? caMakeSearchResult($subject_table, $ids) : null;
				
				if($previous_id) {
					$nav_previous_template = caGetOption('representationViewerPreviousLink', $options, "← ^{$subject_table}.preferred_labels%truncate=20&ellipsis=1 <ifdef code='{$subject_table}.idno'>(^{$subject_table}.idno)</ifdef>");
					$qr->nextHit();
					$rep_ids = $qr->get('ca_object_representations.representation_id', ['returnAsArray' => true]);
					if(is_array($rep_ids) && sizeof($rep_ids)) {
						$controls .=  "<a href='#' onClick='caMediaPanel.callbackData={url:".json_encode(caDetailUrl($request, $subject_table, $previous_id))."};jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($request, '*', '*', $request->getAction(), array('representation_id' => array_shift($rep_ids), $t_subject->primaryKey() => $previous_id, 'context' => $context))."\");'>".$qr->getWithTemplate($nav_previous_template)."</a>";
					} else {
						$previous_id = null;
					}
				}
				if($next_id) {
					$nav_next_template = caGetOption('representationViewerNextLink', $options, "^{$subject_table}.preferred_labels%truncate=20&ellipsis=1 <ifdef code='{$subject_table}.idno'>(^{$subject_table}.idno)</ifdef> →");
					
					$qr->nextHit();
					$rep_ids = $qr->get('ca_object_representations.representation_id', ['returnAsArray' => true]);
					if(is_array($rep_ids) && sizeof($rep_ids)) {
						$controls .=  ($previous_id ? " | " : "")."<a href='#' onClick='caMediaPanel.callbackData={url:".json_encode(caDetailUrl($request, $subject_table, $next_id))."};jQuery(\"#caMediaPanelContentArea\").load(\"".caNavUrl($request, '*', '*', $request->getAction(), array('representation_id' => array_shift($rep_ids), $t_subject->primaryKey() => $next_id, 'context' => $context))."\");'>".$qr->getWithTemplate($nav_next_template)."</a>";			}
				}
				$controls .= "</div>\n";
			}
			
			$o_view->setVar('original_media_url', $original_media_url = $t_instance->getMediaUrl('media', 'original', []));
			$o_view->setVar('display_media_url', $display_version ? $t_instance->getMediaUrl('media', $display_version, []) : $original_media_url);
		} elseif(is_a($t_instance, 'ca_attribute_values')) {
			$o_view->setVar('original_media_url', $original_media_url = $t_instance->getMediaUrl('value_blob', 'original', []));
			$o_view->setVar('display_media_url', $display_version ? $t_instance->getMediaUrl('value_blob', $display_version, []) : $original_media_url);
		} elseif(is_a($t_instance, 'ca_site_page_media')) {
			$o_view->setVar('original_media_url', $original_media_url = $t_instance->getMediaUrl('media', 'original', []));
			$o_view->setVar('display_media_url', $display_version ? $t_instance->getMediaUrl('media', $display_version, []) : $original_media_url);
		}
		if ($t_subject && $t_instance && ($request->user->canDoAction('can_download_media') || $request->user->canDoAction('can_download_ca_object_representations'))) {
				if (is_array($versions = $request->config->getList('ca_object_representation_download_versions'))) {
					$editor_url = caEditorUrl($request, $t_media->tableName(), $t_media->getPrimaryKey(), true);
					$download_path = $editor_url['module'].'/'.$editor_url['controller'];
				
					$controls .= "<div class='download'>";
					// -- provide user with a choice of versions to download
					$controls .= caFormTag($request, 'DownloadMedia', 'caMediaDownloadForm', $download_path, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
					$controls .= _t('Download as %1', caHTMLSelect('version', array_combine(array_map("_t", $versions), $versions), array('style' => 'font-size: 8px; height: 16px;')));
					$controls .= caFormSubmitLink($request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1, [], ['color' => 'white']), '', 'caMediaDownloadForm', 'caMediaDownloadFormButton', ['aria-label' => _t('Download media representations')]);
					$controls .= caHTMLHiddenInput($t_media->primaryKey(), array('value' => $t_media->getPrimaryKey()));
					if (is_a($t_instance, 'ca_object_representations')) { $controls .= caHTMLHiddenInput("representation_id", array('value' => $t_instance->getPrimaryKey())); }
					if (is_a($t_instance, 'ca_site_page_media')) { $controls .= caHTMLHiddenInput("media_id", array('value' => $t_instance->getPrimaryKey())); }
					if (is_a($t_instance, 'ca_attribute_values')) { $controls .= caHTMLHiddenInput("value_id", array('value' => $t_instance->getPrimaryKey())); }
					$controls .= caHTMLHiddenInput("download", array('value' => 1));
					$controls .= "</form>\n";
					
					if (is_array($rep_ids) && (sizeof($rep_ids) > 1)) {
						$controls .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".caNavLink($request, _t('Download all')." ".caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1, [], ['color' => 'white']), 'xxx', '*', '*', 'DownloadMedia', [$t_subject->primaryKey() => $t_subject->getPrimaryKey()]);
					}
					
					$controls .= "</div>\n";
				}

		}
		$o_view->setVar('hideOverlayControls', caGetOption('hideOverlayControls', $options, false));
		$o_view->setVar('controls', $controls);
	
		return $o_view->render(caGetOption('viewerWrapper', $options, 'viewerWrapper').'.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function searchViewerData($request, $identifier, $data=null, $options=null) {
		throw new ApplicationException(_t('Media search is not available'));
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function autocomplete($request, $identifier, $data=null, $options=null) {
		throw new ApplicationException(_t('Media search autocomplete is not available'));
	}
	# -------------------------------------------------------
}
