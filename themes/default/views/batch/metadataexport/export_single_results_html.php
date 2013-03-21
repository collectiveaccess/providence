<?php
/* ----------------------------------------------------------------------
 * batch/metadataexport/export_results_html.php :
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

$t_exporter = $this->getVar('t_exporter');
$vs_export = $this->getVar('export');
$vn_id = $this->getVar('item_id');

switch($t_exporter->getSetting('exporter_type')){
	case 'CSV':
		header('Content-Type: text/csv; charset=UTF-8');
		$vs_ext = ".csv";
		break;
	case 'MARC':
		switch($t_exporter->getSetting('MARC_outputFormat')){
			case 'readable':
				header('Content-Type: text/plain; charset=UTF-8');
				$vs_ext = ".txt";
			case 'raw':
				header('Content-Type: application/marc; charset=UTF-8');
				$vs_ext = ".mrc";
				break;
			case 'xml':
			default:
				header('Content-Type: text/xml; charset=UTF-8');
				$vs_ext = ".xml";
				break;	
		}
		break;
	case 'XML':
	default:
		header('Content-Type: text/xml; charset=UTF-8');
		$vs_ext = ".xml";
		break;
}

header('Content-Disposition: attachment; filename="'.$vn_id.$vs_ext.'"');
header('Content-Transfer-Encoding: binary');
print $vs_export;
exit();