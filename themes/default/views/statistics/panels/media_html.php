<?php
/* ----------------------------------------------------------------------
 * themes/default/statistics/panels/media_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2022 Whirl-i-Gig
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

	$data = $this->getVar('data');
	$totals = is_array($data['media']) ? $data['media'] : [];

?>
	<h3><?= _t('Media'); ?></h3>
	
	<div><?= _t("Size of media: %1", caHumanFilesize($totals['total_size'])); ?></div>
	<div><?= _t("Number of files: %1", $totals['file_count']); ?></div>
<?php
	if(is_array($totals['by_format'])) { 
?>
	<div><?= _t("File counts by format:"); ?></div>
	<ul>
<?php
		foreach($totals['by_format'] as $mimetype => $info) {
			if (!($typename = Media::getTypenameForMimetype($mimetype))) { $typename = _t('Unknown'); }
			print "<li>[{$typename}]: {$info['count']}<br/>("._t('%1 source; %2 total', caHumanFilesize($info['source_filesize'] ?? 0), caHumanFilesize($info['total_filesize'] ?? 0)).")</li>\n";
		}
?>
	</ul>
<?php
	}
?>
	<div><?= _t("File counts by representation access:"); ?></div>
	<ul>
<?php
		foreach($totals['by_status'] as $mimetype => $by_access) {
			foreach($by_access as $access => $info) {
				if (!($typename = Media::getTypenameForMimetype($mimetype))) { $typename = _t('Unknown'); }
				print "<li>[{$typename}] {$access}: {$info['count']} "._t('(%1)', caHumanFilesize($info['filesize'] ?? 0))."</li>\n";
			}
		}
?>
	</ul>
<?php
	foreach($totals as $k => $section) {
		if (preg_match("!^by_status_(ca_[a-z]+)$!", $k, $m)) {
			if(!($tn = Datamodel::getTableProperty($m[1], 'NAME_SINGULAR'))) { continue; }
?>	
			<div><?= _t("File counts by %1 access:", $tn); ?></div>
			<ul>
<?php
				foreach($totals['by_status'] as $mimetype => $by_access) {
					foreach($by_access as $access => $info) {
						if (!($typename = Media::getTypenameForMimetype($mimetype))) { $typename = _t('Unknown'); }
						print "<li>[{$typename}] {$access}: {$info['count']} "._t('(%1)', caHumanFilesize($info['filesize'] ?? 0))."</li>\n";
					}
				}
?>
			</ul>
<?php
		}
	}