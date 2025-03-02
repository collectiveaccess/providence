/* 
	Date: 21 October 2010
	Migration: 29
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Fix locale_id fields
*/

ALTER TABLE ca_loans MODIFY COLUMN locale_id smallint unsigned null;
ALTER TABLE ca_movements MODIFY COLUMN locale_id smallint unsigned null;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (29, unix_timestamp());
