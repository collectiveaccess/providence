<?php
/* ----------------------------------------------------------------------
 * app/views/object_representations/object_representation_download_binary.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
$show_version = $this->getVar('version');
$file_path = $this->getVar('version_path');

header("Content-type: application/octet-stream");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-Length: ' . filesize($file_path));
header("Content-Disposition: attachment; filename=".caEscapeFilenameForDownload($this->getVar('version_download_name')));

set_time_limit(0);
$o_fp = @fopen($file_path,"rb");
while(is_resource($o_fp) && !feof($o_fp)) {
	print(@fread($o_fp, 1024*8));
	ob_flush();
	flush();
}
