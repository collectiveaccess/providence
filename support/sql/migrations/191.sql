/*
	Date: 4 September 2023
	Migration: 191
	Description: Don't enforce uniqueness in database, to allow for reuse of identifiers
*/

/*==========================================================================*/

DROP INDEX u_set_code ON ca_sets;
CREATE INDEX i_set_code ON ca_sets(set_code); 

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (191, unix_timestamp());
