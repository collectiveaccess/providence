<?php
/* ----------------------------------------------------------------------
 * views/bundles/representation_viewer_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2013 Whirl-i-Gig
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
	$vb_use_media_editor		= $this->getVar('use_media_editor');
	$vb_no_controls				= $this->getVar('noControls');
	
	$va_pages = $va_sections = array();
	$vb_use_book_reader = false;
	$vb_use_pdfjs_viewer = false;
	$vb_should_use_native_pdf_viewer = false;
	
	$vn_open_to_page = 1;

	if ($t_rep->get('mimetype') ==  'application/pdf') {
		switch($vs_pdf_pref = $this->request->user->getPreference('pdf_viewer')) {
			case 'pdfjs':
				$vb_use_pdfjs_viewer = true;
				break;
			case 'native_plus_pdfjs':
				if ($this->request->session->getVar('has_pdf_plugin')) { 
					$vb_should_use_native_pdf_viewer = true;
				} else {
					$vb_use_pdfjs_viewer = true;	
				}
				break;
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
				$vb_should_use_pdfjs_viewer = isset($va_display_options['use_pdfjs_viewer']) && (bool)$va_display_options['use_pdfjs_viewer'];
				break;
		}
	} else {
		$vb_should_use_book_viewer = isset($va_display_options['use_book_viewer']) && (bool)$va_display_options['use_book_viewer'];
		$vb_should_use_pdfjs_viewer = isset($va_display_options['use_pdfjs_viewer']) && (bool)$va_display_options['use_pdfjs_viewer'];
		$vb_should_use_native_pdf_viewer = false;
	}
	
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
						($vb_should_use_book_viewer || $vb_should_use_pdfjs_viewer)
						&&
						($this->getVar('num_multifiles') > 0)
					) {
						// Create book viewer from single representation with multifiles
						
						if ($vb_should_use_pdfjs_viewer && ($t_rep->get('mimetype') ==  'application/pdf')) {
							$vb_use_pdfjs_viewer = true;
						} 
						$vb_use_book_reader = true;
				
						$vn_object_id = $t_object->getPrimaryKey();
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
				
	
		if ($vb_use_pdfjs_viewer && $this->request->config->get('use_pdfjs_viewer')) {
			// PDFjs
?>
<div id="outerContainer">

      <div id="sidebarContainer">
        <div id="toolbarSidebar">
          <div class="splitToolbarButton toggled">
            <button id="viewThumbnail" class="toolbarButton group toggled" title="Show Thumbnails" tabindex="2" data-l10n-id="thumbs">
               <span data-l10n-id="thumbs_label">Thumbnails</span>
            </button>
            <button id="viewOutline" class="toolbarButton group" title="Show Document Outline" tabindex="3" data-l10n-id="outline">
               <span data-l10n-id="outline_label">Document Outline</span>
            </button>
          </div>
        </div>
        <div id="sidebarContent">
          <div id="thumbnailView">
          </div>
          <div id="outlineView" class="hidden">
          </div>
        </div>
      </div>  <!-- sidebarContainer -->

      <div id="mainContainer">
        <div class="findbar hidden hiddenSmallView" id="findbar">
          <label for="findInput" class="toolbarLabel" data-l10n-id="find_label">Find:</label>
          <input id="findInput" class="toolbarField" tabindex="21">
          <div class="splitToolbarButton">
            <button class="toolbarButton findPrevious" title="" id="findPrevious" tabindex="22" data-l10n-id="find_previous">
              <span data-l10n-id="find_previous_label">Previous</span>
            </button>
            <div class="splitToolbarButtonSeparator"></div>
            <button class="toolbarButton findNext" title="" id="findNext" tabindex="23" data-l10n-id="find_next">
              <span data-l10n-id="find_next_label">Next</span>
            </button>
          </div>
          <input type="checkbox" id="findHighlightAll" class="toolbarField">
          <label for="findHighlightAll" class="toolbarLabel" tabindex="24" data-l10n-id="find_highlight">Highlight all</label>
          <input type="checkbox" id="findMatchCase" class="toolbarField">
          <label for="findMatchCase" class="toolbarLabel" tabindex="25" data-l10n-id="find_match_case_label">Match case</label>
          <span id="findMsg" class="toolbarLabel"></span>
        </div>
        <div class="toolbar" id="toolbarTopLevel">
          <div id="toolbarContainer">
            <div id="toolbarViewer">
              <div id="toolbarViewerLeft">
                <button id="sidebarToggle" class="toolbarButton" title="Toggle Sidebar" tabindex="4" data-l10n-id="toggle_sidebar">
                  <span data-l10n-id="toggle_sidebar_label">Toggle Sidebar</span>
                </button>
                <div class="toolbarButtonSpacer"></div>
                <button id="viewFind" class="toolbarButton group hiddenSmallView" title="Find in Document" tabindex="5" data-l10n-id="findbar">
                   <span data-l10n-id="findbar_label">Find</span>
                </button>
                <div class="splitToolbarButton">
                  <button class="toolbarButton pageUp" title="Previous Page" id="previous" tabindex="6" data-l10n-id="previous">
                    <span data-l10n-id="previous_label">Previous</span>
                  </button>
                  <div class="splitToolbarButtonSeparator"></div>
                  <button class="toolbarButton pageDown" title="Next Page" id="next" tabindex="7" data-l10n-id="next">
                    <span data-l10n-id="next_label">Next</span>
                  </button>
                </div>
                <label id="pageNumberLabel" class="toolbarLabel" for="pageNumber" data-l10n-id="page_label">Page: </label>
                <input type="number" id="pageNumber" class="toolbarField pageNumber" value="1" size="4" min="1" tabindex="8">
                </input>
                <span id="numPages" class="toolbarLabel"></span>
              </div>
              <div id="toolbarViewerRight">
                <input id="fileInput" class="fileInput" type="file" oncontextmenu="return false;" style="visibility: hidden; position: fixed; right: 0; top: 0" />

                <button id="fullscreen" class="toolbarButton fullscreen hiddenSmallView" title="Switch to Presentation Mode" tabindex="12" data-l10n-id="presentation_mode">
                  <span data-l10n-id="presentation_mode_label">Presentation Mode</span>
                </button>

                <button id="openFile" class="toolbarButton openFile hiddenSmallView" title="Open File" tabindex="13" data-l10n-id="open_file">
                   <span data-l10n-id="open_file_label">Open</span>
                </button>

                <button id="print" class="toolbarButton print" title="Print" tabindex="14" data-l10n-id="print">
                  <span data-l10n-id="print_label">Print</span>
                </button>

                <button id="download" class="toolbarButton download" title="Download" tabindex="15" data-l10n-id="download">
                  <span data-l10n-id="download_label">Download</span>
                </button>
                <!-- <div class="toolbarButtonSpacer"></div> -->
                <a href="#" id="viewBookmark" class="toolbarButton bookmark hiddenSmallView" title="Current view (copy or open in new window)" tabindex="16" data-l10n-id="bookmark"><span data-l10n-id="bookmark_label">Current View</span></a>
                
                 <button id="pdfclose" class="toolbarButton close" title="Close" tabindex="16" data-l10n-id="close" onclick="caMediaPanel.hidePanel(); return false;">
                  <span data-l10n-id="download_label">Close</span>
                </button>
              </div>
              <div class="outerCenter">
                <div class="innerCenter" id="toolbarViewerMiddle">
                  <div class="splitToolbarButton">
                    <button class="toolbarButton zoomOut" id="zoom_out" title="Zoom Out" tabindex="9" data-l10n-id="zoom_out">
                      <span data-l10n-id="zoom_out_label">Zoom Out</span>
                    </button>
                    <div class="splitToolbarButtonSeparator"></div>
                    <button class="toolbarButton zoomIn" id="zoom_in" title="Zoom In" tabindex="10" data-l10n-id="zoom_in">
                      <span data-l10n-id="zoom_in_label">Zoom In</span>
                     </button>
                  </div>
                  <span id="scaleSelectContainer" class="dropdownToolbarButton">
                     <select id="scaleSelect" title="Zoom" oncontextmenu="return false;" tabindex="11" data-l10n-id="zoom">
                      <option id="pageAutoOption" value="auto" selected="selected" data-l10n-id="page_scale_auto">Automatic Zoom</option>
                      <option id="pageActualOption" value="page-actual" data-l10n-id="page_scale_actual">Actual Size</option>
                      <option id="pageFitOption" value="page-fit" data-l10n-id="page_scale_fit">Fit Page</option>
                      <option id="pageWidthOption" value="page-width" data-l10n-id="page_scale_width">Full Width</option>
                      <option id="customScaleOption" value="custom"></option>
                      <option value="0.5">50%</option>
                      <option value="0.75">75%</option>
                      <option value="1">100%</option>
                      <option value="1.25">125%</option>
                      <option value="1.5">150%</option>
                      <option value="2">200%</option>
                    </select>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <menu type="context" id="viewerContextMenu">
          <menuitem label="First Page" id="first_page"
                    data-l10n-id="first_page" ></menuitem>
          <menuitem label="Last Page" id="last_page"
                    data-l10n-id="last_page" ></menuitem>
          <menuitem label="Rotate Counter-Clockwise" id="page_rotate_ccw"
                    data-l10n-id="page_rotate_ccw" ></menuitem>
          <menuitem label="Rotate Clockwise" id="page_rotate_cw"
                    data-l10n-id="page_rotate_cw" ></menuitem>
        </menu>

        <div id="viewerContainer" tabindex="1">
          <div id="viewer" contextmenu="viewerContextMenu"></div>
        </div>

        <div id="loadingBox">
          <div id="loading"></div>
          <div id="loadingBar"><div class="progress"></div></div>
        </div>

        <div id="errorWrapper" hidden='true'>
          <div id="errorMessageLeft">
            <span id="errorMessage"></span>
            <button id="errorShowMore" onclick="" oncontextmenu="return false;" data-l10n-id="error_more_info">
              More Information
            </button>
            <button id="errorShowLess" onclick="" oncontextmenu="return false;" data-l10n-id="error_less_info" hidden='true'>
              Less Information
            </button>
          </div>
          <div id="errorMessageRight">
            <button id="errorClose" oncontextmenu="return false;" data-l10n-id="error_close">
              Close
            </button>
          </div>
          <div class="clearBoth"></div>
          <textarea id="errorMoreInfo" hidden='true' readonly="readonly"></textarea>
        </div>
      </div> <!-- mainContainer -->

    </div> <!-- outerContainer -->
    <div id="printContainer"></div>
    
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery.getScript('<?php print $this->request->getBaseUrlPath(); ?>/js/pdfjs/viewer.js', function() {
				PDFJS.workerSrc = '<?php print $this->request->getBaseUrlPath(); ?>/js/pdfjs/pdf.js';
				PDFJS.webViewerLoad('<?php print $t_rep->getMediaUrl('media', 'original'); ?>'); 
			});
		});
	</script>
<?php	
		} elseif ($vb_use_book_reader) {
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
			return;
		} elseif ($vb_should_use_native_pdf_viewer) {
?>
	<div class="caMediaOverlayControls">
			<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
<?php
	if ($this->request->user->canDoAction('can_download_media')) {
?>
				<div class='download'>
<?php 
					if (is_array($va_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
						// -- provide user with a choice of versions to download
						print caFormTag($this->request, 'DownloadMedia', 'caMediaDownloadForm', 'editor/objects/ObjectEditor', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						print caHTMLSelect('version', $va_versions, array('style' => 'font-size: 9px;'));
						print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/downloadWhite.png' border='0' title='"._t("Download media")."' valign='bottom'/>", '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						print caHTMLHiddenInput("object_id", array('value' => $t_object->getPrimaryKey()));
						print caHTMLHiddenInput("representation_id", array('value' => $t_rep->getPrimaryKey()));
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
				$vs_label = $t_object->getLabelForDisplay();
				print (mb_strlen($vs_label) > 80) ? mb_substr($vs_label, 0, 80)."..." : $vs_label;
			
				if($t_object->get("idno")){
					print " [".$t_object->get("idno")."]";
				}
?>			
			</div>
			<div class='repNav'>
<?php
				if ($vn_id = $this->getVar('previous_representation_id')) {
					print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', $this->request->getAction(), array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>←</a>";
				}
				if (sizeof($va_reps) > 1) {
					print ' '._t("%1 of %2", $this->getVar('representation_index'), sizeof($va_reps)).' ';
				}
				if ($vn_id = $this->getVar('next_representation_id')) {
					print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', $this->request->getAction(), array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>→</a>";
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
	if ($this->request->user->canDoAction('can_download_media')) {
?>
				<div class='download'>
<?php 
					if (is_array($va_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
						// -- provide user with a choice of versions to download
						print caFormTag($this->request, 'DownloadMedia', 'caMediaDownloadForm', 'editor/objects/ObjectEditor', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						print caHTMLSelect('version', $va_versions, array('style' => 'font-size: 9px;'));
						print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/downloadWhite.png' border='0' title='"._t("Download media")."' valign='bottom'/>", '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						print caHTMLHiddenInput("object_id", array('value' => $t_object->getPrimaryKey()));
						print caHTMLHiddenInput("representation_id", array('value' => $t_rep->getPrimaryKey()));
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
					$vs_label = $t_object->getLabelForDisplay();
					print (mb_strlen($vs_label) > 80) ? mb_substr($vs_label, 0, 80)."..." : $vs_label;
				
					if($t_object->get("idno")){
						print " [".$t_object->get("idno")."]";
					}
?>			
				</div>
				<div class='repNav'>
<?php
					if ($vn_id = $this->getVar('previous_representation_id')) {
						print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', $this->request->getAction(), array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>←</a>";
					}
					if (sizeof($va_reps) > 1) {
						print ' '._t("%1 of %2", $this->getVar('representation_index'), sizeof($va_reps)).' ';
					}
					if ($vn_id = $this->getVar('next_representation_id')) {
						print "<a href='#' onClick='jQuery(\"#{$vs_container_id}\").load(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', $this->request->getAction(), array('representation_id' => (int)$vn_id, 'object_id' => (int)$t_object->getPrimaryKey()))."\");'>→</a>";
					}
?>
				</div>
<?php
		if ($vb_use_media_editor) {
			$va_display_info = caGetMediaDisplayInfo($vs_display_type, $t_rep->getMediaInfo('media', 'INPUT', 'MIMETYPE'));
			
			if (isset($va_display_info['editing_tools']) && (is_array($va_tool_views = $va_display_info['editing_tools']))) {
				foreach($va_tool_views as $vs_tool_view) {
					$this->addViewPath($this->request->getThemeDirectoryPath()."/views/editor/objects");
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
<?php
	// return standard tag
	if (!is_array($va_display_options)) { $va_display_options = array(); }
	$vs_tag = $t_rep->getMediaTag('media', $vs_show_version, array_merge($va_display_options, array(
		'id' => ($vs_display_type == 'media_overlay') ? 'caMediaOverlayContentMedia' : 'caMediaDisplayContentMedia', 
		'viewer_base_url' => $this->request->getBaseUrlPath(),
		'annotation_load_url' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), 'object_id' => (int)$t_object->getPrimaryKey())),
		'annotation_save_url' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'SaveAnnotations', array('representation_id' => (int)$t_rep->getPrimaryKey(), 'object_id' => (int)$t_object->getPrimaryKey())),
		'help_load_url' => caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'ViewerHelp', array()),
		'captions' => $t_rep->getCaptionFileList()
	)));
	# --- should the media be clickable to open the overlay?
	if($va_display_options['no_overlay'] || ($vs_display_type == 'media_overlay') || ($vs_display_type == 'media_editor')){
		print $vs_tag;
	}else{
		print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'Detail', 'Object', 'GetRepresentationInfo', array('object_id' => $t_object->getPrimaryKey(), 'representation_id' => $t_rep->getPrimaryKey()))."\"); return false;' >".$vs_tag."</a>";
	}
?>
	</div>