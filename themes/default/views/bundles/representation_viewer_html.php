<?php
/* ----------------------------------------------------------------------
 * views/bundles/representation_viewer_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
	$t_object 					= $this->getVar('t_object');
	$t_rep 						= $this->getVar('t_object_representation');
	$t_set_item 				= $this->getVar('t_set_item');				// ca_set_items instance (if being used with selectable representations in a set item)
	$t_order_item 				= $this->getVar('t_order_item');			// ca_commerce_order_items instance (if being used with selectable representations in an e-commerce order item)
	$va_versions 				= $this->getVar('versions');	
	$vn_representation_id 		= $t_rep->getPrimaryKey();
	$va_reps 					= $this->getVar('reps');
	
	$vs_display_type		 	= $this->getVar('display_type');
	$va_display_options		 	= $this->getVar('display_options');
	$vs_show_version 			= $this->getVar('version');
	
	// Get filename of originally uploaded file
	$va_media_info 				= $t_rep->getMediaInfo('media');
	$vs_original_filename 		= $va_media_info['ORIGINAL_FILENAME'];
	
	$vs_container_id 			= $this->getVar('containerID');
	
	$va_pages = $va_sections = array();
	$vb_use_book_reader = false;
	$vn_open_to_page = 1;


		$vb_should_use_book_viewer = isset($va_display_options['use_book_viewer']) && (bool)$va_display_options['use_book_viewer'];

		$vs_book_viewer_content_mode = null;
		$vn_object_id = $vn_rep_id = null;
		if (
			$vb_should_use_book_viewer 
			&&
			isset($va_display_options['use_book_viewer_when_number_of_representations_exceeds']) 
			&& 
			((int)$va_display_options['use_book_viewer_when_number_of_representations_exceeds'] < sizeof($va_reps))
		) {
			// Create book viewer from multiple representations
			$va_reps = $t_object->getRepresentations(array('thumbnail', 'large', 'page'));
			$vn_object_id = $t_object->getPrimaryKey();
			
			$vn_c = 1;
			
			$vb_set_is_loaded = false;
			if ($t_set_item && ($vb_set_is_loaded = $t_set_item->getPrimaryKey())) {
				$va_selected_reps = $t_set_item->getSelectedRepresentationIDs();
			} else {
				if ($t_order_item && ($vb_set_is_loaded = $t_order_item->getPrimaryKey())) {
					$va_selected_reps = $t_order_item->getRepresentationIDs();
				}
			}
 			foreach($va_reps as $vn_id => $va_file) {
				$va_pages[] = array(
					'object_id' => $vn_object_id, 'representation_id' => $vn_id,
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
					'object_id' => $vn_object_id, 
					'representation_id' => $vn_id,
					'isSelected' => $vn_selected, 
					'editUrl' => caEditorUrl($this->request, 'ca_object_representations', $vn_id), 
					'downloadUrl' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('object_id' => $vn_object_id, 'representation_id' => $vn_id, 'version' => 'original'))
				);
				$vn_c++;
			}

			$vn_object_id = $t_object->getPrimaryKey();
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
				($va_ancestor_ids = $t_object->getHierarchyAncestors(null, array('idsOnly' => true)))
			) {
				$vn_parent_id = array_pop($va_ancestor_ids);
				
				$vn_page_id = $t_object->getPrimaryKey();
				$t_object->load($vn_parent_id);
				$va_child_ids = $t_object->getHierarchyChildren(null, array('idsOnly' => true));
				
				foreach($va_ancestor_ids as $vn_id) {
					array_unshift($va_child_ids, $vn_id);
				}
				$o_children = $t_object->makeSearchResult('ca_objects', $va_child_ids);
				
				$vn_c = 1;
				while($o_children->nextHit()) {
					$vs_thumbnail_url = $o_children->getMediaUrl('ca_object_representations.media', 'thumbnail');
					$vs_thumbnail_path = $o_children->getMediaPath('ca_object_representations.media', 'thumbnail');
					$va_thumbnail_info = $o_children->getMediaInfo('ca_object_representations.media', 'thumbnail');
					$vs_large_url = $o_children->getMediaUrl('ca_object_representations.media', 'large');
					$vs_large_path = $o_children->getMediaPath('ca_object_representations.media', 'large');
					$va_large_info = $o_children->getMediaInfo('ca_object_representations.media', 'large');
					$vs_page_url = $o_children->getMediaUrl('ca_object_representations.media', 'page');
					$vs_page_path = $o_children->getMediaPath('ca_object_representations.media', 'page');
					$va_page_info = $o_children->getMediaInfo('ca_object_representations.media', 'page');
					
					$vn_object_id = (int)$o_children->get('ca_objects.object_id');
					$va_pages[$vn_object_id] = array(
						'title' => $vs_title = $o_children->get('ca_objects.preferred_labels.name'),
						'object_id' => $vn_object_id, 'representation_id' => $vn_representation_id = (int)$o_children->get('ca_object_representations.representation_id'),
						'thumbnail_url' => $vs_thumbnail_url, 'thumbnail_path' => $vs_thumbnail_path, 'thumbnail_width' => $va_thumbnail_info['WIDTH'], 'thumbnail_height' => $va_thumbnail_info['HEIGHT'], 'thumbnail_mimetype' => $va_thumbnail_info['MIMETYPE'],
						'normal_url' => $vs_large_url, 'normal_path' => $vs_large_path, 'normal_width' => $va_large_info['WIDTH'], 'normal_height' => $va_large_info['HEIGHT'], 'normal_mimetype' => $va_large_info['MIMETYPE'],
						'large_url' => $vs_page_url, 'large_path' => $vs_page_path, 'large_width' => $va_page_info['WIDTH'], 'large_height' => $va_page_info['HEIGHT'], 'large_mimetype' => $va_page_info['MIMETYPE']
					);
					$va_sections[$vn_object_id] = array(
						'title' => $vs_title, 
						'page' => $vn_c, 
						'object_id' => $vn_object_id, 
						'representation_id' => $vn_representation_id, 
						'isSelected' => 0,
						'editUrl' => caEditorUrl($this->request, 'ca_objects', $vn_object_id), 
						'downloadUrl' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('object_id' => $vn_object_id, 'representation_id' => $vn_representation_id, 'version' => 'original'))
					);
					if ($o_children->get('ca_objects.object_id') == $vn_page_id) { $vn_open_to_page = $vn_c; }
					
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
				
				$vn_object_id = $t_object->getPrimaryKey();
				
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
					($va_child_ids = $t_object->getHierarchyChildren(null, array('idsOnly' => true)))
				) {
					array_unshift($va_child_ids, $t_object->getPrimaryKey());
					// Create book viewer from hierarchical objects
					$o_children = $t_object->makeSearchResult('ca_objects', $va_child_ids);
					$vn_object_id = $t_object->getPrimaryKey();
					
					$vn_c = 1;
					while($o_children->nextHit()) {
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
							'object_id' => $vn_object_id, 'representation_id' => $vn_representation_id = (int)$o_children->get('ca_object_representations.representation_id'),
							'thumbnail_url' => $vs_preview_url, 'thumbnail_path' => $vs_preview_path, 'thumbnail_width' => $va_preview_info['WIDTH'], 'thumbnail_height' => $va_preview_info['HEIGHT'], 'thumbnail_mimetype' => $va_preview_info['MIMETYPE'],
							'normal_url' => $vs_large_url, 'normal_path' => $vs_large_path, 'normal_width' => $va_large_info['WIDTH'], 'normal_height' => $va_large_info['HEIGHT'], 'normal_mimetype' => $va_large_info['MIMETYPE'],
							'large_url' => $vs_page_url, 'large_path' => $vs_page_path, 'large_width' => $va_page_info['WIDTH'], 'large_height' => $va_page_info['HEIGHT'], 'large_mimetype' => $va_page_info['MIMETYPE']
						);
						$va_sections[] = array(
							'title' => $vs_title, 
							'page' => $vn_c, 
							'object_id' => $vn_object_id, 
							'representation_id' => $vn_representation_id, 
							'isSelected' => 0,
							'editUrl' => caEditorUrl($this->request, 'ca_objects', $vn_object_id), 
							'downloadUrl' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'DownloadRepresentation', array('object_id' => $vn_object_id, 'representation_id' => $vn_representation_id, 'version' => 'original')));
					
						$vn_c++;
					}
					
					$vs_book_viewer_content_mode = 'hierarchy_of_representations';
					$vb_use_book_reader = true;
				} else {
					if (
						$vb_should_use_book_viewer
						&&
						($this->getVar('num_multifiles') > 0)
					) {
						// Create book viewer from single representation with multifiles
						$vb_use_book_reader = true;
				
						foreach($t_rep->getFileList(null, 0, null, array('preview', 'large_preview', 'page_preview')) as $vn_id => $va_file) {
							$va_pages[] = array(
								'object_id' => $vn_object_id, 'representation_id' => $t_rep->getPrimaryKey(),
								'thumbnail_url' => $va_file['preview_url'], 'thumbnail_path' => $va_file['preview_path'], 'thumbnail_width' => $va_file['preview_width'], 'thumbnail_height' => $va_file['preview_height'], 'thumbnail_mimetype' => $va_file['preview_mimetype'],
								'normal_url' => $va_file['large_preview_url'], 'normal_path' => $va_file['large_preview_path'], 'normal_width' => $va_file['large_preview_width'], 'normal_height' => $va_file['large_preview_height'], 'normal_mimetype' => $va_file['large_preview_mimetype'],
								'large_url' => $va_file['page_preview_url'], 'large_path' => $va_file['page_preview_path'], 'large_width' => $va_file['page_preview_width'], 'large_height' => $va_file['page_preview_height'], 'large_mimetype' => $va_file['page_preview_mimetype'],
							);
						}
						
						
						$vn_object_id = $t_object->getPrimaryKey();
						$vn_representation_id = $t_rep->getPrimaryKey();
						$vs_book_viewer_content_mode = 'multifiles';
					}
				}
			}
		}
				
	
		
		if ($vb_use_book_reader) {
			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
			$o_view->setVar('pages', $va_pages);
			$o_view->setVar('sections', $va_sections);
			$o_view->setVar('object_id', $vn_object_id);
			$o_view->setVar('representation_id', $vn_representation_id);
			$o_view->setVar('item_id', $t_set_item->getPrimaryKey());
			$o_view->setVar('order_item_id', $t_order_item->getPrimaryKey());
			$o_view->setVar('content_mode', $vs_book_viewer_content_mode);
			$o_view->setVar('initial_page', $vn_open_to_page);
			$o_view->setVar('display_type', $vs_display_type);
			$o_view->setVar('display_options', $va_display_options);
			
			$va_page_cache = $this->request->session->getVar('caDocumentViewerPageListCache');
			$va_page_cache[$vn_object_id.'/'.$vn_representation_id] = $va_pages;
			$this->request->session->setVar('caDocumentViewerPageListCache', $va_page_cache);
			
			$va_section_cache = $this->request->session->getVar('caDocumentViewerSectionCache');
			$va_section_cache[$vn_object_id.'/'.$vn_representation_id] = $va_sections;
			$this->request->session->setVar('caDocumentViewerSectionCache', $va_section_cache);
			
			print $o_view->render('bookviewer_html.php');
		} else {
?>
	<!-- Controls -->
	<div class="caMediaOverlayControls">
			<table width="95%">
				<tr valign="middle">
					<td align="left">
<?php
							$va_rep_info = $this->getVar('version_info');

							if (($this->getVar('version_type')) && ($va_rep_info['WIDTH'] > 0) && ($va_rep_info['HEIGHT'] > 0)) {
								print $this->getVar('version_type')."; ". $va_rep_info['WIDTH']." x ". $va_rep_info['HEIGHT']."px";
							}
?>
					</td>
<?php
					if($this->request->user->canDoAction("can_edit_ca_objects")){
?>
						<td align="left" valign="middle">
							<div><div style="float:left"><a href="<?php print caEditorUrl($this->request, 'ca_object_representations', $vn_representation_id)?>" ><?php print caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__)?></a></div><div style="float:left; margin:2px 0px 0px 3px;"><?php print _t("Edit representation"); ?></div></div>
						</td>
<?php
					}
?>
					<td align="middle" valign="middle">
						<div>
<?php
	if ($vn_id = $this->getVar('previous_representation_id')) {
		print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>←</a>";
	}
	if (sizeof($va_reps) > 1) {
		print ' '._t("%1 of %2", $this->getVar('representation_index'), sizeof($va_reps)).' ';
	}
	if ($vn_id = $this->getVar('next_representation_id')) {
		print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetRepresentationInfo', array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>→</a>";
	}
?>
						</div>
					</td>
<?php
					if($this->request->user->canDoAction("can_download_ca_object_representations")){
?>
					<td align="right" text-align="right">
<?php 
						print caFormTag($this->request, 'DownloadRepresentation', 'downloadRepresentationForm', 'editor/objects/ObjectEditor', 'get', 'multipart/form-data', null, array('disableUnsavedChangesWarning' => true));
						print caHTMLSelect('version', $va_versions, array('id' => 'caMediaOverlayDownloadVersionControl', 'class' => 'caMediaOverlayControls'), array('value' => 'original'));
						print ' '.caFormSubmitLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_DOWNLOAD__, null, array('align' => 'middle')), '', 'downloadRepresentationForm');
						print caHTMLHiddenInput('representation_id', array('value' => $t_rep->getPrimaryKey()));
						print caHTMLHiddenInput('object_id', array('value' => $t_object->getPrimaryKey()));
						print caHTMLHiddenInput('download', array('value' => 1));
?>
						</form>
					</td>
<?php
					}
?>
				</tr>
			</table>
	</div><!-- end caMediaOverlayControls -->

	<div id="caMediaOverlayContent">
<?php
	// return standard tag
	if (!is_array($va_display_options)) { $va_display_options = array(); }
	print $t_rep->getMediaTag('media', $vs_show_version, array_merge($va_display_options, array(
		'id' => 'caMediaOverlayContentMedia', 
		'viewer_base_url' => $this->request->getBaseUrlPath()
	)));
?>
	</div><!-- end caMediaOverlayContent -->
<?php
	}
?>