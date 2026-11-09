/*
	Date: 6 October 2026
	Migration: 216
	Description: Add new schema update tracking fields
*/
/*==========================================================================*/

ALTER TABLE ca_schema_updates ADD COLUMN schema_name char(20) not null default 'CORE';
ALTER TABLE ca_schema_updates ADD COLUMN modifier int not null default 0;
CREATE INDEX i_schema_name ON ca_schema_updates(schema_name);
DROP INDEX u_version_num ON ca_schema_updates;
UPDATE ca_schema_updates SET modifier = 0, schema_name = 'CORE';
CREATE UNIQUE INDEX u_version_num ON ca_schema_updates(schema_name, version_num, modifier);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, modifier, datetime, schema_name) VALUES (216, 0, unix_timestamp(), 'CORE');
