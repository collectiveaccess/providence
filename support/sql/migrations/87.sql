/*
	Date: 24 June 2013
	Migration: 87
	Description: Use shorter indexes for compatibility with MySQL 5.6
*/

DROP INDEX i_name ON ca_list_labels;
CREATE INDEX i_name ON ca_list_labels(name(128));

drop index i_name_singular on ca_list_item_labels;
create index i_name_singular on ca_list_item_labels
(
	item_id,
	name_singular(128)
);

drop index i_name on ca_list_item_labels;
create index i_name on ca_list_item_labels
(
	item_id,
	name_plural(128)
);

DROP INDEX i_name_sort ON ca_list_item_labels;
CREATE INDEX i_name_sort ON ca_list_item_labels(name_sort(128));

DROP INDEX i_name ON ca_metadata_element_labels;
CREATE INDEX i_name ON ca_metadata_element_labels(name(128));

DROP INDEX i_original_filename ON ca_object_representations;
CREATE INDEX i_original_filename ON ca_object_representations(original_filename(128));

DROP INDEX i_name ON ca_occurrence_labels;
CREATE INDEX i_name ON ca_occurrence_labels(name(128));

DROP INDEX i_name ON ca_collection_labels;
CREATE INDEX i_name ON ca_collection_labels(name(128));

DROP INDEX i_name_sort ON ca_collection_labels;
CREATE INDEX i_name_sort ON ca_collection_labels(name_sort(128));

DROP INDEX i_name ON ca_place_labels;
DROP INDEX i_name_sort ON ca_place_labels;
CREATE INDEX i_name ON ca_place_labels(name(128));
CREATE INDEX i_name_sort ON ca_place_labels(name_sort(128));

DROP INDEX i_name ON ca_storage_location_labels;
CREATE INDEX i_name ON ca_storage_location_labels(name(128));

DROP INDEX i_name_sort ON ca_storage_location_labels;
CREATE INDEX i_name_sort ON ca_storage_location_labels(name_sort(128));

DROP INDEX i_name ON ca_loan_labels;
DROP INDEX i_name_sort ON ca_loan_labels;
CREATE INDEX i_name ON ca_loan_labels(name(128));
CREATE INDEX i_name_sort ON ca_loan_labels(name_sort(128));

DROP INDEX i_name ON ca_movement_labels;
DROP INDEX i_name_sort ON ca_movement_labels;
CREATE INDEX i_name ON ca_movement_labels(name(128));
CREATE INDEX i_name_sort ON ca_movement_labels(name_sort(128));

DROP INDEX i_name ON ca_representation_annotation_labels;
CREATE INDEX i_name ON ca_representation_annotation_labels(name(128));

DROP INDEX i_name_sort ON ca_representation_annotation_labels;
CREATE INDEX i_name_sort ON ca_representation_annotation_labels(name_sort(128));

DROP INDEX i_name ON ca_object_lot_event_labels;
CREATE INDEX i_name ON ca_object_lot_event_labels(name(128));

DROP INDEX i_name_sort ON ca_object_lot_event_labels;
CREATE INDEX i_name_sort ON ca_object_lot_event_labels(name_sort(128));

DROP INDEX i_name ON ca_object_lot_labels;
CREATE INDEX i_name ON ca_object_lot_labels(name(128));

DROP INDEX i_name_sort ON ca_object_lot_labels;
CREATE INDEX i_name_sort ON ca_object_lot_labels(name_sort(128));

DROP INDEX i_name ON ca_object_labels;
CREATE INDEX i_name ON ca_object_labels(name(128));

DROP INDEX i_name_sort ON ca_object_labels;
CREATE INDEX i_name_sort ON ca_object_labels(name_sort(128));

DROP INDEX i_name ON ca_object_event_labels;
CREATE INDEX i_name ON ca_object_event_labels(name(128));

DROP INDEX i_name_sort ON ca_object_event_labels;
CREATE INDEX i_name_sort ON ca_object_event_labels(name_sort(128));

DROP INDEX i_name_sort ON ca_data_importer_labels;
CREATE INDEX i_name_sort ON ca_data_importer_labels(name_sort(128));

DROP INDEX i_name_sort ON ca_data_exporter_labels;
CREATE INDEX i_name_sort ON ca_data_exporter_labels(name_sort(128));

DROP INDEX i_name_sort ON ca_entity_labels;
CREATE INDEX i_name_sort ON ca_entity_labels(name_sort(128));
 
drop index i_value_longtext1 on ca_attribute_values;
create index i_value_longtext1 on ca_attribute_values(value_longtext1(128));

drop index i_value_longtext2 on ca_attribute_values;
create index i_value_longtext2 on ca_attribute_values(value_longtext2(128));

DROP INDEX i_name ON ca_tour_labels;
DROP INDEX i_name_sort ON ca_tour_labels;
CREATE INDEX i_name ON ca_tour_labels(name(128));
CREATE INDEX i_name_sort ON ca_tour_labels(name_sort(128));

DROP INDEX i_name ON ca_tour_stop_labels;
DROP INDEX i_name_sort ON ca_tour_stop_labels;
CREATE INDEX i_name ON ca_tour_stop_labels(name(128));
CREATE INDEX i_name_sort ON ca_tour_stop_labels(name_sort(128));


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (87, unix_timestamp());