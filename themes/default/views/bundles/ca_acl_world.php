<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_world.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
	$vs_id_prefix 		= $this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_user');				// user
	$t_subject 			= $this->getVar('t_subject');		
	$va_settings 		= $this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	$t_acl = new ca_acl();
	
	$vb_read_only		=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_users') == __CA_BUNDLE_ACCESS_READONLY__));
	
	$t_acl->set('access', (int)$this->getVar('initialValue'));
?>
<div id="<?php print $vs_id_prefix.'_world'; ?>">
	<div class="bundleContainer">
		<div class="caItemList">
			<div id="<?php print $vs_id_prefix; ?>_World" class="labelInfo">
				<table class="caListItem">
					<tr>
						<td class="formLabel">
							<?php print _t('Everyone'); ?>
							<?php print $t_acl->htmlFormElement('access', '^ELEMENT', array('name' => $vs_id_prefix.'_access_world', 'id' => $vs_id_prefix.'_access_world')); ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>