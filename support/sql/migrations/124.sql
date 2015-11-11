/*
	Date: 8 November 2015
	Migration: 124
	Description: Add role-based access restriction for UI screens
*/

/*==========================================================================*/

create table ca_editor_uis_x_roles (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	role_id int unsigned not null references ca_user_roles(role_id),
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_role_id				(role_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens_x_roles (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	role_id int unsigned not null references ca_user_roles(role_id),
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_role_id				(role_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (124, unix_timestamp());
