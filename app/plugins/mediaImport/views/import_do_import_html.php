<?php
/* ----------------------------------------------------------------------
 * plugins/ltoimport/views/import_do_import_html.php : 
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
 
 	$vs_directory 		= $this->getVar('directory');
 	$va_errors	 		= $this->getVar('errors');
 	$va_report			= $this->getVar('report');
 ?>
<div class="sectionBox">
<?php
	if (sizeof($va_errors)) {
?>
	<h1><?php print _t('Media import request could not be submitted due to errors'); ?></h1>
	<h2><?php print _t("The following errors occurred"); ?>:
		</ul>
<?php
	foreach($va_errors as $vs_error) {
		print "			<li>{$vs_error}</li>\n";
	}
?>
		</ul>
	</h2>

<?php
	} else {
?>
	<h1><?php print _t('Media import request submitted'); ?></h1>
<?php
		if (is_array($va_report)) {
			print "<h2>"._t('Processing report')."</h2>\n";
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
?>
		<h2><?php print _t("Your request to import media from <em>%1</em> has been submitted and will be processed shortly. You will receive an email when the import is completed.", $vs_directory); ?></h2>
<?php
		}

	}
?>
</div>

<div class="editorBottomPadding"><!-- empty --></div>