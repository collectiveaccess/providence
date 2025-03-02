/*
	Date: 15 April 2014
	Migration: 100
	Description: add fields location and deaccession tracking
*/

/* Add home location field for objects */
ALTER TABLE ca_objects ADD COLUMN home_location_id int unsigned null references ca_storage_locations(location_id);

/* Add deaccession fields for objects */
ALTER TABLE ca_objects ADD COLUMN accession_sdatetime decimal(30,20) null;
ALTER TABLE ca_objects ADD COLUMN accession_edatetime decimal(30,20) null;
ALTER TABLE ca_objects ADD COLUMN deaccession_sdatetime decimal(30,20) null;
ALTER TABLE ca_objects ADD COLUMN deaccession_edatetime decimal(30,20) null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (100, unix_timestamp());