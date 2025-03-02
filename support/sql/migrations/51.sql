/* 
	Date: 7 January 2011
	Migration: 51
	Description:
*/


ALTER TABLE ca_commerce_communications ADD COLUMN deleted tinyint not null;
ALTER TABLE ca_commerce_orders ADD COLUMN deleted tinyint not null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (51, unix_timestamp());