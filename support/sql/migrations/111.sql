/*
	Date: 11 October 2014
	Migration: 111
	Description: change remaining myisam tables to innodb
*/

DROP INDEX i_row_id ON ca_media_content_locations;
DROP INDEX f_content ON ca_media_content_locations;
DROP INDEX i_content ON ca_media_content_locations;

/* ALTER TABLE ca_media_content_locations DROP page; */
ALTER TABLE ca_media_content_locations ENGINE = innodb;

create index i_row_id on ca_media_content_locations(row_id, table_num);
create index i_content on ca_media_content_locations(content(255));

DROP TABLE IF EXISTS ca_mysql_fulltext_search;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (111, unix_timestamp());
