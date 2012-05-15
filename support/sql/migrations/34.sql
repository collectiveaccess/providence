/* 
	Date: 19 February 2011
	Migration: 34
	Description:
*/

/* -------------------------------------------------------------------------------- */
/* Improved data import logging */
/* -------------------------------------------------------------------------------- */
ALTER TABLE ca_data_import_items DROP COLUMN occurred_on;
ALTER TABLE ca_data_import_items MODIFY COLUMN table_num tinyint unsigned null;
ALTER TABLE ca_data_import_items MODIFY COLUMN row_id tinyint unsigned null;
ALTER TABLE ca_data_import_items MODIFY COLUMN type_code tinyint unsigned null;
ALTER TABLE ca_data_import_items ADD COLUMN source_ref int unsigned not null;
ALTER TABLE ca_data_import_items ADD COLUMN started_on int unsigned null;
ALTER TABLE ca_data_import_items ADD COLUMN completed_on int unsigned null;
ALTER TABLE ca_data_import_items ADD COLUMN elapsed_time decimal(8,4) null;
ALTER TABLE ca_data_import_items ADD COLUMN success tinyint unsigned null;
ALTER TABLE ca_data_import_items ADD COLUMN message text not null;

/* -------------------------------------------------------------------------------- */
/* Rank fields for objects and authorities */
/* -------------------------------------------------------------------------------- */
ALTER TABLE ca_objects ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_lots ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_entities ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_places ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_occurrences ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_collections ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_storage_locations ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_loans ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_movements ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_object_representations ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_sets ADD COLUMN rank int unsigned not null;
ALTER TABLE ca_list_items MODIFY COLUMN rank int unsigned not null;

/* -------------------------------------------------------------------------------- */
/* "Advanced" search form configuration */
/* -------------------------------------------------------------------------------- */
create table ca_search_form_placements (
	placement_id	int unsigned not null primary key auto_increment,
	form_id		int unsigned not null references ca_search_forms(form_id),
	
	bundle_name 	varchar(255) not null,
	rank			int unsigned not null,
	settings		longtext not null,
	
	KEY i_bundle_name (bundle_name),
	KEY i_rank (rank),
	KEY i_form_id (form_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */
/* User-based access control for displays, search forms and sets */
/* -------------------------------------------------------------------------------- */
create table ca_bundle_displays_x_users (
	relation_id 	int unsigned not null auto_increment,
	display_id 	int unsigned not null references ca_bundle_displays(display_id),
	user_id 		int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_search_forms_x_users (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null references ca_search_forms(form_id),
	user_id 		int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_form_id			(form_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_sets_x_users (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null references ca_sets(set_id),
	user_id int unsigned not null references ca_user(user_id),
	access tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_editor_uis_x_users (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null references ca_sets(set_id),
	user_id int unsigned not null references ca_user(user_id),
	access tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (34, unix_timestamp());