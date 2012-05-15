/* 
	Date: 16 July 2010
	Migration: 17
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_browses MODIFY COLUMN params LONGBLOB not null;
ALTER TABLE ca_browses ADD COLUMN facets LONGBLOB not null;
TRUNCATE TABLE ca_browse_results;
TRUNCATE TABLE ca_browses;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (17, unix_timestamp());
