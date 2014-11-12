<?php
	$vb_success = $this->getVar('alternate_destination_success');
	$vs_display_name = $this->getVar('dest_display_name');

	if($vb_success) {
		print "<div>"._t("Upload to <i>%1</i> successful", $vs_display_name)."</div>";
	} else {
		print "<div>"._t("There was an error while uploading to <i>%1</i>. Check the Event Log for more information.", $vs_display_name)."</div>";
	}
