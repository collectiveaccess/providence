<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_comments_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 
	$vn_moderated_comment_count = $this->getVar('moderated_comment_count');
	$vn_unmoderated_comment_count = $this->getVar('unmoderated_comment_count');
	$vn_total_comment_count = $this->getVar('total_comment_count');
?>
	<h3><?php print _t('User comments'); ?>:
	<div><?php
			if ($vn_unmoderated_comment_count == 1) {
				print _t("1 comment needs moderation");
			} else {
				print _t("%1 comments need moderation", $vn_unmoderated_comment_count);
			}
	?></div>
	<div><?php
			if ($vn_moderated_comment_count == 1) {
				print _t("1 moderated comment");
			} else {
				print _t("%1 moderated comments", $vn_moderated_comment_count);
			}
	?></div>
	<div><?php
			if ($vn_total_comment_count == 1) {
				print _t("1 comment total");
			} else {
				print _t("%1 comments total", $vn_total_comment_count);
			}
	?></div>
	</h3>