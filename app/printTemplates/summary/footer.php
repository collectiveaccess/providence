<?php
/* ----------------------------------------------------------------------
 * app/templates/footer.php : standard PDF report footer
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
 * @name Footer
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */
 	
 	$t_item = $this->getVar('t_subject');
	
	if($this->request->config->get('summary_header_enabled')) {
		$vs_footer = '<table class="footerText" style="width: 100%;"><tr>';
		if($this->request->config->get('summary_show_identifier')) {
			$vs_footer .= "<td class='footerText'  style='font-family: \"Sans Light\"; font-size: 12px; text-align: center;'>".$t_item->getLabelForDisplay()." (".$t_item->get($t_item->getProperty('ID_NUMBERING_ID_FIELD')).")</td>";
		}
	
		if($this->request->config->get('summary_show_timestamp')) {
			$vs_footer .= "<td class='footerText'  style='font-family: \"Sans Light\"; font-size: 12px; text-align: center;'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</td>";
		}
		$vs_footer .= "</tr></table>";
		
		
		switch($this->getVar('PDFRenderer')) {
			case 'domPDF':
?>
<div id='footer'>
<?php
	
	print $vs_footer;
?>
</div>
<?php
				break;
			
			case 'PhantomJS':
?>
			<script type="text/javascript">
				// For PhantomJS
				PhantomJSPrinting['footer'] = {
					height: "50mm",
					contents: function(pageNum, numPages) { 
						return '<div style="position: relative;width: 100%; height: 100px; text-align: center;"><?php print addslashes($vs_footer); ?></div>';	
					}
				};
			</script>
<?php
				break;
			case 'wkhtmltopdf':
?>
<!--BEGIN FOOTER-->
<!DOCTYPE html>
<html>
<head>
	<link type="text/css" href="<?php print $this->getVar('base_path'); ?>/pdf.css" rel="stylesheet" />
</head>
<body>
<?php
	print $vs_footer;
?>
</body>
</html>
<!--END FOOTER-->

<?php
			break;
		}
	}