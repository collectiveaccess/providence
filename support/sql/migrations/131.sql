/*
	Date: 15 January 2016
	Migration: 129
	Description: Add tables for data replication
*/

/*==========================================================================*/

create table ca_guids
(
  guid_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  row_id          int unsigned      not null,
  guid            VARCHAR(36)       not null,

  primary key (guid_id),
  index i_table_num_row_id (table_num, row_id),
  unique index u_guid (guid)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_replication_log
(
  entry_id        int unsigned      not null AUTO_INCREMENT,
  source_system_guid     VARCHAR(36)       not null,
  log_id          int unsigned      not null,
  status          char(1)           not null,
  vars            longtext          null,

  primary key (entry_id),
  index i_source_log (source_system_guid, log_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (131, unix_timestamp());
