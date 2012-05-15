/* 
	Date: 3 March 2011
	Migration: 35
	Description:
*/

/* -------------------------------------------------------------------------------- */
/* Improved data import logging */
/* -------------------------------------------------------------------------------- */

ALTER TABLE ca_locales ADD COLUMN  dont_use_for_cataloguing	tinyint unsigned not null;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (35, unix_timestamp());