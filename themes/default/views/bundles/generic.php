<?php
/* ----------------------------------------------------------------------
 * bundles/generic.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
	$id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_subject 		= $this->getVar('t_subject');
	
	$settings 		= $this->getVar('settings');
?>
<div id="<?= $id_prefix; ?>" class="generic">
	<div class="bundleContainer">
		<div class="genericEditorContent">
			<?= $t_subject->getWithTemplate($settings['displayTemplate']); ?>
		</div>
		<div style='clear:both;'></div>
	</div>
</div>