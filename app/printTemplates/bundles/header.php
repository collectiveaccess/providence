<?php	
/* ----------------------------------------------------------------------
 * app/printTemplates/bundles/header.php : standard PDF report header
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Header
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */
 
switch($this->getVar('PDFRenderer')) {
	case 'domPDF':
?>
<div id='header'>
<?php
	print caGetReportLogo();
	print "<div class='pagingText'>"._t('Page')." </div>"; ?>
</div>
<?php
		break;			
	case 'wkhtmltopdf':
?>
<!--BEGIN HEADER--><!DOCTYPE html>
<html>
	<head>
	<link type="text/css" href="<?= $this->getVar('base_path'); ?>/pdf.css" rel="stylesheet" />
<?php
		print caGetReportLogo();
		print "<div class='pagingText' id='pagingText' style='position: absolute; top: 0px; right: 0px;'> </div>";
?>
	<script>
		function dynvar() {
			var vars = {};
			var x = document.location.search.substring(1).split('&');

			for (var i in x) {
				var z = x[i].split('=',2);

				if (!vars[z[0]]) {
					vars[z[0]] = unescape(z[1]);
				}
			}

			document.getElementById('pagingText').innerHTML = 'page ' + vars.page; // + ' of ' + vars.topage
		}
	
	</script>
	</head>
	<body onload='dynvar();'>
	</body>
</html>
<!--END HEADER-->
<?php
		break;
}
