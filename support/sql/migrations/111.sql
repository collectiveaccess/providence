/*
	Date: 11 October 2014
	Migration: 111
	Description: change remaining myisam tables to innodb
*/

ALTER TABLE ca_media_content_locations DROP page;
ALTER TABLE ca_media_content_locations ENGINE = innodb;

DROP TABLE IF EXISTS ca_mysql_fulltext_search;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (111, unix_timestamp());
