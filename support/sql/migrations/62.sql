/* 
	Date: 7 May 2012
	Migration: 62
	Description:
*/

ALTER TABLE ca_commerce_transactions ADD COLUMN deleted tinyint unsigned not null default 0;
ALTER TABLE ca_sets ADD COLUMN deleted tinyint unsigned not null default 0;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (62, unix_timestamp());