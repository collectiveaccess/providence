/* 
	Date: 23 November 2012
	Migration: 72
	Description:
*/

ALTER TABLE ca_commerce_orders ADD COLUMN sales_agent varchar(1024) not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (72, unix_timestamp());