/*
	Date: 28 August 2023
	Migration: 190
	Description: Add table to support download of deferred reports
*/

/*==========================================================================*/

create table ca_user_export_downloads (
  download_id		    int unsigned        not null AUTO_INCREMENT,
  created_on        	int unsigned        not null,
  generated_on        	int unsigned        null,
  user_id             	int unsigned        null,
  download_type    		varchar(30)	   		not null,
  metadata				longtext			not null,
  status		 		varchar(30)    		not null default 'QUEUED',
  downloaded_on			int unsigned		null,
  error_code            smallint unsigned   not null default 0,
  export_file           longblob            not null,

  primary key (download_id),

  constraint fk_ca_export_download_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict,

  index i_user_id (user_id)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (190, unix_timestamp());
