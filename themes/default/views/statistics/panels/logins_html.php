<?php
/* ----------------------------------------------------------------------
 * themes/default/statistics/panels/logins_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
	$totals = is_array($data['logins']) ? $data['logins'] : [];
?>
	<h3><?= _t('Logins'); ?></h3>
	
	<?php if (is_array($totals['most_recent'])) { ?>
	<div><?= _t("Most recent login at %1 by %2", $totals['most_recent']['last_login'], trim($totals['most_recent']['last_login_user_fname'].' '.$totals['most_recent']['last_login_user_lname'])." (".$totals['most_recent']['last_login_user_email'].")"); ?></div>
	<br/>
	<?php } ?>
<?php
	if(is_array($totals['counts'])) { 
		if(is_array($totals['counts']['by_class'])) { 
?>
		<div><?= _t("User accounts:"); ?></div>
		<ul>
	<?php
			foreach($totals['counts']['by_class'] as $class => $total) {
				print "<li>{$class}: {$total}</li>\n";
			}
	?>
		</ul>
<?php
		}
		
		if(is_array($totals['counts']['by_interval'])) { 
?>
		<div><?= _t("User logins:"); ?></div>
		<ul>
	<?php
			foreach($totals['counts']['by_interval'] as $interval => $total) {
				print "<li>{$interval}: {$total}</li>\n";
			}
	?>
		</ul>
<?php
		}
		
	}
