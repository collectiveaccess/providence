/*
	Date: 16 June 2020
	Migration: 164
	Description:    Add media upload manager session table
*/

/*==========================================================================*/

create table if not exists ca_media_upload_sessions (
   session_id                int unsigned                   not null AUTO_INCREMENT,
   user_id                   int unsigned                   not null references ca_users(user_id),
   session_key               char(36)                       not null,
   created_on                int unsigned                   not null,
   completed_on              int unsigned                   null,
   last_activity_on          int unsigned                   null,
   cancelled                 tinyint unsigned               not null default 0,
   
   num_files		         int unsigned                   not null,
   total_bytes		         int unsigned                   not null,
   progress		             longtext                       null,
   
   primary key (session_id),

   index i_session_id               (session_id),
   index i_created_on			    (created_on),
   index i_completed_on			    (completed_on),
   index i_last_activity_on			(last_activity_on),
   index i_cancelled      	        (cancelled),
   unique index i_session_key      	(session_key)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (164, unix_timestamp());
