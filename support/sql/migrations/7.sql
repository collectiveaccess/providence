/* 
	Date: 19 October 2009
	Migration: 7
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Add missing field to ca_object_events
*/
ALTER TABLE ca_object_events ADD COLUMN object_id int unsigned not null references ca_objects(object_id);
CREATE INDEX i_object_id ON ca_object_events(object_id);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (7, unix_timestamp());
