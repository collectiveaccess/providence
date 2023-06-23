<?php
/* ----------------------------------------------------------------------
 * views/editor/objects/summary_html_ajax.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2021 Whirl-i-Gig
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
	$t_item 				= $this->getVar('t_subject');
	$item_id 				= $this->getVar('subject_id');

	$t_display 				= $this->getVar('t_display');
	$placements 			= $this->getVar("placements");
	$reps 					= $t_item->getRepresentations(array("thumbnail", "small", "medium"));
?>
    <div id="summary" style="clear: both;">
<?php
    print caEditorPrintSummaryControls($this, TRUE);
    print caEditorFieldList($this->request, $t_item, [], []);
?>
	<div id="title">
		<?= $t_item->getLabelForDisplay(); ?>
	</div><!-- end title -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr class='summaryImages'>
			<td valign="top" align="center" width="744">
<?php
	if (is_array($reps)) {
		foreach($reps as $rep) {
			if(sizeof($reps) > 1){
				# --- more than one rep show thumbnails
				$padding_top = ((120 - $rep["info"]["thumbnail"]["HEIGHT"])/2) + 5;
				print "<table style='float:left; margin: 0px 16px 10px 0px; ".$clear."' cellpadding='0' cellspacing='0'><tr><td align='center' valign='middle'><div class='thumbnailsImageContainer' id='container".$rep['representation_id']."' style='padding: ".$padding_top."px 5px ".$padding_top."px 5px;' onmouseover='$(\".download".$rep['representation_id']."\").show();' onmouseout='$(\".download".$rep['representation_id']."\").hide();'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $item_id, 'representation_id' => $rep['representation_id']))."\");'>".$rep['tags']['thumbnail']."</a>\n";

				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div class='download".$rep['representation_id']." downloadMediaContainer'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1), 'downloadMedia', 'editor/objects', 'ObjectEditor', 'DownloadMedia', array('object_id' => $item_id, 'representation_id' => $rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div></td></tr></table>\n";
			}else{
				# --- one rep - show medium rep
				print "<div id='container".$rep['representation_id']."' class='oneThumbContainer' onmouseover='$(\".download".$rep['representation_id']."\").show();' onmouseout='$(\".download".$rep['representation_id']."\").hide();'>";
				print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'editor/objects', 'ObjectEditor', 'GetMediaOverlay', array('object_id' => $item_id, 'representation_id' => $rep['representation_id']))."\");'>".$rep['tags']['medium']."</a>\n";
				if ($this->request->user->canDoAction('can_download_ca_object_representations')) {
					print "<div class='download".$rep['representation_id']." downloadMediaContainer'>".caNavLink($this->request, caNavIcon(__CA_NAV_ICON_DOWNLOAD__, 1), 'downloadMedia', 'editor/objects', 'ObjectEditor', 'DownloadMedia', array('object_id' => $item_id, 'representation_id' => $rep['representation_id'], 'version' => 'original'))."</div>\n";
				}
				print "</div>";
			}
		}
	}

?>
			</td>
		</tr>
		<tr>
			<td valign="top" align="left" style="padding-right:10px;">
				<div id="summary-html-data-page">
					<div class="_error"></div>
					<div class="_indicator">
						<img src='<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif'/>
						Loading...
					</div>
					<div class="content_wrapper">
						<?php // This looks in the wrong place now, $this is of class View not ActionController (maybe 'Search/ObjectSearch/summary_html_ajax_placements.php'?) ?>
						<?php // print $this->render('summary_html_ajax_placements.php'); ?>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div><!-- end summary -->
<?php
TooltipManager::add('#printButton', _t("Download Summary as PDF"));
TooltipManager::add('a.downloadMediaContainer', _t("Download Media"));
?>

<?php $data_url = $this->request->getControllerUrl() . '/SummaryData'; ?>
<?php $display_url = $this->request->getControllerUrl() . '/SummaryDisplay'; ?>

<script type="text/javascript">
    $(document).ready(function () {
        <?php // Show/hide the loading spinner div. ?>
        $(document).ajaxStart(() => {
            $('#summary-html-data-page ._error').empty();
            $('#summary-html-data-page ._indicator').show();
        });
        $(document).ajaxStop(() => $('#summary-html-data-page ._indicator').hide());

        loadDisplay();
    });

    <?php // Load the display 'template' of sorts. ?>
    function loadDisplay() {
        $.ajax({
            type: 'POST',
            url: '<?php print $display_url; ?>',
            data: $('#caSummaryDisplaySelectorForm').serialize(),
            error: (jqXHR, textStatus, errorThrown) => {
	            $('#summary-html-data-page ._error').html('Error: ' + textStatus);
            },
            success: (data) => {
                $('#summary-html-data-page .content_wrapper').empty();
                $('#summary-html-data-page .content_wrapper').html(data);

                loadData();
            },
        });
    }

    <?php // Load the actual data for the display 'template'. ?>
    function loadData() {
        $('#summary-html-data-page ._content').each(
            function() {
                let id = $(this).attr('placementId');
                $.ajax({
                    url: '<?php print $data_url; ?>',
                    data: $('#caSummaryDisplaySelectorForm').serialize() + '&va_placement_id=' + id,
	                error: (jqXHR, textStatus, errorThrown) => {
                        $('#summary-html-data-page ._error').html('Error: ' + textStatus);
                    },
                    success: (data) => {
                        $('#summary-html-data-page ._content[placementid="' + id + '"]').html(data);
                    },
                });
            }
        );
    }

</script>
