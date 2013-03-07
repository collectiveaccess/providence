/* 
	Date: 3 March 2013
	Migration: 80
	Description:
*/


/* -------------------------------------------------------------------------------- */

/*==========================================================================*/
create table if not exists ca_data_import_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   occurred_on                    int unsigned                   not null,
   user_id                        int unsigned,
   description                    text                           not null,
   type_code                      char(10)                       not null,
   source                         text                           not null,
   primary key (event_id),
   
   index i_user_id(user_id),
   
   constraint fk_ca_data_import_events_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_data_import_items
(
   item_id                        int unsigned                  not null AUTO_INCREMENT,
   event_id                       int unsigned                  not null,
   source_ref                    varchar(255)                  not null,
   table_num                    tinyint unsigned            null,
   row_id                          int unsigned                  null,
   type_code                     char(1)                          null,
   started_on                    int unsigned                 not null,
   completed_on               int unsigned                 null,
   elapsed_time                decimal(8,4)                  null,
   success                        tinyint unsigned            null,
   message                       text                              not null,
   primary key (item_id),
   
   index i_event_id (event_id),
   index i_row_id (table_num, row_id),
   
   constraint fk_ca_data_import_items_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_data_import_event_log
(
   log_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                    int unsigned                   not null,
   item_id                      int unsigned                   null,
   type_code                  char(10)                       not null,
   date_time                  int unsigned                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   
   index i_event_id (event_id),
   index i_item_id (item_id),
   
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (80, unix_timestamp());
