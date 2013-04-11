<?php
/* ----------------------------------------------------------------------
 * bundles/ca_editor_ui_screen_type_restrictions.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
	$vs_id_prefix 			= $this->getVar('placement_code').$this->getVar('id_prefix');
 	$vs_element 			= $this->getVar('type_restrictions');
 	
 	$va_errors = array();
 	if(is_array($va_action_errors = $this->getVar('errors'))) {
 		foreach($va_action_errors as $o_error) {
 			$va_errors[] = $o_error->getErrorDescription();
 		}
 	}
?>
	<div>
		<div class="bundleContainer">
			<div class="caItemList">
				<div class="labelInfo">	
<?php
					if (is_array($va_errors) && sizeof($va_errors)) {
?>
						<span class="formLabelError"><?php print join('; ', $va_errors); ?></span>
<?php
					}
?>
					<?php print $vs_element; ?>
				</div>
			</div>
		</div>
	</div>