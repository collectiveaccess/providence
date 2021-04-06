/*
	Date: 6 April 2021
	Migration: 169
	Description:    Extend maximum length of primary type label fields
*/

/*==========================================================================*/

ALTER TABLE ca_occurrence_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_occurrence_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_occurrence_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

DROP INDEX u_all on ca_collection_labels;
ALTER TABLE ca_collection_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_occurrence_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_collection_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;
create unique index u_all on ca_collection_labels
(
   collection_id,
   name(255),
   type_id,
   locale_id
);

ALTER TABLE ca_object_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_object_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_object_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

ALTER TABLE ca_loan_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_loan_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_loan_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

ALTER TABLE ca_movement_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_movement_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_movement_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

ALTER TABLE ca_object_representation_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_object_representation_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_object_representation_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

ALTER TABLE ca_tour_stop_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_tour_stop_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_tour_stop_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

ALTER TABLE ca_object_lot_labels MODIFY COLUMN name varchar(16384) NOT NULL;
UPDATE ca_object_lot_labels SET name_sort = substr(name_sort, 0, 255);
ALTER TABLE ca_object_lot_labels MODIFY COLUMN name_sort varchar(255) NOT NULL;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (169, unix_timestamp());
