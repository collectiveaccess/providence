/*
	Date: 3 November 2013
	Migration: 94
	Description: Add ca_bundle_display_type_restrictions table
*/

create table ca_bundle_display_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   display_id                     int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_display_id			(display_id),
   index i_type_id				(type_id),
   constraint fk_ca_bundle_display_type_restrictions_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (94, unix_timestamp());