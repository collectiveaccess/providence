/*
	Date: 7 July 2016
	Migration: 136
	Description: More reasonable user/group notification model
*/

/*==========================================================================*/
ALTER TABLE ca_notifications DROP FOREIGN KEY fk_ca_users_user_id;
ALTER TABLE ca_notifications DROP FOREIGN KEY fk_ca_user_groups_group_id;


ALTER TABLE ca_notifications DROP COLUMN user_id;
ALTER TABLE ca_notifications DROP COLUMN group_id;

ALTER TABLE ca_notifications DROP COLUMN was_read;

ALTER TABLE ca_notifications DROP INDEX i_table_num_row_id;
ALTER TABLE ca_notifications DROP COLUMN table_num;
ALTER TABLE ca_notifications DROP COLUMN row_id;
/*==========================================================================*/
create table ca_notification_subjects (
  subject_id      int unsigned        not null auto_increment,
  notification_id int unsigned        not null references ca_notifications(notification_id),
  was_read        tinyint unsigned    not null default 0,
  table_num       tinyint unsigned    not null,
  row_id          int unsigned        not null,

  primary key (subject_id),
  index i_notification_id (notification_id),
  index i_table_num_row_id (table_num, row_id)

) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (136, unix_timestamp());
