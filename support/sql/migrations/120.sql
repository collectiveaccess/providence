/*
	Date: 2 July 2015
	Migration: 120
	Description: Add search indexing queue table
*/

/*==========================================================================*/
create table ca_search_indexing_queue
(
  entry_id        int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  row_id          int unsigned      not null,
  field_data      LONGTEXT          not null default '',
  reindex         tinyint unsigned  not null default 0,
  changed_fields  TEXT              not null default '',
  options         LONGTEXT          not null default '',

  primary key (entry_id),
  index i_table_num_row_id (table_num, row_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (120, unix_timestamp());
