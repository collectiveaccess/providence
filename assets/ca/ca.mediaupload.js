/* ----------------------------------------------------------------------
 * js/ca.mediaupload.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2023 Whirl-i-Gig
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
 
var caUI = caUI || {};

(function ($) {
	caUI.initMediaUploadManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			fieldNamePrefix: '',
			uploadURL: null,
			setCenterURL: null,
			annotationEditorURL: null,
			primaryID: null,
			representationID: null,
			uploadAreaMessage: 'Upload here',
			uploadAreaIndicator: 'Uploading...',
			progressMessage: "Progress: ",
			uploadTotalMessage: "%count selected",
			uploadTotalMessageClass: "mediaUploadAreaMessageCount",
			isPrimaryLabel: "Is primary",
			index: 0,
			maxFilesize: null,
			maxFilesizeTxt: null
		}, options);
		
		jQuery('#' + that.fieldNamePrefix + 'UploadAreaMessage' + that.index).html(that.uploadAreaMessage).on('click', function(e) {
			jQuery('#' + that.fieldNamePrefix + 'UploadFileControl' + that.index).click();
			e.preventDefault();
		});
		jQuery('#' + that.fieldNamePrefix + 'progressbar' + that.index).progressbar({ value: 0 });
		jQuery('#' + that.fieldNamePrefix + 'UploadArea' + that.index).fileupload({
			dataType: 'json',
			url: that.uploadURL,
			dropZone: jQuery('#' + that.fieldNamePrefix + 'UploadArea' + that.index),
			fileInput: jQuery('#' + that.fieldNamePrefix + 'UploadFileControl' + that.index),
			singleFileUploads: false,
			change: function(e, data) {
				data.files.map(function (file) {
					if(options.maxFilesize && file.size > options.maxFilesize ) {
						jQuery('#' + that.fieldNamePrefix + 'ProgressGroup' + that.index).show(250);
						jQuery('#' + that.fieldNamePrefix + 'ProgressStatus' + that.index).html(`file exceeds maximum upload size ${options.maxFilesizeTxt}`);
						setTimeout(function() {
							jQuery('#' + that.fieldNamePrefix + 'ProgressGroup' + that.index).hide(250);
							jQuery('#' + that.fieldNamePrefix + 'UploadArea' + that.index).show(150);
						}, 1000);
						throw `file exceeds maximum upload size ${options.maxFilesizeTxt}`;
					}
				});
			},
			done: function (e, data) {
				if (data.result.error) {
					jQuery('#' + that.fieldNamePrefix + 'ProgressGroup' + that.index).show(250);
					jQuery('#' + that.fieldNamePrefix + 'ProgressStatus' + that.index).html(data.result.error);
					setTimeout(function() {
						jQuery('#' + that.fieldNamePrefix + 'ProgressGroup' + that.index).hide(250);
					}, 3000);
				} else {
					jQuery('#' + that.fieldNamePrefix + 'ProgressStatus' + that.index).html(data.result.msg ? data.result.msg : "");
					setTimeout(function() {
						jQuery('#' + that.fieldNamePrefix + 'ProgressGroup' + that.index).hide(250);
						jQuery('#' + that.fieldNamePrefix + 'UploadArea' + that.index).show(150);
					}, 1500);
				}
				
				that.addFiles(data.result.files);
			},
			progressall: function (e, data) {
				var m = "<div class='" + that.uploadTotalMessageClass + "'>" + that.uploadAreaIndicator.replace("%percent", parseInt(data.loaded / data.total * 100, 10) + "%") + "</div>";
				jQuery('#' + that.fieldNamePrefix + 'UploadAreaMessage' + that.index).html(m);
			}
		});	
		
		// --------------------------------------------------------------------------------
		// Methods
		// --------------------------------------------------------------------------------
		that.addFiles = function(newFiles) {
		    var existing_files = jQuery('#' + that.fieldNamePrefix + 'MediaRefs' + that.index).val();
            var files = (existing_files && existing_files.length > 0) ? existing_files.split(";") : [];
            files = files.concat(newFiles).filter((v, i, a) => a.indexOf(v) === i);	// unique files only
            
            var m = "<div class='" + that.uploadTotalMessageClass + "'>" + that.uploadTotalMessage.replace("%count", files.length) + "</div>";
            jQuery('#' + that.fieldNamePrefix + 'UploadAreaMessage' + that.index).html(that.uploadAreaMessage + ((files.length > 0) ? m : ''));
            jQuery('#' + that.fieldNamePrefix + 'MediaRefs' + that.index).val(files.join(";"));
            
            jQuery('#' + that.fieldNamePrefix + 'UploadCount' + that.index).data('count', files.length);
		};
		
		that.openEditor = function() {
			jQuery('#' + that.fieldNamePrefix + '_rep_info_ro' + that.index).hide();
			jQuery('#' + that.fieldNamePrefix + '_MediaMetadataEditButton' + that.index).hide();
			jQuery('#' + that.fieldNamePrefix + '_detail_editor_' + that.index).removeClass('mediaUploadInfoArea').addClass('mediaUploadEditArea').slideDown(250);
			jQuery('#' + that.fieldNamePrefix + '_objectRepresentationMetadataEditorMediaRightCol' + that.index).hide();
		};
	
		that.closeEditor = function() {
			jQuery('#' + that.fieldNamePrefix + '_detail_editor_' + that.index).removeClass('mediaUploadEditArea').addClass('mediaUploadInfoArea').slideUp(250, function() {
				jQuery('#' + that.fieldNamePrefix + '_rep_info_ro' + that.index).slideDown(50);
			});
			jQuery('#' + that.fieldNamePrefix + '_change_indicator_' + that.index).show();
			jQuery('#' + that.fieldNamePrefix + '_MediaMetadataEditButton' + that.index).show();
			jQuery('#' + that.fieldNamePrefix + '_objectRepresentationMetadataEditorMediaRightCol' + that.index).show();
		};
	
		that.setAsPrimary = function() {
			jQuery('.' + that.fieldNamePrefix + '_is_primary').val('');
			jQuery('#' + that.fieldNamePrefix + '_is_primary_' + that.index).val('1');
			jQuery('.caObjectRepresentationPrimaryIndicator').hide();
			if (that.representationID != that.primaryID) {
				jQuery('#' + that.fieldNamePrefix + '_is_primary_indicator_' + that.index).show();
			}
		};
		that.showImageCenterEditor = function() {
			caUI.mediaUploadSetCenterPanels[that.fieldNamePrefix].showPanel(that.setCenterURL, that.setImageCenterForSave);
		}
		
		that.setImageCenterForSave = function() {
			jQuery("#topNavContainer").show(250);
			jQuery('#' + that.fieldNamePrefix + '_change_indicator_' + that.index).show();
		
			var center_x = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('left'))/parseInt(jQuery('#caImageCenterEditorImage').width());
			var center_y = parseInt(jQuery('#caObjectRepresentationSetCenterMarker').css('top'))/parseInt(jQuery('#caImageCenterEditorImage').height());
			
			jQuery('#' + that.fieldNamePrefix + '_center_x_' + that.index).val(center_x);
			jQuery('#' + that.fieldNamePrefix + '_center_y_' + that.index).val(center_y);
		}
		
		that.showAnnotationEditor = function(e) {
			caUI.mediaUploadAnnotationEditorPanels[that.fieldNamePrefix].showPanel(that.annotationEditorURL);
			if(e) { e.preventDefault(); }
			return false;
		}
		
		that.showEmbeddedMetadata = function(e) {
			jQuery('#' + that.fieldNamePrefix + '_media_metadata_' + that.index).slideToggle(300);
			if(e) { e.preventDefault(); }1
			return false;
		}
		
		jQuery('#' + that.fieldNamePrefix + '_MediaMetadataEditButton' + that.index).off('click').on('click', function(e) {
			that.openEditor();
			e.preventDefault();
			return false;
		});
		
		jQuery('#' + that.fieldNamePrefix + '_MediaMetadataSaveButton' + that.index).off('click').on('click', function(e) {
			that.closeEditor();
			e.preventDefault();
			return false;
		});
		
		jQuery('#' + that.fieldNamePrefix + '_SetAsPrimaryButton' + that.index).off('click').on('click', function(e) {
			that.setAsPrimary();
			e.preventDefault();
			return false;
		});
		
		jQuery('#' + that.fieldNamePrefix + '_edit_image_center_' + that.index).off('click').on('click', function(e) {
			that.showImageCenterEditor(that.index);
			e.preventDefault();
			return false;
		});
		
		jQuery('#' + that.fieldNamePrefix + '_edit_annotations_button_' + that.index).off('click').on('click', function(e) {
			that.showAnnotationEditor();
			e.preventDefault();
			return false;
		});
		
		jQuery('#' + that.fieldNamePrefix + '_caObjectRepresentationMetadataButton_' + that.index).off('click').on('click', function(e) {
			that.showEmbeddedMetadata();
			e.preventDefault();
			return false;
		});
		
		
		
		if (parseInt(that.representationID) === parseInt(that.primaryID)) {
			jQuery('#' + that.fieldNamePrefix + '_SetAsPrimaryButton' + that.index).html(that.isPrimaryLabel);
		}
		
		if(!caUI.mediaUploadAnnotationEditorPanels) { caUI.mediaUploadAnnotationEditorPanels = []; }
		if (!caUI.mediaUploadAnnotationEditorPanels[that.fieldNamePrefix]) {
			caUI.mediaUploadAnnotationEditorPanels[that.fieldNamePrefix] = caUI.initPanel({ 
				panelID: 'caAnnoEditor' + that.fieldNamePrefix,								/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'caAnnoEditor' + that.fieldNamePrefix + 'ContentArea',		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: '.close',
				centerHorizontal: true,
				onOpenCallback: function() {
					jQuery('#topNavContainer').hide(250);
				},
				onCloseCallback: function() {
					jQuery('#topNavContainer').show(250);
				}
			})
			jQuery("body").append('<div id="caAnnoEditor' + that.fieldNamePrefix + '" class="caAnnoEditorPanel"><div id="caAnnoEditor' + that.fieldNamePrefix + 'ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
		}
		
		if(!caUI.mediaUploadSetCenterPanels) { caUI.mediaUploadSetCenterPanels = []; }
		if (!caUI.mediaUploadSetCenterPanels[that.fieldNamePrefix]) {
			caUI.mediaUploadSetCenterPanels[that.fieldNamePrefix] = caUI.initPanel({ 
				panelID: 'caImageCenterEditor' + that.fieldNamePrefix,								/* DOM ID of the <div> enclosing the panel */
				panelContentID: 'caImageCenterEditor' + that.fieldNamePrefix + 'ContentArea',		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: '#000000',				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: '.close',
				centerHorizontal: true,
				onOpenCallback: function() {
					jQuery('#topNavContainer').hide(250);
				},
				onCloseCallback: function() {
					jQuery('#topNavContainer').show(250);
				}
			});
			jQuery('body').append('<div id="caImageCenterEditor' + that.fieldNamePrefix + '" class="caAnnoEditorPanel"><div id="caImageCenterEditor' + that.fieldNamePrefix + 'ContentArea" class="caAnnoEditorPanelContentArea"></div></div>');
		}
		
		jQuery(".caAnnoEditorLaunchButton").hide();
		jQuery(".annotationTypeClipTimeBasedVideo, .annotationTypeClipTimeBasedAudio").show();
		
		jQuery(".caSetImageCenterLaunchButton").hide();
		jQuery(".annotationTypeSetCenterImage, .annotationTypeSetCenterDocument").show();
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
})(jQuery);
