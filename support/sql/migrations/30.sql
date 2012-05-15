/* 
	Date: 28 October 2010
	Migration: 30
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Fix locale_id fields
*/

ALTER TABLE ca_attribute_values ADD COLUMN value_blob longblob null;

UPDATE ca_attribute_values SET value_blob = value_longtext1 WHERE element_id IN
(SELECT element_id FROM ca_metadata_elements WHERE datatype IN (15,16));



/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (30, unix_timestamp());
