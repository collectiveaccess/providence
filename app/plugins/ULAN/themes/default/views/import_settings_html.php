<?php
/* ----------------------------------------------------------------------
 * app/plugins/ULAN/controllers/ImportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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
		($vb_importers_available ? (caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Import"), 'caULANResultsForm')) : '').' '.
		(caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', '*', '*', 'Index')),
		'',
		''
	);
 ?>
 <form action="#" id="caULANSearchForm">
 	<div class="formLabel">
 		<?php print _t('Find in ULAN').': '.caHTMLTextInput("term", array('value' => '', 'id' => 'caULANTerm'), array('width' => '250px')); ?>
 		<a href="#" id="caULANTermLookup" class="button"><?php print caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
 	</div>
 </form>
 
<?php
	print caFormTag($this->request, 'Run', 'caULANResultsForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
?>
	<div class="<?php print $vb_importers_available ? 'formLabel' : 'formLabelError'; ?>">
<?php
	if ($vb_importers_available) {
		print _t('Import using').': '.$this->getVar('importer_list_select');
	} else {
		print _t('You must load at least one ULAN mapping before you can import');
	}
?>
 	</div>
 	
 	<div class="caULANResultsPagination">
 		<a href='#' id='caULANResultsPreviousLink' class='button'>&lsaquo; <?php print _t('Previous'); ?></a>
 		<a href='#' id='caULANResultsNextLink' class='button'><?php print _t('Next'); ?> &rsaquo;</a>
 	</div>
 	
 	<br style="clear"/>
 	
	<div id="caULANResults" class="bundleContainer">
		<div class="caULANResultsMessage">
			<?php print _t('Enter a ULAN search above to begin'); ?>
		</div>
	</div>
	
	<div class='formLabel'>
<?php
		if ($vb_importers_available) {
			print _t('Log level').': '.caHTMLSelect('log_level', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $this->getVar('log_level')));
			print "<a id='caULANImport'>Run import</a>";
		}
?>
	</div>
</form>

<div class="editorBottomPadding"><!-- empty --></div>
 
 <script type="text/javascript">
 	var caULANStartIndex = 0;
 	var caULANLastSearchTerm = '';
 	var caULANNumResultsPerLoad = 50;
 	
 	jQuery(document).ready(function() {
 		jQuery("#caULANTermLookup").on("click", function(e) {
 			caULANStartIndex = 0;
 			caULANLastSearchTerm = jQuery("#caULANTerm").val();
 			caSearchULAN(caULANLastSearchTerm, 0);
 		});
 		
 		// Import click triggers start of import
 		jQuery("#caULANImport").on("click", function(e) {
 			jQuery('#caULANResultsForm').submit();
 		});
 		
 		// Return triggers search
		jQuery("#caULANTerm").on('keydown', function(e){
			if(e.keyCode == 13) {
				jQuery("#caULANTermLookup").click();
				e.preventDefault();
				return false;
			}
		});
		
		// Show/hide detailed info on click of item labels
		jQuery(document).on('click', '.caULANSearchResultItem', {}, function(e) {
			jQuery(this).parent().find(".caULANSearchResultDetails").slideToggle(250, function(e) {
				var url = jQuery(this).parent().find(".caULANSearchResultCheckbox").val();
				
				var details = this;
				jQuery(details).html("<div class='caULANDetailsMessage'><?php print caBusyIndicatorIcon($this->request).' '._t('Loading details...'); ?></div>");
				jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Detail'); ?>', { url: url }, function(data) {
					jQuery(details).html(data.display);
				});
			});
			return false;
		});
		
		jQuery(document).on('click', '#caULANResultsNextLink', {}, function(e) {
			caGetNextULANResults();
		});
		jQuery(document).on('click', '#caULANResultsPreviousLink', {}, function(e) {
			caGetPreviousULANResults();
		});
 	});
 	
 	function caSearchULAN(term, start, c, msg) {
 		if (!msg) { msg = "<?php print addslashes(_t('Searching ULAN...')); ?>"; }
 		if (start <= 0) { start = 0; }
 		if (c <= 0) { c = 10; }
 		jQuery("#caULANResults").html("<div class='caULANResultsMessage'><?php print caBusyIndicatorIcon($this->request).' '; ?>" + msg + "</div>");
		jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Lookup'); ?>', {term: term, start: start, count: c }, function(data) {
			if (data['count'] >= 50) {
				jQuery('#caULANResultsNextLink').show();
			} else {
				jQuery('#caULANResultsNextLink').hide();
			}
			if (start > 0) {
				jQuery('#caULANResultsPreviousLink').show();
			} else {
				jQuery('#caULANResultsPreviousLink').hide();
			}
			
			var html = '';
			if (jQuery.isArray(data['results']) && (data['results'].length > 0)) {
				for(var i=0; i < data['results'].length; i++) {
					if (data['results'][i].idno > 0) {
						html += "<li class='caULANResultItem'><input type='checkbox' name='ULANID[]' value='" + data['results'][i].url + "' class='caULANSearchResultCheckbox'/> <a href='#' class='caULANSearchResultItem'>" + data['results'][i].label + "</a> <div class='caULANSearchResultDetails' id='caULANSearchResult_" + i + "'></div></li>";
					} else {
						html += "<li class='caULANResultItem'>" + data['results'][i].label + "</li>";
					}
				}
				html = "<ul>" + html + "</ul>";
			} else {
				html = "<div class='caULANResultsMessage'><?php print addslashes(_t('No results found')); ?></div>";
			}
			
			jQuery("#caULANResults").html(html);
		});
 	}
 	
 	function caGetNextULANResults() {
 		if (caULANLastSearchTerm) {
 			caULANStartIndex += caULANNumResultsPerLoad;
 			caSearchULAN(caULANLastSearchTerm, caULANStartIndex, caULANNumResultsPerLoad, "<?php print addslashes(_t('Loading next page...')); ?>");
 			jQuery('#caULANResultsPreviousLink').show();
 		}
 	}
 	function caGetPreviousULANResults() {
 		if (caULANLastSearchTerm && (caULANStartIndex > 0)) {
 			caULANStartIndex -= caULANNumResultsPerLoad;
 			if (caULANStartIndex <= 0) {
 				caULANStartIndex = 0;
 				jQuery('#caULANResultsPreviousLink').hide();
 			}
 			caSearchULAN(caULANLastSearchTerm, caULANStartIndex, caULANNumResultsPerLoad, "<?php print addslashes(_t('Loading previous page...')); ?>");
 		}
 	}
 </script>
