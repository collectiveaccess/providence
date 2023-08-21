/*
<<<<<<< HEAD
	Date: 14 June 2023
	Migration: 186
	Description:  Add sortable set_code field
*/
/*==========================================================================*/

ALTER TABLE ca_sets ADD COLUMN set_code_sort varchar(100) null;
CREATE INDEX i_set_code_sort ON ca_sets(set_code_sort);
=======
	Date: 11 August 2023
	Migration: 186
	Description: Resolve the differences between legacy data model and current data model
*/
>>>>>>> df7f034f8 (Add migration 186 to synchronize legacy and current data models)

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
<<<<<<< HEAD
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (186, unix_timestamp());
=======
INSERT IGNORE INTO ca_schema_updates (version_num, datetime)
 VALUES (186, unix_timestamp());

>>>>>>> df7f034f8 (Add migration 186 to synchronize legacy and current data models)
