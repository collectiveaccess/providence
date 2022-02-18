/*
	Date: 17 February 2022
	Migration: 177
	Description:  Add label qualifier
*/

/*==========================================================================*/

ALTER TABLE ca_object_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_object_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_object_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_object_labels(sdatetime, edatetime);
UPDATE ca_object_labels SET access = 1;

ALTER TABLE ca_entity_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_entity_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_entity_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_entity_labels(sdatetime, edatetime);
UPDATE ca_entity_labels SET access = 1;

ALTER TABLE ca_place_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_place_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_place_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_place_labels(sdatetime, edatetime);
UPDATE ca_place_labels SET access = 1;

ALTER TABLE ca_occurrence_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_occurrence_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_occurrence_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_occurrence_labels(sdatetime, edatetime);
UPDATE ca_occurrence_labels SET access = 1;

ALTER TABLE ca_object_lot_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_object_lot_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_object_lot_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_object_lot_labels(sdatetime, edatetime);
UPDATE ca_object_lot_labels SET access = 1;

ALTER TABLE ca_collection_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_collection_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_collection_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_collection_labels(sdatetime, edatetime);
UPDATE ca_collection_labels SET access = 1;

ALTER TABLE ca_object_representation_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_object_representation_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_object_representation_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_object_representation_labels(sdatetime, edatetime);
UPDATE ca_object_representation_labels SET access = 1;

ALTER TABLE ca_loan_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_loan_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_loan_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_loan_labels(sdatetime, edatetime);
UPDATE ca_loan_labels SET access = 1;

ALTER TABLE ca_movement_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_movement_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_movement_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_movement_labels(sdatetime, edatetime);
UPDATE ca_movement_labels SET access = 1;

ALTER TABLE ca_storage_location_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_storage_location_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_storage_location_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_storage_location_labels(sdatetime, edatetime);
UPDATE ca_storage_location_labels SET access = 1;

ALTER TABLE ca_list_item_labels ADD COLUMN sdatetime decimal(30,20) null;
ALTER TABLE ca_list_item_labels ADD COLUMN edatetime decimal(30,20) null;
ALTER TABLE ca_list_item_labels ADD COLUMN access tinyint unsigned not null default 0;
CREATE INDEX i_effective_date ON ca_list_item_labels(sdatetime, edatetime);
UPDATE ca_list_item_labels SET access = 1;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (177, unix_timestamp());
