/*
	Date: 6 July 2016
	Migration: 135
	Description: Add infrastructure for notifications
*/

/*==========================================================================*/
create table ca_notifications (
  notification_id     int unsigned        not null AUTO_INCREMENT,
  notification_type   tinyint unsigned    not null default 0,
  user_id             int unsigned,
  group_id            int unsigned,
  datetime            int unsigned        not null,
  table_num           tinyint unsigned,
  row_id              int unsigned,
  was_read            tinyint unsigned    not null default 0,
  message             longtext,

  primary key (notification_id),

  constraint fk_ca_users_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict,

  constraint fk_ca_user_groups_group_id foreign key (group_id)
    references ca_user_groups (group_id) on delete restrict on update restrict,

  index i_table_num_row_id (table_num, row_id),
  index i_datetime (datetime),
  index i_notification_type (notification_type)

) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (135, unix_timestamp());
