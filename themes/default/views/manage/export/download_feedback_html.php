<?php
	$vb_success = $this->getVar('alternate_destination_success');
	$vs_display_name = $this->getVar('dest_display_name');

	if($vb_success) {
		print "<div>Upload to <i>{$vs_display_name}</i> successful</div>";
	} else {
		print "<div>There was an error while uploading to <i>{$vs_display_name}</i>.</div>";
	}
