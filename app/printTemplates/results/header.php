<?php	
/* ----------------------------------------------------------------------
 * app/templates/header.php : standard PDF report header
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Header
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */
 
	if($this->request->config->get('report_header_enabled')) {
		switch($this->getVar('PDFRenderer')) {
			case 'domPDF'
?>
<div id='header'>
<?php
	if(file_exists($this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img'))){
		print '<img src="'.$this->request->getThemeDirectoryPath().'/graphics/logos/'.$this->request->config->get('report_img').'" class="headerImg"/>';
	}
	print "<div class='pagingText'>"._t('Page')." </div>";
?>
</div>
<?php
			break;
		case 'PhantomJS':
?>
			<script type="text/javascript">
				// For PhantomJS
				PhantomJSPrinting['header'] = {
					height: "80px",
					contents: function(pageNum, numPages) { 
						return '<div style="position: absolute; width: 100%; height: 80px;"><img style="width:126px;height:30px;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMMAAAAtCAIAAACcU0gPAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBNYWNpbnRvc2giIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6ODg3MkFDRTQzQTdDMTFFMzgyMTBBNTgwQzA5RjJCMDMiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6ODg3MkFDRTUzQTdDMTFFMzgyMTBBNTgwQzA5RjJCMDMiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDowNUQ4NkQwNTNBNzYxMUUzODIxMEE1ODBDMDlGMkIwMyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDowNUQ4NkQwNjNBNzYxMUUzODIxMEE1ODBDMDlGMkIwMyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PpNFliAAABAoSURBVHja7JwHVFVX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"/>'
							   + '<div style="position: absolute; top: 0px; right: 10px; font: "helvetica, sans-serif"; font-size: 10px; text-align: right;  margin: 0px 0px 10px 25px;"><?php print _t('Page'); ?> ' + pageNum + '</div></div>';
			
					}
				};
			</script>
<?php	
			break;
			
		case 'wkhtmltopdf':
?>
<!--BEGIN HEADER--><!DOCTYPE html>
<html>
<head>
	<link type="text/css" href="<?php print $this->getVar('base_path'); ?>/pdf.css" rel="stylesheet" />
<?php
	if(file_exists($this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img'))){
		print '<img src="'.$this->request->getThemeDirectoryPath().'/graphics/logos/'.$this->request->config->get('report_img').'" class="headerImg"/>';
	}
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
	}
}