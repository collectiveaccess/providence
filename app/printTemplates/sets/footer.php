<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/sets/footer.php : standard PDF report footer
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
 * @name Footer
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */
$result 			= $this->getVar('result');
$num_items			= (int)$result->numHits();

$footer = '<table class="footerText" style="width: 100%;"><tr>';
if($this->getVar('param_showSearchTermInFooter')) {
	$footer .= "<td class='footerText'>".$this->getVar('criteria_summary_truncated')."</td>";
}

if($this->getVar('param_showResultCountInFooter')) {
	$footer .= "<td class='footerText'>".(($num_items == 1) ? _t('%1 item', $num_items) : _t('%1 items', $num_items))."</td>";
}

if($this->getVar('param_showTimestampInFooter')) {
	$footer .= "<td class='footerText'>".caGetLocalizedDate(null, ['dateFormat' => 'delimited'])."</td>";
}
$footer .= "</tr></table>";

switch($this->getVar('PDFRenderer')) {
	case 'domPDF':
?>
<div id='footer'>
	<?= $footer; ?>
</div>
<?php
		break;
	case 'wkhtmltopdf':
?>
<!--BEGIN FOOTER-->
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link type="text/css" href="<?= $this->getVar('base_path'); ?>/pdf.css" rel="stylesheet" />
<?php
	if(file_exists($this->getVar('base_path')."/local/pdf.css")){
?>
		<link type="text/css" href="<?= $this->getVar('base_path'); ?>/local/pdf.css" rel="stylesheet" />
<?php	
	} 
?>
	</head>
	<body>
		<table class="footerText"><tr>
			<?= $footer; ?>
		</tr></table>
	</body>
</html>
<!--END FOOTER-->

<?php
	break;
}
