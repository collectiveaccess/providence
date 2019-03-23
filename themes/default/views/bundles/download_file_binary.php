<?php
/* ----------------------------------------------------------------------
 * app/views/bundles/download_file_binary.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2015 Whirl-i-Gig
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
 
	header("Content-type: application/octet-stream");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Cache-control: private");
	header("Content-Disposition: attachment; filename=".preg_replace('![^A-Za-z0-9\.\-]+!', '_', $this->getVar('archive_name')));
	
	set_time_limit(0);
	
	if ($o_zip = $this->getVar('zip_stream')) {
		$o_zip->stream();
		exit();
	} elseif(file_exists($vs_path = $this->getVar('archive_path'))) {
		$o_fp = @fopen($vs_path,"rb");
		while(is_resource($o_fp) && !feof($o_fp)) {
			print(@fread($o_fp, 1024*8));
			ob_flush();
			flush();
		}
		exit();
	} else {
		throw new ApplicationException(_t('File for download does not exist'));
	}
