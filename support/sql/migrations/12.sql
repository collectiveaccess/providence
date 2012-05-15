/* 
	Date: 22 February 2010
	Migration: 12
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	add support for typed object representations
*/
ALTER TABLE ca_object_representations ADD COLUMN type_id int unsigned not null;
CREATE INDEX i_type_id ON ca_object_representations(type_id);

SELECT 'You must now run the "12_install_representation_types.php" script in the support/sql/migration_tools/ directory to complete this update. See http://wiki.collectiveaccess.org/index.php?title=Data_and_Database_Migrations for more information.' NOTICE;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (12, unix_timestamp());