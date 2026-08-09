/*
	Date: 22 April 2026
	Migration: 211
	Description: Support for multiple relationship type restriction definitions
*/

/*==========================================================================*/

create table ca_relationship_type_restrictions (
   restriction_id                 smallint unsigned              not null AUTO_INCREMENT,
   type_id                        smallint unsigned,
   sub_type_left_id               int unsigned                   null,
   include_subtypes_left          tinyint unsigned               not null default 0,
   sub_type_right_id              int unsigned                   null,
   include_subtypes_right         tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                         smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_type_id				(type_id),
   index i_sub_type_left_id		(sub_type_left_id),
   index i_sub_type_right_id	(sub_type_right_id),
   unique index u_all           (type_id, sub_type_left_id, sub_type_right_id),
   constraint fk_ca_relationship_type_restrictions_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (211, unix_timestamp());
