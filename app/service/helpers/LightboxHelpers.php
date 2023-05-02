<?php
/* ----------------------------------------------------------------------
 * app/service/helpers/LightboxHelpers.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
namespace GraphQLServices\Helpers\Lightbox;

/**
 *
 */
function getLightboxList(\ca_users $u) : ?array {
	// TODO: check access for user
	$t_sets = new \ca_sets();
	$lightboxes = $t_sets->getSetsForUser(["table" => 'ca_objects', "user_id" => $u->getPrimaryKey(), "checkAccess" => [0,1], "parents_only" => true]);
	
	return array_map(function($v) {
		return [
			'id' => $v['set_id'],
			'title' => $v['label'],
			'count' => $v['count'],
			'author_fname' => $v['fname'],
			'author_lname' => $v['lname'],
			'author_email' => $v['email'],
			'type' => $v['set_type'],
			'created' => date('c', $v['created']),
			'content_type' => \Datamodel::getTableName($v['table_num']),
			'content_type_singular' => \Datamodel::getTableProperty($v['table_num'], 'NAME_SINGULAR'),
			'content_type_plural' => \Datamodel::getTableProperty($v['table_num'], 'NAME_PLURAL'),
			
		];
	}, $lightboxes);	
}
