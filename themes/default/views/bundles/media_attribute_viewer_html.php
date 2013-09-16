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
	$vs_display_mimetype		= $this->getVar('version_mimetype');
	$vs_original_mimetype		= $this->getVar('mimetype');
	
	if ($vs_display_mimetype ==  'application/pdf') {
		switch($vs_pdf_pref = $this->request->user->getPreference('pdf_viewer')) {
			case 'pdfjs':
				$vb_should_use_pdfjs_viewer = true;
				break;
			case 'native_plus_pdfjs':
				if ($this->request->session->getVar('has_pdf_plugin')) { 
					$vb_should_use_native_pdf_viewer = true;
				} else {
					$vb_should_use_pdfjs_viewer = true;	
				}
				break;
			case 'native_plus_book_viewer':
				if ($this->request->session->getVar('has_pdf_plugin')) { 
					$vb_should_use_native_pdf_viewer = true;
				} else {
					$vb_should_use_pdfjs_viewer = true;	
				}
				break;
			case 'book_viewer':
				$vb_should_use_pdfjs_viewer = true;
				break;
			default:
				if (!($vb_should_use_pdfjs_viewer = isset($va_display_options['use_book_viewer']) && (bool)$va_display_options['use_book_viewer'])) {
					$vb_should_use_pdfjs_viewer = isset($va_display_options['use_pdfjs_viewer']) && (bool)$va_display_options['use_pdfjs_viewer'];
				}
				break;
		}
	} else {
		$vb_should_use_pdfjs_viewer = $vb_should_use_native_pdf_viewer = false;
	}	
	
	if ($vb_should_use_pdfjs_viewer && $this->request->config->get('use_pdfjs_viewer')) {
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
				PDFJS.webViewerLoad('<?php print $t_value->getMediaUrl('value_blob', ($this->getVar('mimetype') ==  'application/pdf') ? 'original' : 'pdf'); ?>'); 
			});
		});
	</script>
<?php	
		} else {	
?>
	<!-- Controls -->
	<div class="caMediaOverlayControls">
			<div class='close'><a href="#" onclick="caMediaPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
<?php
	if ($this->request->user->canDoAction('can_download_media')) {
?>
				<div class='download'>
<?php 
					if (is_array($va_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
						// -- provide user with a choice of versions to download
						print caFormTag($this->request, 'DownloadMedia', 'caMediaDownloadForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
						print caHTMLSelect('version', $va_versions, array('style' => 'font-size: 9px;'));
						print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/downloadWhite.png' border='0' title='"._t("Download media")."' valign='bottom'/>", '', 'caMediaDownloadForm', 'caMediaDownloadFormButton');
						print caHTMLHiddenInput('value_id', array('value' => $t_value->getPrimaryKey()));
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
					//$vs_label = $t_object->getLabelForDisplay();
					//print (mb_strlen($vs_label) > 80) ? mb_substr($vs_label, 0, 80)."..." : $vs_label;
				
					//if($t_object->get("idno")){
					//	print " [".$t_object->get("idno")."]";
					//}
?>			
				</div>
<?php
		
?>
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
<?php
	}
?>