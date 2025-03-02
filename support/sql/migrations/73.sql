/* 
	Date: 26 December 2012
	Migration: 73
	Description:
*/

/* --------------------------- Batch edit log --------------------------- */
create table ca_batch_log
(
   batch_id                       int unsigned              not null AUTO_INCREMENT,
   user_id                        int unsigned              not null,
   log_datetime                   int unsigned              not null,
   notes                          text                      not null,
   batch_type                     char(2)                   not null,
   table_num                      tinyint unsigned          not null,
   
   primary key (batch_id), 
   KEY i_log_datetime (log_datetime),
   KEY i_user_id (user_id),
   constraint fk_ca_batch_log_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_batch_log_items 
(
	batch_id                       int unsigned                   not null,
	log_id                         bigint                         not null,
	row_id                         int unsigned                   not null,
	
	primary key (batch_id, log_id, row_id), 
   	KEY i_log_id (log_id),
    KEY i_row_id (row_id),
    constraint fk_ca_batch_log_items_batch_id foreign key (batch_id)
      references ca_batch_log (batch_id) on delete restrict on update restrict,
    constraint fk_ca_change_log_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (73, unix_timestamp());