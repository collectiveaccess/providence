<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/representation_viewer_html.php : 
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
	$t_subject 					= $this->getVar('t_subject');					// the record to which the media is attached

	$t_rep 						= $this->getVar('t_representation');			// representation media is in (if displaying representations)
	$t_value					= $this->getVar('t_attribute_value');			// the attribute value the media is in (if displaying a media attribute value)
	$vn_representation_id 		= $t_rep ? $t_rep->getPrimaryKey() : null;
	$vn_value_id				= $t_value ? $t_value->getPrimaryKey() : null;
	
	$va_reps 					= $this->getVar('reps');							// list of representations to display (if displaying representations)
	
	$t_set_item 				= $this->getVar('t_set_item');						// ca_set_items instance (if being used with selectable representations in a set item)
	
	$va_versions 				= $this->getVar('versions');						// available media versions
	
	$vs_mimetype = $vn_representation_id ? $t_rep->get('mimetype') : $t_value->getMediaInfo('value_blob', 'original', 'MIMETYPE'); // mimetype of media to display
	
	$vs_display_type		 	= $this->getVar('display_type');
	$va_display_options		 	= $this->getVar('display_options');
	$vs_show_version 			= $this->getVar('version');
	
	// Get filename of originally uploaded file
	if ($vn_representation_id) {
		$va_media_info 				= $t_rep->getMediaInfo('media');
		$vs_original_filename 		= $va_media_info['ORIGINAL_FILENAME'];
	}
	
	$vs_container_id 			= $this->getVar('containerID');
	$vb_use_media_editor		= $this->getVar('use_media_editor');
	$vb_no_controls				= $this->getVar('noControls');
	
	$va_pages = $va_sections = array();
	$vb_use_book_reader = false;
	$vb_should_use_native_pdf_viewer = false;
	
	$vn_open_to_page = 1;
	
	if ($vs_mimetype ==  'application/pdf') {
		switch($vs_pdf_pref = $this->request->user->getPreference('pdf_viewer')) {
			case 'native_plus_book_viewer':
				if ($this->request->session->getVar('has_pdf_plugin')) { 
					$vb_should_use_native_pdf_viewer = true;
				} else {
					$vb_should_use_book_viewer = true;	
				}
				break;
			case 'book_viewer':
				$vb_should_use_book_viewer = true;
				break;
			default:
				$vb_should_use_book_viewer = isset($va_display_options['use_book_viewer']) && (bool)$va_display_options['use_book_viewer'];
				break;
		}
	} else {
		$vb_should_use_book_viewer = isset($va_display_options['use_book_viewer']) && (bool)$va_display_options['use_book_viewer'];
		$vb_should_use_native_pdf_viewer = false;
	}
	
	$va_url = caEditorUrl($this->request, $t_subject->tableName(), $t_subject->getPrimaryKey(), true);
	if($vn_representation_id > 0) {
		// displaying representation
		$vs_book_viewer_content_mode = null;
		$vn_subject_id = $vn_rep_id = null;
		if (
			$vb_should_use_book_viewer 
			&&
			isset($va_display_options['use_book_viewer_when_number_of_representations_exceeds']) 
			&& 
			((int)$va_display_options['use_book_viewer_when_number_of_representations_exceeds'] < sizeof($va_reps))
		) {
			// Create book viewer from multiple representations
			$va_reps = $t_subject->getRepresentations(array('thumbnail', 'large', 'page'));
			$vn_subject_id = $t_subject->getPrimaryKey();
		
			$vn_c = 1;
		
			$vb_set_is_loaded = false;
			$va_selected_reps = array();
			if ($t_set_item && ($vb_set_is_loaded = $t_set_item->getPrimaryKey())) {
				$va_selected_reps = $t_set_item->getSelectedRepresentationIDs();
			} 
			
			foreach($va_reps as $vn_id => $va_file) {
				$va_pages[] = array(
					'subject_id' => $vn_subject_id, 'representation_id' => $vn_id,
					'thumbnail_url' => $va_file['urls']['thumbnail'], 'thumbnail_path' => $va_file['paths']['thumbnail'], 'thumbnail_width' => $va_file['info']['thumbnail']['WIDTH'], 'thumbnail_height' => $va_file['info']['thumbnail']['HEIGHT'], 'thumbnail_mimetype' => $va_file['info']['thumbnail']['MIMETYPE'],
					'normal_url' => $va_file['urls']['large'], 'normal_path' => $va_file['paths']['large'], 'normal_width' => $va_file['info']['large']['WIDTH'], 'normal_height' => $va_file['info']['large']['HEIGHT'], 'normal_mimetype' => $va_file['info']['large']['MIMETYPE'],
					'large_url' => $va_file['urls']['page'], 'large_path' => $va_file['paths']['page'], 'large_width' => $va_file['info']['page']['WIDTH'], 'large_height' => $va_file['info']['page']['HEIGHT'], 'large_mimetype' => $va_file['info']['page']['MIMETYPE']
				);
			
				$vs_title = (isset($va_file['label']) && trim($va_file['label'])) ? $va_file['label'] : _t('Page %1', $vn_c);
			
				if ($vb_set_is_loaded) {
					$vn_selected = (isset($va_selected_reps[$vn_id]) && $va_selected_reps[$vn_id]) ? 1 : 0;
				} else {
					$vn_selected = 0;
				}
				
				$va_sections[] = array(
					'title' => $vs_title, 
					'page' => $vn_c, 
					'subject_id' => $vn_subject_id, 
					'representation_id' => $vn_id,
					'isSelected' => $vn_selected, 
					'editUrl' => caEditorUrl($this->request, 'ca_object_representations', $vn_id), 
					'downloadUrl' => caNavUrl($this->request, $va_url['module'], $va_url['controller'], 'DownloadMedia', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'representation_id' => $vn_id, 'value' => $vn_value_id, 'version' => 'original'))
				);
				$vn_c++;
			}

			$vs_book_viewer_content_mode = 'multiple_representations';
			$vb_use_book_reader = true;
		} else {
			if (
				$vb_should_use_book_viewer
				&& 
				(sizeof($va_reps) == 1)
				&&
				(
					((isset($va_display_options['show_hierarchy_in_book_viewer'])
					&& 
					(bool)$va_display_options['show_hierarchy_in_book_viewer']))
					||
					((isset($va_display_options['show_subhierarchy_in_book_viewer'])
					&& 
					(bool)$va_display_options['show_subhierarchy_in_book_viewer']))
				)
				&&
				($va_ancestor_ids = $t_subject->getHierarchyAncestors(null, array('idsOnly' => true)))
			) {
				$vn_parent_id = array_pop($va_ancestor_ids);
			
				$vn_page_id = $t_subject->getPrimaryKey();
				$t_subject->load($vn_parent_id);
				$va_child_ids = $t_subject->getHierarchyChildren(null, array('idsOnly' => true));
			
				foreach($va_ancestor_ids as $vn_id) {
					array_unshift($va_child_ids, $vn_id);
				}
				$o_children = $t_subject->makeSearchResult('ca_objects', $va_child_ids);
			
				$vn_c = 1;
				while($o_children->nextHit()) {
					if (!($vn_representation_id = (int)$o_children->get('ca_object_representations.representation_id'))) { continue; }
				
					$vs_thumbnail_url = $o_children->getMediaUrl('ca_object_representations.media', 'thumbnail');
					$vs_thumbnail_path = $o_children->getMediaPath('ca_object_representations.media', 'thumbnail');
					$va_thumbnail_info = $o_children->getMediaInfo('ca_object_representations.media', 'thumbnail');
					$vs_large_url = $o_children->getMediaUrl('ca_object_representations.media', 'large');
					$vs_large_path = $o_children->getMediaPath('ca_object_representations.media', 'large');
					$va_large_info = $o_children->getMediaInfo('ca_object_representations.media', 'large');
					$vs_page_url = $o_children->getMediaUrl('ca_object_representations.media', 'page');
					$vs_page_path = $o_children->getMediaPath('ca_object_representations.media', 'page');
					$va_page_info = $o_children->getMediaInfo('ca_object_representations.media', 'page');
				
					$vn_subject_id = (int)$o_children->get($t_subject->tableName().'.'.$t_subject->primaryKey());
					$va_pages[$vn_subject_id] = array(
						'title' => $vs_title = $o_children->get('ca_objects.preferred_labels.name'),
						'subject_id' => $vn_subject_id, 'representation_id' => $vn_representation_id,
						'thumbnail_url' => $vs_thumbnail_url, 'thumbnail_path' => $vs_thumbnail_path, 'thumbnail_width' => $va_thumbnail_info['WIDTH'], 'thumbnail_height' => $va_thumbnail_info['HEIGHT'], 'thumbnail_mimetype' => $va_thumbnail_info['MIMETYPE'],
						'normal_url' => $vs_large_url, 'normal_path' => $vs_large_path, 'normal_width' => $va_large_info['WIDTH'], 'normal_height' => $va_large_info['HEIGHT'], 'normal_mimetype' => $va_large_info['MIMETYPE'],
						'large_url' => $vs_page_url, 'large_path' => $vs_page_path, 'large_width' => $va_page_info['WIDTH'], 'large_height' => $va_page_info['HEIGHT'], 'large_mimetype' => $va_page_info['MIMETYPE']
					);
					$va_sections[$vn_subject_id] = array(
						'title' => $vs_title, 
						'page' => $vn_c, 
						'subject_id' => $vn_subject_id, 
						'representation_id' => $vn_representation_id, 
						'isSelected' => 0,
						'editUrl' => caEditorUrl($this->request, $t_subject->tableName(), $vn_subject_id), 
						'downloadUrl' => caNavUrl($this->request, $va_url['module'], $va_url['controller'], 'DownloadMedia', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'representation_id' => $vn_representation_id, 'value_id' => $vn_value_id, 'version' => 'original'))
					);
					if ($o_children->get($t_subject->tableName().'.'.$t_subject->primaryKey()) == $vn_page_id) { $vn_open_to_page = $vn_c; }
				
					$vn_c++;
				}
				ksort($va_pages);
				$va_pages = array_values($va_pages);
			
				ksort($va_sections);
				$va_sections = array_values($va_sections);
				$vn_c = 1;
				foreach($va_sections as $vn_i => $va_section) {
					$va_sections[$vn_i]['page'] = $vn_c;
					$vn_c++;
				}
			
				$vn_subject_id = $t_subject->getPrimaryKey();
			
				$vs_book_viewer_content_mode = 'hierarchy_of_representations';
				$vb_use_book_reader = true;
			} else {
				if (
					$vb_should_use_book_viewer
					&&
					(sizeof($va_reps) == 1)
					&&
					isset($va_display_options['show_hierarchy_in_book_viewer'])
					&& 
					(bool)$va_display_options['show_hierarchy_in_book_viewer']
					&&
					($va_child_ids = $t_subject->getHierarchyChildren(null, array('idsOnly' => true)))
				) {
					array_unshift($va_child_ids, $t_subject->getPrimaryKey());
					// Create book viewer from hierarchical objects
					$o_children = $t_subject->makeSearchResult('ca_objects', $va_child_ids);
					$vn_subject_id = $t_subject->getPrimaryKey();
				
					$vn_c = 1;
					while($o_children->nextHit()) {
						if (!($vn_representation_id = (int)$o_children->get('ca_object_representations.representation_id'))) { continue; }
						$vs_preview_url = $o_children->getMediaUrl('ca_object_representations.media', 'preview');
						$vs_preview_path = $o_children->getMediaPath('ca_object_representations.media', 'preview');
						$va_preview_info = $o_children->getMediaInfo('ca_object_representations.media', 'preview');
						$vs_large_url = $o_children->getMediaUrl('ca_object_representations.media', 'large');
						$vs_large_path = $o_children->getMediaPath('ca_object_representations.media', 'large');
						$va_large_info = $o_children->getMediaInfo('ca_object_representations.media', 'large');
						$vs_page_url = $o_children->getMediaUrl('ca_object_representations.media', 'page');
						$vs_page_path = $o_children->getMediaPath('ca_object_representations.media', 'page');
						$va_page_info = $o_children->getMediaInfo('ca_object_representations.media', 'page');
					
						$va_pages[] = array(
							'title' => $vs_title = $o_children->get('ca_objects.preferred_labels.name'),
							'subject_id' => $vn_subject_id, 'representation_id' => $vn_representation_id,
							'thumbnail_url' => $vs_preview_url, 'thumbnail_path' => $vs_preview_path, 'thumbnail_width' => $va_preview_info['WIDTH'], 'thumbnail_height' => $va_preview_info['HEIGHT'], 'thumbnail_mimetype' => $va_preview_info['MIMETYPE'],
							'normal_url' => $vs_large_url, 'normal_path' => $vs_large_path, 'normal_width' => $va_large_info['WIDTH'], 'normal_height' => $va_large_info['HEIGHT'], 'normal_mimetype' => $va_large_info['MIMETYPE'],
							'large_url' => $vs_page_url, 'large_path' => $vs_page_path, 'large_width' => $va_page_info['WIDTH'], 'large_height' => $va_page_info['HEIGHT'], 'large_mimetype' => $va_page_info['MIMETYPE']
						);
						$va_sections[] = array(
							'title' => $vs_title, 
							'page' => $vn_c, 
							'subject_id' => $vn_subject_id, 
							'representation_id' => $vn_representation_id, 
							'isSelected' => 0,
							'editUrl' => caEditorUrl($this->request, $t_subject->tableName(), $vn_subject_id), 
							'downloadUrl' => caNavUrl($this->request, $va_url['module'], $va_url['controller'], 'DownloadMedia', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'representation_id' => $vn_representation_id, 'value_id' => $vn_value_id, 'version' => 'original')));
				
						$vn_c++;
					}
				
					$vs_book_viewer_content_mode = 'hierarchy_of_representations';
					$vb_use_book_reader = true;
				} else {
					if (
						($vb_should_use_book_viewer)
						&&
						($this->getVar('num_multifiles') > 0)
					) {
						// Create book viewer from single representation with multifiles
						$vb_use_book_reader = true;
			
						$vn_subject_id = $t_subject->getPrimaryKey();
						foreach($t_rep->getFileList(null, 0, null, array('preview', 'large_preview', 'page_preview')) as $vn_id => $va_file) {
							$va_pages[] = array(
								'subject_id' => $vn_subject_id, 'representation_id' => $vn_representation_id,
								'thumbnail_url' => $va_file['preview_url'], 'thumbnail_path' => $va_file['preview_path'], 'thumbnail_width' => $va_file['preview_width'], 'thumbnail_height' => $va_file['preview_height'], 'thumbnail_mimetype' => $va_file['preview_mimetype'],
								'normal_url' => $va_file['large_preview_url'], 'normal_path' => $va_file['large_preview_path'], 'normal_width' => $va_file['large_preview_width'], 'normal_height' => $va_file['large_preview_height'], 'normal_mimetype' => $va_file['large_preview_mimetype'],
								'large_url' => $va_file['page_preview_url'], 'large_path' => $va_file['page_preview_path'], 'large_width' => $va_file['page_preview_width'], 'large_height' => $va_file['page_preview_height'], 'large_mimetype' => $va_file['page_preview_mimetype'],
							);
						}
					
					
						$vs_book_viewer_content_mode = 'multifiles';
					}
				}
			}
		}
	} elseif($vn_value_id > 0) {
		// displaying media attribute
		if (
			($vb_should_use_book_viewer)
			&&
			($this->getVar('num_multifiles') > 0)
		) {
			// Create book viewer from single representation with multifiles
			$vb_use_book_reader = true;

			$vn_subject_id = $t_subject->getPrimaryKey();
			foreach($t_value->getFileList(null, 0, null, array('preview', 'large_preview', 'page_preview')) as $vn_id => $va_file) {
				$va_pages[] = array(
					'subject_id' => $vn_subject_id, 'value_id' => $vn_value_id,
					'thumbnail_url' => $va_file['preview_url'], 'thumbnail_path' => $va_file['preview_path'], 'thumbnail_width' => $va_file['preview_width'], 'thumbnail_height' => $va_file['preview_height'], 'thumbnail_mimetype' => $va_file['preview_mimetype'],
					'normal_url' => $va_file['large_preview_url'], 'normal_path' => $va_file['large_preview_path'], 'normal_width' => $va_file['large_preview_width'], 'normal_height' => $va_file['large_preview_height'], 'normal_mimetype' => $va_file['large_preview_mimetype'],
					'large_url' => $va_file['page_preview_url'], 'large_path' => $va_file['page_preview_path'], 'large_width' => $va_file['page_preview_width'], 'large_height' => $va_file['page_preview_height'], 'large_mimetype' => $va_file['page_preview_mimetype'],
				);
			}
		
			$vs_book_viewer_content_mode = 'multifiles';
		}
		
	}	

	if ($vb_use_book_reader) {
		// Return Javascript for document viewer interface
		$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
		$o_view->setVar('t_subject', $t_subject);
		$o_view->setVar('t_representation', $t_rep);
		$o_view->setVar('t_attribute_value', $t_value);
		
		$o_view->setVar('item_id', $t_set_item ? $t_set_item->getPrimaryKey() : null);
		
		$o_view->setVar('pages', $va_pages);
		$o_view->setVar('sections', $va_sections);
		
		$o_view->setVar('content_mode', $vs_book_viewer_content_mode);
		$o_view->setVar('initial_page', $vn_open_to_page);
		$o_view->setVar('display_type', $vs_display_type);
		$o_view->setVar('display_options', $va_display_options);
		
		// Save pages in cache so BaseEditorController::GetPageListAsJSON() can get them for return to the document viewer
		$va_page_cache = $this->request->session->getVar('caDocumentViewerPageListCache');
		$vs_page_cache_key = ($vs_book_viewer_content_mode == 'hierarchy_of_representations') ? md5($vn_subject_id) : md5($vn_subject_id.'/'.$vn_representation_id.'/'.$vn_value_id);
		$va_page_cache[$vs_page_cache_key] = $va_pages;
	
		
		
		$this->request->session->setVar('caDocumentViewerPageListCache', $va_page_cache);
		
		$va_section_cache = $this->request->session->getVar('caDocumentViewerSectionCache');
		$va_section_cache[$vn_object_id.'/'.$vn_representation_id] = $va_sections;
		$this->request->session->setVar('caDocumentViewerSectionCache', $va_section_cache);
		
		print $o_view->render('bookviewer_html.php');
		return;
	} elseif ($vb_should_use_native_pdf_viewer) {
?>
	<div class="caMediaOverlayControls">
			<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
<?php
		if ($this->request->user->canDoAction('can_download_media') || $this->request->user->canDoAction('can_download_ca_object_representations')) {
?>
				<div class='download'>
<?php 
					if (is_array($va_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
						// -- provide user with a choice of versions to download
						print caFormTag($this->request, 'DownloadMedia', 'caMediaDownloadForm', $va_url['module'].'/'.$va_url['controller'], 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						print caHTMLSelect('version', $va_versions, array('style' => 'font-size: 9px;'));
						print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/downloadWhite.png' border='0' title='"._t("Download media")."' valign='bottom'/>", '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						print caHTMLHiddenInput($t_subject->primaryKey(), array('value' => $t_subject->getPrimaryKey()));
						if ($vn_representation_id) { print caHTMLHiddenInput("representation_id", array('value' => $vn_representation_id)); }
						if ($vn_value_id) { print caHTMLHiddenInput("value_id", array('value' => $vn_value_id)); }
						print caHTMLHiddenInput("download", array('value' => 1));
						print "</form>\n";
					}
?>				
				</div>
<?php
	}
?>
			<div class='objectInfo'>
<?php
				$vs_label = $t_subject->getLabelForDisplay();
				print caTruncateStringWithEllipsis($vs_label, 80);
			
				if($t_subject->get("idno")){
					print " [".$t_subject->get("idno")."]";
				}
?>			
			</div>
			<div class='repNav'>
<?php
				if ($vn_id = $this->getVar('previous_representation_id')) {
					print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, '*', '*', $this->request->getAction(), array('representation_id' => (int)$vn_id, $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey()))."\");'>←</a>";
				}
				if (sizeof($va_reps) > 1) {
					print ' '._t("%1 of %2", $this->getVar('representation_index'), sizeof($va_reps)).' ';
				}
				if ($vn_id = $this->getVar('next_representation_id')) {
					print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, '*', '*', $this->request->getAction(), array('representation_id' => (int)$vn_id, $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey()))."\");'>→</a>";
				}
?>
			</div>
	</div><!-- end caMediaOverlayControls -->
	
	<div id='caMediaOverlayContent'> </div>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			var pdfObj = new PDFObject({ url: '<?php print $t_rep->getMediaUrl('media', 'original'); ?>' }).embed('caMediaOverlayContent');
		});
	</script>
<?php
		} else {
		
			if (!$vb_no_controls) {
?>
	<!-- Controls - only for media overlay -->
	<div class="caMediaOverlayControls">
			<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
<?php
	if ($this->request->user->canDoAction('can_download_media') || $this->request->user->canDoAction('can_download_ca_object_representations')) {
?>
				<div class='download'>
<?php 
					if (is_array($va_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
						// -- provide user with a choice of versions to download
						print caFormTag($this->request, 'DownloadMedia', 'caMediaDownloadForm', $va_url['module'].'/'.$va_url['controller'], 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						print caHTMLSelect('version', $va_versions, array('style' => 'font-size: 9px;'));
						print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/downloadWhite.png' border='0' title='"._t("Download media")."' valign='bottom'/>", '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						print caHTMLHiddenInput($t_subject->primaryKey(), array('value' => $t_subject->getPrimaryKey()));
						if ($vn_representation_id) { print caHTMLHiddenInput("representation_id", array('value' => $vn_representation_id)); }
						if ($vn_value_id) { print caHTMLHiddenInput("value_id", array('value' => $vn_value_id)); }
						print caHTMLHiddenInput("download", array('value' => 1));
						print "</form>\n";
					}
?>				
				</div>
<?php
	}
?>
				<div class='objectInfo'>
<?php
					$vs_label = $t_subject->getLabelForDisplay();
					print caTruncateStringWithEllipsis($vs_label, 80);
				
					if($t_subject->get("idno")){
						print " [".$t_subject->get("idno")."]";
					}
?>			
				</div>
				<div class='repNav'>
<?php
					if ($vn_id = $this->getVar('previous_representation_id')) {
						print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, '*', '*', $this->request->getAction(), array('representation_id' => (int)$vn_id, $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey()))."\");'>←</a>";
					}
					if (sizeof($va_reps) > 1) {
						print ' '._t("%1 of %2", $this->getVar('representation_index'), sizeof($va_reps)).' ';
					}
					if ($vn_id = $this->getVar('next_representation_id')) {
						print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, '*', '*', $this->request->getAction(), array('representation_id' => (int)$vn_id, $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey()))."\");'>→</a>";
					}
?>
				</div>
<?php
		if ($vb_use_media_editor) {
			$va_display_info = caGetMediaDisplayInfo($vs_display_type, $t_rep->getMediaInfo('media', 'INPUT', 'MIMETYPE'));
			
			if (isset($va_display_info['editing_tools']) && (is_array($va_tool_views = $va_display_info['editing_tools']))) {
				foreach($va_tool_views as $vs_tool_view) {
					$this->addViewPath($this->request->getThemeDirectoryPath()."/bundles");
					print $this->render("representation_media_editor/{$vs_tool_view}_html.php");
				}
			}
?>

<?php
			}
		}
?>
	</div><!-- end caMediaOverlayControls -->
<?php
	}
?>
	<div id="<?php print ($vs_display_type == 'media_overlay') ? 'caMediaOverlayContent' : 'caMediaDisplayContent'; ?>">
		<div class="caMediaOverlayProgress" id="caMediaOverlayProgress">
			<div class="caMediaOverlayProgressContent">
			</div>
		</div>
<?php
	// return standard tag
	if (!is_array($va_display_options)) { $va_display_options = array(); }
	
	if ($vn_representation_id) {
		$va_pieces = caEditorUrl($this->request, $t_subject->tableName(), null, true);

		$vs_tag = $t_rep->getMediaTag('media', $vs_show_version, array_merge($va_display_options, array(
			'id' => $vs_viewer_id = (($vs_display_type == 'media_overlay') ? 'caMediaOverlayContentMedia' : 'caMediaDisplayContentMedia'), 
			'viewer_base_url' => $this->request->getBaseUrlPath(),
			// don't think we need to pass the subject key? also, we can't use */* for controller and module, since Search controllers don't have GetAnnotations/SaveAnnotations
			//'annotation_load_url' => caNavUrl($this->request, '*', '*', 'GetAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
			'annotation_load_url' => caNavUrl($this->request, $va_pieces['module'], $va_pieces['controller'], 'GetAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey())),
			//'annotation_save_url' => caNavUrl($this->request, '*', '*', 'SaveAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey())),
			'annotation_save_url' => caNavUrl($this->request, $va_pieces['module'], $va_pieces['controller'], 'SaveAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey())),
			//'download_url' => caNavUrl($this->request, '*', '*', 'DownloadMedia', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
			'download_url' => caNavUrl($this->request, $va_pieces['module'], $va_pieces['controller'], 'DownloadMedia', array('representation_id' => (int)$t_rep->getPrimaryKey(), $t_subject->primaryKey() => (int)$t_subject->getPrimaryKey(), 'version' => 'original')),
			//'help_load_url' => caNavUrl($this->request, '*', '*', 'ViewerHelp', array()),
			'help_load_url' => caNavUrl($this->request, $va_pieces['module'], $va_pieces['controller'], 'ViewerHelp', array()),
			'annotationEditorPanel' => 'caRepresentationAnnotationEditor',
			'annotationEditorUrl' => caNavUrl($this->request, 'editor/representation_annotations', 'RepresentationAnnotationQuickAdd', 'Form', array('representation_id' => (int)$t_rep->getPrimaryKey())),
			'captions' => $t_rep->getCaptionFileList(), 'progress_id' => 'caMediaOverlayProgress'
		)));
	} else {
		$vs_tag = $t_value->getMediaTag('value_blob', $vs_show_version, array_merge($va_display_options, array(
			'id' => $vs_viewer_id = (($vs_display_type == 'media_overlay') ? 'caMediaOverlayContentMedia' : 'caMediaDisplayContentMedia'), 
			'viewer_base_url' => $this->request->getBaseUrlPath()
		)));
	}
	
	# --- should the media be clickable to open the overlay?
	if($va_display_options['no_overlay'] || ($vs_display_type == 'media_overlay') || ($vs_display_type == 'media_editor')){
		print $vs_tag;
	}else{
		print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'Detail', 'Object', 'GetMediaOverlay', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'representation_id' => $t_rep->getPrimaryKey()))."\"); return false;' >".$vs_tag."</a>";
	}
?>
<script type="text/javascript">
	var caRepresentationAnnotationEditor;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caRepresentationAnnotationEditor = caUI.initPanel({ 
				panelID: "caRepresentationAnnotationEditor",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRepresentationAnnotationEditorContentArea",		/* DOM ID of the content area <div> in the panel */
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				useExpose: false,
				onCloseCallback: function() {
					jQuery("#<?php print $vs_viewer_id; ?>").tileviewer("refreshAnnnotations");
				}
			});
		}
	});
	
	function caAnnoEditorDisableAnnotationForm() {
		caRepresentationAnnotationEditor.hidePanel();
	}
	function caAnnoEditorTlReload() {
		console.log("caught reload");
	}
	function caAnnoEditorTlLoad() {
		console.log("caught load");
	}
	function caAnnoEditorEdit(annotation_id) {
		caRepresentationAnnotationEditor.hidePanel();
		return false;
	}
	function caAnnoEditorGetPlayerTime() {
		return 0;
	}
	function caAnnoEditorTlRemove() {
		console.log("caught remove");
	}
</script>
	</div>
	<div id="caRepresentationAnnotationEditor" class="caRelationQuickAddPanel"> 
		<div id="caRepresentationAnnotationEditorContentArea">
		<div class='quickAddDialogHeader'><?php print _t('Edit annotation'); ?></div>
		
		</div>
	</div>