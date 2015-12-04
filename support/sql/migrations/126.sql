/*
	Date: 4 December 2015
	Migration: 126
	Description: Add GUID table
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

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (126, unix_timestamp());
