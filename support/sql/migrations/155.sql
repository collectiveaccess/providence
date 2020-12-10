/*
	Date: 6 September 2018
	Migration: 155
	Description: Allow tags to have arbitrary sort order
*/



ALTER TABLE ca_items_x_tags ADD COLUMN `rank` int unsigned not null default 0;
CREATE INDEX i_rank ON ca_items_x_tags(`rank`);
UPDATE ca_items_x_tags SET `rank` = relation_id;

create table if not exists ca_ip_bans (
   ban_id                    int unsigned                   not null AUTO_INCREMENT,
   reason                    varchar(255)                   not null,
   created_on                int unsigned                   not null,
   expires_on                int unsigned                   null,
   
   ip_addr		             varchar(39)                    not null,
   
   primary key (ban_id),

   index i_created_on			    (created_on),
   index i_expires_on			    (expires_on),
   index i_ip_addr				    (ip_addr)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (155, unix_timestamp());
