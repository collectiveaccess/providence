/*
	Date: 6 February 2025
	Migration: 200
	Description: Add anonymous sharing links table
*/

/*==========================================================================*/

create table ca_sets_x_anonymous_access (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null,
	access tinyint unsigned not null default 0,
	guid varchar(100) not null,
	name varchar(255) not null,
	settings text not null,
	sdatetime int unsigned null,
	edatetime int unsigned null,
	
	primary key 				    (relation_id),
	index i_set_id				    (set_id),
	unique index u_guid   			(guid),
	unique index u_name				(set_id, name),
	
   constraint fk_ca_sets_x_anonymous_access_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (200, unix_timestamp());
