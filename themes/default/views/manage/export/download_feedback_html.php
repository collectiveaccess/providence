<?php
/* ----------------------------------------------------------------------
 * manage/export/download_feedback_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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

	$vb_success = $this->getVar('alternate_destination_success');
	$vs_display_name = $this->getVar('dest_display_name');

	if($vb_success) {
		print "<div>"._t("Upload to <i>%1</i> successful", $vs_display_name)."</div>";
	} else {
		print "<div>"._t("There was an error while uploading to <i>%1</i>. Check the events log for more information.", $vs_display_name)."</div>";
	}
