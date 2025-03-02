/*
	Date: 27 December 2023
	Migration: 194
	Description: Add additional set item specifiers
*/

/*==========================================================================*/

ALTER TABLE ca_set_items ADD COLUMN representation_id int unsigned null references ca_object_representations(representation_id);
ALTER TABLE ca_set_items ADD COLUMN annotation_id int unsigned null references ca_representation_annotations(annotation_id);

CREATE INDEX i_row_key on ca_set_items(row_id, representation_id, annotation_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (194, unix_timestamp());
