<?php
/* ----------------------------------------------------------------------
 * app/plugins/WorldCat/controllers/ImportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2017 Whirl-i-Gig
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
 
 	
 	$va_importer_list = $this->getVar('importer_list');
 	$vb_importers_available = (is_array($va_importer_list) && sizeof($va_importer_list));
 	
 	print $vs_control_box = caFormControlBox(
		($vb_importers_available ? (caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Import"), 'caWorldCatResultsForm')) : '').' '.
		(caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', '*', '*', 'Index')),
		'',
		''
	);
 ?>
 <form action="#" id="caWorldCatSearchForm">
 	<div class="formLabel">
 		<?php print _t('Find in WorldCat').': '.caHTMLTextInput("term", array('value' => '', 'id' => 'caWorldCatTerm'), array('width' => '250px')); ?>
 		<a href="#" id="caWorldCatTermLookup" class="button"><?php print caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
 	</div>
 </form>
 
<?php
	print caFormTag($this->request, 'Run', 'caWorldCatResultsForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
?>
	<div class="<?php print $vb_importers_available ? 'formLabel' : 'formLabelError'; ?>">
<?php
	if ($vb_importers_available) {
		print _t('Import using').': '.$this->getVar('importer_list_select');
	} else {
		print _t('You must load at least one WorldCat mapping before you can import');
	}
?>
 	</div>
 	
 	<div class="caWorldCatResultsPagination">
 		<a href='#' id='caWorldCatResultsPreviousLink' class='button'>&lsaquo; <?php print _t('Previous'); ?></a>
 		<a href='#' id='caWorldCatResultsNextLink' class='button'><?php print _t('Next'); ?> &rsaquo;</a>
 	</div>
 	
 	<br style="clear"/>
 	
 	<div class="caWorldCatResultsContainer">
		<div id="caWorldCatResults" class="bundleContainer">
			<div class="caWorldCatResultsMessage">
				<?php print _t('Enter a WorldCat search above to begin'); ?>
			</div>
		</div>
<?php
	print $this->request->config->get('worlcat_isbn_exists_key');
?>
	</div>
	
	<div class='formLabel'>
<?php
		if ($vb_importers_available) {
			print _t('Log level').': '.caHTMLSelect('log_level', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $this->getVar('log_level')));
		}
?>
	</div>
</form>

<div class="editorBottomPadding"><!-- empty --></div>
 
 <script type="text/javascript">
 	var caWorldCatStartIndex = 0;
 	var caWorldCatLastSearchTerm = '';
 	var caWorldCatNumResultsPerLoad = 25;
 	
 	jQuery(document).ready(function() {
 		jQuery("#caWorldCatTermLookup").on("click", function(e) {
 			caWorldCatStartIndex = 0;
 			caWorldCatLastSearchTerm = jQuery("#caWorldCatTerm").val();
 			caSearchWorldCat(caWorldCatLastSearchTerm, 0);
 		});
 		
 		// Import click triggers start of import
 		jQuery("#caWorldCatImport").on("click", function(e) {
 			jQuery('#caWorldCatResultsForm').submit();
 		});
 		
 		// Return triggers search
		jQuery("#caWorldCatTerm").on('keydown', function(e){
			if(e.keyCode == 13) {
				jQuery("#caWorldCatTermLookup").click();
				e.preventDefault();
				return false;
			}
		});
		
		// Show/hide detailed info on click of item labels
		jQuery(document).on('click', '.caWorldCatSearchResultItem', {}, function(e) {
			jQuery(this).parent().find(".caWorldCatSearchResultDetails").slideToggle(250, function(e) {
				var url = jQuery(this).parent().find(".caWorldCatSearchResultCheckbox").val();
				
				var details = this;
				jQuery(details).html("<div class='caWorldCatDetailsMessage'><?php print caBusyIndicatorIcon($this->request).' '._t('Loading details...'); ?></div>");
				jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Detail'); ?>', { url: url }, function(data) {
					jQuery(details).html(data.display);
				});
			});
			return false;
		});
		
		jQuery(document).on('click', '#caWorldCatResultsNextLink', {}, function(e) {
			caGetNextWorldCatResults();
		});
		jQuery(document).on('click', '#caWorldCatResultsPreviousLink', {}, function(e) {
			caGetPreviousWorldCatResults();
		});
 	});
 	
 	function caSearchWorldCat(term, start, c, msg) {
 		if (!msg) { msg = "<?php print addslashes(_t('Searching WorldCat...')); ?>"; }
 		if (start <= 0) { start = 0; }
 		if (c <= 0) { c = 10; }
 		jQuery("#caWorldCatResults").html("<div class='caWorldCatResultsMessage'><?php print caBusyIndicatorIcon($this->request).' '; ?>" + msg + "</div>");
		jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Lookup'); ?>', {term: term, start: start, count: c }, function(data) {
			if (data['count'] >= 25) {
				jQuery('#caWorldCatResultsNextLink').show();
			} else {
				jQuery('#caWorldCatResultsNextLink').hide();
			}
			if (start > 0) {
				jQuery('#caWorldCatResultsPreviousLink').show();
			} else {
				jQuery('#caWorldCatResultsPreviousLink').hide();
			}
			
			var html = '';
			if (jQuery.isArray(data['results']) && (data['results'].length > 0)) {
				for(var i=0; i < data['results'].length; i++) {
					if (data['results'][i].id > 0) {
						var existing_object_display_text = data['results'][i].existingObject;
						html += "<li class='caWorldCatResultItem'><input type='checkbox' name='WorldCatID[]' value='" + data['results'][i].id + "' class='caWorldCatSearchResultCheckbox'/> <a href='#' class='caWorldCatSearchResultItem'>" + data['results'][i].label + "</a> " + existing_object_display_text + " <div class='caWorldCatSearchResultDetails' id='caWorldCatSearchResult_" + i + "'></div></li>";
					} else {
						html += "<li class='caWorldCatResultItem'>" + data['results'][i].label + "</li>";
					}
				}
				html = "<ul>" + html + "</ul>";
			} else {
				html = "<div class='caWorldCatResultsMessage'><?php print addslashes(_t('No results found')); ?></div>";
			}
			
			jQuery("#caWorldCatResults").html(html);
		});
 	}
 	
 	function caGetNextWorldCatResults() {
 		if (caWorldCatLastSearchTerm) {
 			caWorldCatStartIndex += caWorldCatNumResultsPerLoad;
 			caSearchWorldCat(caWorldCatLastSearchTerm, caWorldCatStartIndex, caWorldCatNumResultsPerLoad, "<?php print addslashes(_t('Loading next page...')); ?>");
 			jQuery('#caWorldCatResultsPreviousLink').show();
 		}
 	}
 	function caGetPreviousWorldCatResults() {
 		if (caWorldCatLastSearchTerm && (caWorldCatStartIndex > 0)) {
 			caWorldCatStartIndex -= caWorldCatNumResultsPerLoad;
 			if (caWorldCatStartIndex <= 0) { 
 				caWorldCatStartIndex = 0; 
 				jQuery('#caWorldCatResultsPreviousLink').hide();
 			}
 			caSearchWorldCat(caWorldCatLastSearchTerm, caWorldCatStartIndex, caWorldCatNumResultsPerLoad, "<?php print addslashes(_t('Loading previous page...')); ?>");
 		}
 	}
 </script>