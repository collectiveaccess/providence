/* 
	Date: 8 February 2013
	Migration: 79
	Description:
*/


/* -------------------------------------------------------------------------------- */

/*==========================================================================*/
create table if not exists ca_data_importer_log
(
   log_id                         int unsigned                   not null AUTO_INCREMENT,
   importer_id                    int unsigned                   not null,
   user_id                        int unsigned,
   log_datetime                   int unsigned                   not null,
   notes                          text                           not null,
   table_num                      tinyint unsigned               not null,
   datafile                       longblob                       not null,
   primary key (log_id),
   
   index i_user_id (user_id),
   index i_importer_id (importer_id),
   index i_log_datetime (log_datetime),
   
   constraint fk_ca_data_importer_log_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_data_importers_log_importer_id foreign key (importer_id)
      references ca_data_importers (importer_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_data_importer_log_items
(
   log_item_id                   int unsigned                   not null AUTO_INCREMENT,
   log_id                        int unsigned                   not null,
   log_datetime                  int unsigned                   not null,
   table_num                     tinyint unsigned               not null,
   row_id                        int unsigned                   not null,
   type_code                     char(10)                       not null,
   notes                         text                           not null,
   primary key (log_item_id),
   
   index i_log_id (log_id),
   index i_row_id (row_id),
   index i_log_datetime (log_datetime),
   
   constraint fk_ca_data_importer_log_items_log_id foreign key (log_id)
      references ca_data_importer_log (log_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


ALTER TABLE ca_data_importers ADD COLUMN deleted tinyint unsigned not null;
ALTER TABLE ca_data_importers ADD COLUMN worksheet longblob not null;

ALTER TABLE ca_data_importer_items MODIFY COLUMN group_id int unsigned not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (79, unix_timestamp());
