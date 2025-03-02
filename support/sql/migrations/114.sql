/*
	Date: 10 November 2014
	Migration: 114
	Description: Object check-in/check-out for local users
*/

create table ca_object_checkouts (
   checkout_id	            int unsigned					not null AUTO_INCREMENT,
   group_uuid               char(36) not null,
   object_id                int unsigned not null,
   user_id                	int unsigned not null,
   created_on				int unsigned not null,
   checkout_date			int unsigned null,
   due_date					int unsigned null,
   return_date				int unsigned null,
   checkout_notes			text not null,
   return_notes				text not null,
   deleted					tinyint unsigned not null,
   
   primary key (checkout_id),
   index i_group_uuid (group_uuid),
   index i_object_id (object_id),
   index i_user_id (user_id),
   index i_created_on (created_on),
   index i_checkout_date (checkout_date),
   index i_due_date (due_date),
   index i_return_date (return_date),
   
   constraint fk_ca_object_checkouts_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_checkouts_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (114, unix_timestamp());
