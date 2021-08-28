/*
	Date: 28 August 2021
	Migration: 171
	Description:    Add per-file entries to media upload table structure
*/
/*==========================================================================*/


create table if not exists ca_media_upload_session_files (
   file_id                   int unsigned                   not null AUTO_INCREMENT,
   session_id                int unsigned                   not null references ca_media_upload_sessions(session_id),
   created_on                int unsigned                   not null,
   completed_on              int unsigned                   null,
   last_activity_on          int unsigned                   null,
   filename                  varchar(1024)                  not null,
   
   bytes_received		     bigint unsigned                not null default 0,
   total_bytes		         bigint unsigned                not null default 0,
   error_code                smallint unsigned              not null default 0,
   
   primary key (file_id),

   index i_session_id               (session_id),
   index i_created_on			    (created_on),
   index i_completed_on			    (completed_on),
   index i_last_activity_on			(last_activity_on),
   index i_error_code      	        (error_code)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

alter table ca_media_upload_sessions drop column progress;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (171, unix_timestamp());
