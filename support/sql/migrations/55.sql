/* 
	Date: 1 March 2012
	Migration: 55
	Description:
*/

/* Support for metadata type restrictions the cascade down the type hierarchy */
ALTER TABLE ca_metadata_type_restrictions ADD COLUMN include_subtypes tinyint unsigned not null default 0;

/* Support for user interface screen type restrictions the cascade down the type hierarchy */
ALTER TABLE ca_editor_ui_screen_type_restrictions ADD COLUMN include_subtypes tinyint unsigned not null default 0;


/* User interface type restrictions */
create table ca_editor_ui_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   ui_id                          int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_ui_id				(ui_id),
   index i_type_id				(type_id),
   constraint fk_ca_editor_ui_type_restrictions_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* User interface bundle placement type restrictions */
create table ca_editor_ui_bundle_placement_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   placement_id                   int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_placement_id			(placement_id),
   index i_type_id				(type_id),
   constraint fk_ca_editor_ui_bundle_placement_type_restrictions_placement_id foreign key (placement_id)
      references ca_editor_ui_bundle_placements (placement_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (55, unix_timestamp());