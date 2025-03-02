/*
	Date: 21 April 2023
	Migration: 184
	Description:  Add return confirmation date field
*/
/*==========================================================================*/

ALTER TABLE ca_object_checkouts ADD COLUMN return_confirmation_date int unsigned null;
CREATE INDEX i_return_confirmation_date ON ca_object_checkouts(return_confirmation_date);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (184, unix_timestamp());
