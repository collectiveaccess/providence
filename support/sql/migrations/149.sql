/*
	Date: 17 October 2017
	Migration: 149
	Description: Add disposal date field for deaccessions
*/

 
ALTER TABLE ca_objects ADD COLUMN deaccession_disposal_sdatetime decimal(30,20) null;
ALTER TABLE ca_objects ADD COLUMN deaccession_disposal_edatetime decimal(30,20) null;
CREATE INDEX i_deaccession_disposal_sdatetime ON ca_objects(deaccession_disposal_sdatetime);
CREATE INDEX i_deaccession_disposal_edatetime ON ca_objects(deaccession_disposal_edatetime);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (149, unix_timestamp());