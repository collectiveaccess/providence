/* 
	Date: 29 September 2010
	Migration: 26
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Support for "watch this record" functionality
*/

create table ca_watch_list
(
   watch_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   user_id                        int unsigned                   not null,
   primary key (watch_id),
   
   constraint fk_ca_watch_list_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_watch_list(row_id, table_num);
create index i_user_id on ca_watch_list(user_id);
create unique index u_all on ca_watch_list(row_id, table_num, user_id);

/* -------------------------------------------------------------------------------- */
/*
	Support for "user notes" functionality - "post-it" notes 
	attached to specific bundles in a record
*/

create table ca_user_notes
(
   note_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                     tinyint unsigned               not null,
   row_id                        int unsigned                   not null,
   user_id                       int unsigned                   not null,
   bundle_name                   varchar(255)                   not null,
   note                          longtext                       not null,
   created_on                    int unsigned                   not null,
   primary key (note_id),
   
   constraint fk_ca_user_notes_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_user_notes(row_id, table_num);
create index i_user_id on ca_user_notes(user_id);
create index i_bundle_name on ca_user_notes(bundle_name);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (26, unix_timestamp());
