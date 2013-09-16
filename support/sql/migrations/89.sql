/*
	Date: 20 July 2013
	Migration: 89
	Description: 
*/

drop table if exists ca_media_content_locations;
create table ca_media_content_locations
(
   table_num                      tinyint unsigned            not null,
   row_id                         int unsigned                not null,
   content                        text                        not null,
   loc                            longtext                    not null
) engine=myisam CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_media_content_locations(row_id, table_num);
create index i_content on ca_media_content_locations(content(255));
create fulltext index f_content on ca_media_content_locations(content);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (89, unix_timestamp());