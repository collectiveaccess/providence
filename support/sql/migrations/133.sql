/*
	Date: 18 May 2016
	Migration: 133
	Description: Add circulation status field
*/

/*==========================================================================*/

ALTER TABLE ca_objects ADD  `circulation_status_id` INT UNSIGNED NULL;
ALTER TABLE ca_objects ADD FOREIGN KEY fk_ca_objects_circulation_status_id (circulation_status_id) REFERENCES ca_list_items (item_id) on delete restrict on update restrict;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (133, unix_timestamp());
