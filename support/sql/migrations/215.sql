/*
	Date: 28 September 2026
	Migration: 215
	Description: 
*/

/*==========================================================================*/

create table ca_set_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   set_id                         int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                         smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_set_id				(set_id),
   index i_type_id				(type_id),
   constraint fk_ca_set_type_restrictions_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (215, unix_timestamp());
