/*
	Date: 4 February 2021
	Migration: 167
	Description:    Add is_preferred field for metadata element labels
*/

/*==========================================================================*/

ALTER TABLE ca_metadata_element_labels ADD COLUMN is_preferred tinyint unsigned not null default 0;
UPDATE ca_metadata_element_labels SET is_preferred = 1;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (167, unix_timestamp());
