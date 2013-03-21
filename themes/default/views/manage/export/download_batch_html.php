<?php
/* ----------------------------------------------------------------------
 * manage/export/download_batch_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

$vs_file = $this->getVar('file');
$vs_ext = $this->getVar('extension');
$vs_content_type = $this->getVar('content_type');
if(!$vs_file){
	print _t('Invalid parameters');
} else {
	header('Content-Type: '.$vs_content_type.'; charset=UTF-8');
	header('Content-Disposition: attachment; filename="batch_export.'.$vs_ext.'"');
	header('Content-Transfer-Encoding: binary');
	readfile($vs_file);
	@unlink($vs_file);
	exit();	
}