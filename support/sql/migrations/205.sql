/*
	Date: 8 July 2025
	Migration: 205
	Description: Persistance of unsaved changes
*/

/*==========================================================================*/

create table ca_unsaved_edits
(
   edit_id                        int                         	 not null AUTO_INCREMENT,
   edit_datetime                  int unsigned                   not null,
   user_id                        int unsigned,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   snapshot                       longblob                       not null,
   
   primary key (edit_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_datetime on ca_unsaved_edits(edit_datetime);
create index i_user_id on ca_unsaved_edits(user_id);
create index i_edit on ca_unsaved_edits(row_id, table_num);
create index i_table_num on ca_unsaved_edits(table_num);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (205, unix_timestamp());
