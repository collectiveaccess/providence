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

 ?>
<h1>Search ResourceSpace</h1>
<form action="#" id="caResourceSpaceSearchForm">
    <div class="formLabel formLabelThird">
        <?php print '<div>'._t('Search Resources').'</div><div>'.caHTMLTextInput("term", array('value' => '', 'id' => 'caResourceSpaceId'), array('width' => '200px'))."</div>"; ?>
    </div>
    <div class="formLabel formLabelThird">
        <?php print '<div>'._t('Search Collections').'</div><div>'.caHTMLTextInput("term", array('value' => '', 'id' => 'caResourceSpaceCollectionId'), array('width' => '200px'))."</div>"; ?><br/>
    </div>
    <div class="formLabel formLabelThird">
        <div>Search Systems</div>
        <div id="caResourceSpaceSearchSystems">
        <?php
            foreach($this->getVar('rs_labels') as $vs_rs_code => $vs_rs_label){
                print "<input type='checkbox' id='".$vs_rs_code."_system' name='caResourceSpaceSystems' value='".$vs_rs_code."' checked/>".$vs_rs_label."<br/>";
            }
        ?>
        </div>
    </div>
    <div class="form-label">
        <a href="#" id="caResourceSpaceLookup" class="form-button"><span><?php print caNavIcon(__CA_NAV_ICON_GO__, "30px"); ?> Search</span></a>
    </div>
</form>
<?php
	print caFormTag($this->request, 'Run', 'caResourceSpaceResultsForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true, 'noTimestamp' => true));
?>

 	<div class="caResourceSpaceResultsPagination">
 		<a href='#' id='caResourceSpaceResultsPreviousLink' class='button'>&lsaquo; <?php print _t('Previous'); ?></a>
 		<a href='#' id='caResourceSpaceResultsNextLink' class='button'><?php print _t('Next'); ?> &rsaquo;</a>
 	</div>

 	<div class="caResourceSpaceResultsContainer">
		<div id="caResourceSpaceResults" class="bundleContainer">
			<div class="caResourceSpaceResultsMessage">
				<?php print _t('Enter a ResourceSpace search above to begin'); ?>
			</div>
		</div>
	</div>

    <div class="<?php print $vb_importers_available ? 'formLabel formLabelImport' : 'formLabelError'; ?>">
<?php

	if ($vb_importers_available) {
		print _t('Import using').': '.$this->getVar('importer_list_select');

        print '<span style="float:right">';
        print _t('Log level').': '.caHTMLSelect('log_level', caGetLogLevels(), array('id' => 'caLogLevel'), array('value' => $this->getVar('log_level')));
        print '</span>';
	} else {
		print _t('You must load at least one ResourceSpace mapping before you can import');
	}
    print $vs_control_box = caFormControlBox(
        ($vb_importers_available ? (caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Import"), 'caResourceSpaceResultsForm')) : '').' '.
        (caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', '*', '*', 'Index')),
        '', '' );
?>
 	</div>

</form>

<div class="editorBottomPadding"><!-- empty --></div>

 <script type="text/javascript">
 	var caResourceSpaceStartIndex = 0;
 	var caResourceSpaceLastSearchTerm = '';
 	var caResourceSpaceNumResultsPerLoad = 25;

 	jQuery(document).ready(function() {
 		jQuery("#caResourceSpaceLookup").on("click", function(e) {
            var caResourceSpaceStartIndex = 0;
            var caResourceSpaceSystems = [];
            $('#caResourceSpaceSearchSystems :checked').each(function(){
                caResourceSpaceSystems.push($(this).val());
            });
            caResourceSpaceLastSearchTerm = jQuery("#caResourceSpaceId").val();
            caResourceSpaceCollectionSearchTerm = jQuery("#caResourceSpaceCollectionId").val();
 			caSearchResourceSpace(caResourceSpaceLastSearchTerm, caResourceSpaceCollectionSearchTerm, caResourceSpaceSystems, 0);
 		});

 		// Import click triggers start of import
 		jQuery("#caResourceSpaceImport").on("click", function(e) {
 			jQuery('#caResourceSpaceResultsForm').submit();
 		});

 		// Return triggers search
		jQuery("#caResourceSpaceId").on('keydown', function(e){
			if(e.keyCode == 13) {
				jQuery("#caResourceSpaceLookup").click();
				e.preventDefault();
				return false;
			}
		});

		// Show/hide detailed info on click of item labels
		jQuery(document).on('click', '.caResourceSpaceSearchResultItem', {}, function(e) {
			jQuery(this).parent().find(".caResourceSpaceSearchResultDetails").slideToggle(250, function(e) {
				var url = jQuery(this).parent().find(".caResourceSpaceSearchResultCheckbox").val();

				var details = this;
				jQuery(details).html("<div class='caResourceSpaceDetailsMessage'><?php print caBusyIndicatorIcon($this->request).' '._t('Loading details...'); ?></div>");
				jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Detail'); ?>', { url: url }, function(data) {
					jQuery(details).html(data.display);
				});
			});
			return false;
		});

		jQuery(document).on('click', '#caResourceSpaceResultsNextLink', {}, function(e) {
			caGetNextResourceSpaceResults();
		});
		jQuery(document).on('click', '#caResourceSpaceResultsPreviousLink', {}, function(e) {
			caGetPreviousResourceSpaceResults();
		});

 	});

 	function caSearchResourceSpace(term, collectionTerm, systems, start, c, msg) {
 		if (!msg) { msg = "<?php print addslashes(_t('Searching ResourceSpace...')); ?>"; }
 		if (start <= 0) { start = 0; }
 		if (c <= 0 || c == null) { c = 24; }
        jQuery("#caResourceSpaceResults").html("<div class='caResourceSpaceResultsMessage'><?php print caBusyIndicatorIcon($this->request).' '; ?>" + msg + "</div>");
 		jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'Lookup'); ?>', {term: term+'|'+collectionTerm, start: start, systems: systems, count: c }, function(data) {
            console.log(data);
            if (data['count'] >= 24) {
				jQuery('#caResourceSpaceResultsNextLink').show();
			} else {
				jQuery('#caResourceSpaceResultsNextLink').hide();
			}
			if (start > 0) {
				jQuery('#caResourceSpaceResultsPreviousLink').show();
			} else {
				jQuery('#caResourceSpaceResultsPreviousLink').hide();
			}

			var html = '';
            data.forEach(function(instance){
                var rs_instance = instance[Object.keys(instance)[0]];
                html += '<hr/><h2 class="caResourceSpaceResultHeader">' + rs_instance['label'] + ' Results</h2><hr/>';
                if (jQuery.isArray(rs_instance['results']) && (rs_instance['results'].length > 0)) {
                    var res_header = (rs_instance['results'].length > 1) ? " Resources" : ' Resource';
                    html += '<h5 class="caResourceSpaceResultHeader">' + rs_instance['results'].length + res_header + '</h5><div class="caResourceSpaceResultWrapper"><ul>';
                    for(var i=0; i < rs_instance['results'].length; i++) {
                        var short_title = (rs_instance['results'][i].field8.length > 15) ? rs_instance['results'][i].field8.substr(0, 14) + '&hellip;' : rs_instance['results'][i].field8;
                        short_title += ' (Ref# ' + rs_instance['results'][i].ref + ')';
                        var image_preview = "<img src='" + rs_instance['results'][i].url_pre + "'/>";
                        html += "<li class='caResourceSpaceResultItem'><input type='checkbox' name='ResourceSpaceID[]' value='" + Object.keys(instance)[0] + ":" + rs_instance['results'][i].ref + "' class='caResourceSpaceSearchResultCheckbox'/><strong>" + short_title + "</strong><br/>" + image_preview + " <div class='caResourceSpaceSearchResultDetails' id='caResourceSpaceSearchResult_" + i + "'></div></li>";
    				}
    				html += "</ul></div>";

    			} else {
                    html += '<h2 style="clear:both">No Resource results found for this system</h2>';
                }
                if (jQuery.isArray(rs_instance['collResults']) && (rs_instance['collResults'].length > 0)) {
                    var coll_header = (rs_instance['collResults'].length > 1) ? " Collections" : ' Collection';
                    html += '<hr/><h5 class="caResourceSpaceResultHeader">' + rs_instance['collResults'].length + coll_header + '</h5><div class="caResourceSpaceCollectionResultWrapper"><ul>';
                    rs_instance['collResults'].forEach(function(collResult){
                        var collName = Object.keys(collResult)[0];
                        var collRef = collResult['ref'];
                        html += "<li class='caResourceSpaceCollectionResultItem'><h5>" + collName + " (Total Items in Collection: " + collResult[collName].length + ") <a href='#' onClick='caToggleCollectionSelect(jQuery(this), \"#" + collRef + "Container\"); return false;'>Select All</a> / <a href='#' onClick='caToggleCollectionResultView(jQuery(this), \"#" + collRef + "Container\"); return false;'>Show All Items</a> </h5><ul id='" + collRef + "Container' class='caResourceSpaceCollectionList'>";
                        collResult[collName].forEach(function(item){
                            var item_title = (item['title'].length > 15) ? item['title'].substr(0, 14) + '&hellip;' : item['title'];
                            html += "<li class='caResourceSpaceResultItem'><input type='checkbox' name='ResourceSpaceID[]' value='" + Object.keys(instance)[0] + ":" + collRef + ":" + item['ref'] + "' class='caResourceSpaceSearchResultCheckbox'/><strong>" + item_title + " (Ref# " + item['ref'] + ")</strong><br/><img src='" + item['url_pre'] + "'/></li>";
                        });
                        html += '</ul></li>';
                    });
    				html += "</ul></div>";

    			} else {
                    html += '<h2 style="clear:both">No Collection results found for this system</h2>';
                }
            });
            if(html == '') {
                html = "<div class='caResourceSpaceResultsMessage'><?php print addslashes(_t('No results found')); ?></div>";
            }

			jQuery("#caResourceSpaceResults").html(html);
            jQuery(".caResourceSpaceCollectionList").toggle();
		});
 	}

 	function caGetNextResourceSpaceResults() {
 		if (caResourceSpaceLastSearchTerm) {
 			caResourceSpaceStartIndex += caResourceSpaceNumResultsPerLoad;
 			caSearchResourceSpace(caResourceSpaceLastSearchTerm, caResourceSpaceStartIndex, caResourceSpaceNumResultsPerLoad, "<?php print addslashes(_t('Loading next page...')); ?>");
 			jQuery('#caResourceSpaceResultsPreviousLink').show();
 		}
 	}
 	function caGetPreviousResourceSpaceResults() {
 		if (caResourceSpaceLastSearchTerm && (caResourceSpaceStartIndex > 0)) {
 			caResourceSpaceStartIndex -= caResourceSpaceNumResultsPerLoad;
 			if (caResourceSpaceStartIndex <= 0) {
 				caResourceSpaceStartIndex = 0;
 				jQuery('#caResourceSpaceResultsPreviousLink').hide();
 			}
 			caSearchResourceSpace(caResourceSpaceLastSearchTerm, caResourceSpaceStartIndex, caResourceSpaceNumResultsPerLoad, "<?php print addslashes(_t('Loading previous page...')); ?>");
 		}
 	}

    function caToggleCollectionResultView(thisButton, collId){
        jQuery(collId).toggle('slow');
        setTimeout(function(){
            if(jQuery(collId).is(':visible') == true){
                thisButton.text('Hide All Items');
            } else {
                thisButton.text('Show All Items');
            }
        }, 1000);
    }
    function caToggleCollectionSelect(thisButton, collId){

        var selections = jQuery(collId + ' :input');
        selections.prop('checked', !selections.prop("checked"));
        if(thisButton.text() == 'Select All'){
            thisButton.text('Deselect All');
        } else {
            thisButton.text('Select All');
        }
    }
 </script>
