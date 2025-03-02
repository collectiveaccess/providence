/*
	Date: 28 August 2021
	Migration: 172
	Description:    Reduce width of label fields to allow for update to UTF8mb4
*/

/*==========================================================================*/

ALTER TABLE ca_occurrence_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_collection_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_object_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_loan_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_movement_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_object_representation_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_tour_stop_labels MODIFY COLUMN name varchar(8192) NOT NULL;
ALTER TABLE ca_object_lot_labels MODIFY COLUMN name varchar(8192) NOT NULL;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (172, unix_timestamp());
