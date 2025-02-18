<?php
/* ----------------------------------------------------------------------
 * bundles/ca_acl_world.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2025 Whirl-i-Gig
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
$id_prefix 		= $this->getVar('id_prefix');
$t_instance 	= $this->getVar('t_instance');
$t_item 		= $this->getVar('t_user');	
$t_subject 		= $this->getVar('t_subject');		
$settings 		= $this->getVar('settings');
$add_label 		= $this->getVar('add_label');

$vb_read_only	= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_users') == __CA_BUNDLE_ACCESS_READONLY__));

$t_acl = new ca_acl();
$t_acl->set('access', (int)$this->getVar('initialValue'));

print $t_acl->htmlFormElement('access', '^ELEMENT', array('name' => $id_prefix.'_access_world', 'id' => $id_prefix.'_access_world'));