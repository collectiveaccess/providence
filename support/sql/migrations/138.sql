/*
	Date: 9 Aug 2016
	Migration: 138
	Description: Set defaults
*/

/*==========================================================================*/

ALTER TABLE ca_objects MODIFY COLUMN commenting_status tinyint unsigned not null default 0;
ALTER TABLE ca_objects MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_objects MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_objects MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_object_lots MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_object_lots MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_object_lots MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_object_lots MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_entities MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_entities MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_entities MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_entities MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_places MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_places MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_places MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_places MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_occurrences MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_occurrences MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_occurrences MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_occurrences MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_collections MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_collections MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_collections MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_collections MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_object_representations MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_object_representations MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations MODIFY COLUMN rating_status tinyint unsigned not null default 0;
ALTER TABLE ca_object_representations MODIFY COLUMN is_template tinyint unsigned not null default 0;

ALTER TABLE ca_sets MODIFY COLUMN commenting_status tinyint unsigned not null default 0;	
ALTER TABLE ca_sets MODIFY COLUMN tagging_status tinyint unsigned not null default 0;
ALTER TABLE ca_sets MODIFY COLUMN rating_status tinyint unsigned not null default 0;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (138, unix_timestamp());
