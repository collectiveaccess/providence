<?php
/* ----------------------------------------------------------------------
 * plugins/ltoimport/views/email_report_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
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
	$va_report = $this->getVar('report');
	
	print "<h2>"._t('CollectiveAccess batch media import processing report')."</h2>\n";
	if (is_array($va_report)) {
		foreach($va_report as $vs_f => $va_report_info) {
			print "<h3>"._t('Media file: %1', $vs_f)."</h3>\n";
			print "<ul>\n";
			foreach($va_report_info['notes'] as $vs_note) {
				print "<li>{$vs_note}</li>\n";
			}
			foreach($va_report_info['errors'] as $vs_note) {
				print "<li>{$vs_note}</li>\n";
			}
			print "</ul>\n";
		}
	} else {
		print "<h3>"._t('No media was imported')."</h3>\n";	
	}
?>