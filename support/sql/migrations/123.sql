/*
	Date: 1 November 2015
	Migration: 123
	Description: Add user and group access restriction for UI screens
*/

/*==========================================================================*/

create table ca_editor_ui_screens_x_user_groups (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens_x_users (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null references ca_editor_ui_screens(screen_id),
	user_id int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_user_id				(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (123, unix_timestamp());
