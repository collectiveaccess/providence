/*
	Date: 27 February 2017
	Migration: 145
	Description: Add ca_search_form_type_restrictions table
*/

create table ca_search_form_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   form_id                        int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_form_id				(form_id),
   index i_type_id				(type_id),
   constraint fk_ca_search_form_type_restrictions_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (145, unix_timestamp());