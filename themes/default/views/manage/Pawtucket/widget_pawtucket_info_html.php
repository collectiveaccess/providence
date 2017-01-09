<?php
/* ----------------------------------------------------------------------
 * app/views/manage/widget_pawtucket_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 
 	$pn_page_id = $this->getVar('page_id'); 
 	$vn_num_pages = $this->getVar('num_pages'); 
 	$vn_num_public_pages = $this->getVar('num_public_pages');
 	
 	if ($pn_page_id) {
 		print caEditorInspector($this);
 	} else {
?>
<h3 class='pawtucketStats'><?php print _t('Site content'); ?>:
<div><?php 
	print (($vn_num_pages == 1) ? _t('%1 page', $vn_num_pages) : _t('%1 pages', $vn_num_pages))."<br/>\n"; 
	print (($vn_num_public_pages == 1) ? _t('%1 public page', $vn_num_public_pages) : _t('%1 public pages', $vn_num_public_pages))."<br/>\n"; 

?></div>
</h3>
<?php
	}