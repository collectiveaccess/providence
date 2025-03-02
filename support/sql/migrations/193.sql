/*
	Date: 16 November 2023
	Migration: 193
	Description:    Ban whitelist table
*/

/*==========================================================================*/

create table if not exists ca_ip_whitelist (
   whitelist_id              int unsigned                   not null AUTO_INCREMENT,
   reason                    varchar(255)                   not null,
   created_on                int unsigned                   not null,
   expires_on                int unsigned                   null,
   
   ip_addr		             varchar(39)                    not null,
   
   primary key (whitelist_id),

   index i_created_on			    (created_on),
   index i_expires_on			    (expires_on),
   index i_ip_addr				    (ip_addr)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (193, unix_timestamp());
