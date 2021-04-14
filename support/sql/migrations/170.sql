/*
	Date: 3 August 2020
	Migration: 170
	Description:    Add sortable field for attributes
*/
/*==========================================================================*/

alter table ca_attribute_values ADD COLUMN value_sortable varchar(100) null;
CREATE INDEX i_value_sortable ON ca_attribute_values(value_sortable);
CREATE INDEX i_sorting ON ca_attribute_values(element_id, attribute_id, value_sortable);

/* Objects */
drop index i_name on ca_object_labels;
drop index i_name_sort on ca_object_labels;
alter table ca_object_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_object_labels(name(255));
create index i_name_sort on ca_object_labels(name_sort);
create index i_key_name_sort on ca_object_labels(object_id, name_sort);

/* Object representations */
alter table ca_object_representation_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_object_representation_id on ca_object_representation_labels(representation_id);
create unique index u_all on ca_object_representation_labels(representation_id, name(255), type_id, locale_id);
create index i_locale_id on ca_object_representation_labels(locale_id);
create index i_type_id on ca_object_representation_labels(type_id);
create index i_name on ca_object_representation_labels(name(255));
create index i_name_sort on ca_object_representation_labels(name_sort);
create index i_key_name_sort on ca_object_representation_labels(representation_id, name_sort);

/* Occurrences */
drop index u_all on ca_occurrence_labels;
drop index i_name on ca_occurrence_labels;
drop index i_name_sort on ca_occurrence_labels;
alter table ca_occurrence_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_occurrence_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name_sort on ca_occurrence_labels(name_sort);
create index i_name on ca_occurrence_labels(name(255));
create index i_key_name_sort on ca_occurrence_labels(occurrence_id, name_sort);
create unique index u_all on ca_occurrence_labels(
   occurrence_id,
   name(255),
   type_id,
   locale_id
);

/* Collections */
drop index u_all on ca_collection_labels;
drop index i_name on ca_collection_labels;
drop index i_name_sort on ca_collection_labels;
alter table ca_collection_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_collection_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name_sort on ca_collection_labels(name_sort);
create index i_name on ca_collection_labels(name);
create index i_key_name_sort on ca_collection_labels(collection_id, name_sort);
create unique index u_all on ca_collection_labels
(
   collection_id,
   name(255),
   type_id,
   locale_id
);

/* Places */
drop index u_all on ca_place_labels;
drop index i_name on ca_place_labels;
drop index i_name_sort on ca_place_labels;
alter table ca_collection_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_place_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_place_labels(name(255));
create index i_name_sort on ca_place_labels(name_sort);
create index i_key_name_sort on ca_place_labels(place_id, name_sort);
create unique index u_all on ca_place_labels
(
   place_id,
   name(255),
   type_id,
   locale_id
);

/* Storage locations */
drop index u_all on ca_storage_location_labels;
drop index i_name on ca_storage_location_labels;
drop index i_name_sort on ca_storage_location_labels;
alter table ca_storage_location_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_storage_location_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_storage_location_labels(name(25));
create index i_name_sort on ca_storage_location_labels(name_sort);
create index i_key_name_sort on ca_storage_location_labels(location_id, name_sort);
create unique index u_all on ca_storage_location_labels
(
   location_id,
   name(255),
   locale_id,
   type_id
);

/* Loans */
alter table ca_loan_labels MODIFY COLUMN name_sort varchar(255) not null default '';
drop index i_name on ca_loan_labels;
create index i_name on ca_loan_labels(name(255));
drop index i_name_sort on ca_loan_labels;
create index i_name_sort on ca_loan_labels(name_sort);
create index i_key_name_sort on ca_loan_labels(loan_id, name_sort);
create unique index u_all on ca_loan_labels
(
   loan_id,
   name(255),
   locale_id,
   type_id
);

/* Movements */
alter table ca_movement_labels MODIFY COLUMN name_sort varchar(255) not null default '';
drop index i_name on ca_movement_labels;
drop index i_name_sort on ca_movement_labels;
create index i_name on ca_movement_labels(name(255));
create index i_name_sort on ca_movement_labels(name_sort);
create index i_key_name_sort on ca_movement_labels(movement_id, name_sort);
create unique index u_all on ca_movement_labels
(
   movement_id,
   name(255),
   locale_id,
   type_id
);

/* Representation annotation labels */
drop index u_all on ca_representation_annotation_labels;
drop index i_name on ca_representation_annotation_labels;
drop index i_name_sort on ca_representation_annotation_labels;
alter table ca_representation_annotation_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_representation_annotation_labels(name(255));
create index i_name_sort on ca_representation_annotation_labels(name_sort);
create index i_key_name_sort on ca_representation_annotation_labels(annotation_id, name_sort);
create unique index u_all on ca_representation_annotation_labels
(
   name(255),
   locale_id,
   type_id,
   annotation_id
);

/* Object lots */
drop index i_name on ca_object_lot_labels;
drop index i_name_sort on ca_object_lot_labels;
alter table ca_object_lot_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_object_lot_labels(name(255));
create index i_name_sort on ca_object_lot_labels(name_sort);
create index i_key_name_sort on ca_object_lot_labels(lot_id, name_sort);

/* List item labels */
alter table ca_list_item_labels MODIFY COLUMN name_sort varchar(255) not null default '';
DROP INDEX i_name ON ca_list_item_labels;
DROP INDEX i_name_sort ON ca_list_item_labels;
DROP INDEX i_name_singular ON ca_list_item_labels;
CREATE INDEX i_name_sort ON ca_list_item_labels(name_sort);
CREATE INDEX i_key_name_sort ON ca_list_item_labels(item_id, name_sort);
CREATE index i_name_singular ON ca_list_item_labels(item_id, name_singular);
CREATE index i_name ON ca_list_item_labels(item_id, name_plural);

/* Data importer labels */
alter table ca_data_exporter_labels MODIFY COLUMN name_sort varchar(255) not null default '';
#drop index i_name on ca_data_importer_labels;
drop index i_name_sort on ca_data_importer_labels;
create index i_name on ca_data_importer_labels(name);
create index i_name_sort on ca_data_importer_labels(name_sort);
create index i_key_name_sort on ca_data_importer_labels(importer_id, name_sort);

/* Data exporter labels */
alter table ca_data_exporter_labels MODIFY COLUMN name_sort varchar(255) not null default '';
#drop index i_name on ca_data_exporter_labels;
drop index i_name_sort on ca_data_exporter_labels;
create index i_name on ca_data_exporter_labels(name);
create index i_name_sort on ca_data_exporter_labels(name_sort);
create index i_key_name_sort on ca_data_exporter_labels(exporter_id, name_sort);

/* Entities */
alter table ca_entity_labels MODIFY COLUMN name_sort varchar(255) not null default '';
drop index i_forename on ca_entity_labels;
drop index i_surname on ca_entity_labels;
drop index i_name_sort on ca_entity_labels;
create index i_forename on ca_entity_labels(forename);
create index i_surname on ca_entity_labels(surname(255));
create index i_name_sort on ca_entity_labels(name_sort);
create index i_key_name_sort on ca_entity_labels(entity_id, name_sort);

/* Search form labels */
alter table ca_search_form_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_search_form_labels(name);
create index i_name_sort on ca_search_form_labels(name_sort);
create index i_key_name_sort on ca_search_form_labels(form_id, name_sort);

/* Bundle display labels */
alter table ca_bundle_display_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_bundle_display_labels(name);
create index i_name_sort on ca_bundle_display_labels(name_sort);
create index i_key_name_sort on ca_bundle_display_labels(display_id, name_sort);

/* Tours */
drop index i_name on ca_tour_labels;
drop index i_name_sort on ca_tour_labels;
alter table ca_tour_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_tour_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_tour_labels(name(255));
create index i_name_sort on ca_tour_labels(name_sort);
create index i_key_name_sort on ca_tour_labels(tour_id, name_sort);
create unique index u_all on ca_tour_labels
(
   name(255),
   locale_id,
   tour_id
);

/* Tour stops */
drop index i_name on ca_tour_stop_labels;
drop index i_name_sort on ca_tour_stop_labels;
alter table ca_tour_stop_labels MODIFY COLUMN name varchar(1024) not null default '';
alter table ca_tour_stop_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_tour_stop_labels(name(255));
create index i_name_sort on ca_tour_stop_labels(name_sort);
#create index i_key_name_sort on ca_tour_stop_labels(stop_id, name_sort);
create unique index u_all on ca_tour_stop_labels
(
   name(255),
   locale_id,
   stop_id
);

/* Metadata dictionary */
alter table ca_metadata_dictionary_entry_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_metadata_dictionary_entry_labels(name);
create index i_name_sort on ca_metadata_dictionary_entry_labels(name_sort);
create index i_key_name_sort on ca_metadata_dictionary_entry_labels(entry_id, name_sort);

/* User representation annotations */
drop index u_all on ca_user_representation_annotation_labels;
drop index i_name on ca_user_representation_annotation_labels;
drop index i_name_sort on ca_user_representation_annotation_labels;
alter table ca_user_representation_annotation_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_user_representation_annotation_labels(name(255));
create index i_name_sort on ca_user_representation_annotation_labels(name_sort);
create index i_key_name_sort on ca_user_representation_annotation_labels(annotation_id, name_sort);
create unique index u_all on ca_user_representation_annotation_labels
(
  name(255),
  locale_id,
  type_id,
  annotation_id
);

/* Metadata alert labels */
alter table ca_metadata_alert_rule_labels MODIFY COLUMN name_sort varchar(255) not null default '';
create index i_name on ca_metadata_alert_rule_labels(name);
create index i_name_sort on ca_metadata_alert_rule_labels(name_sort);
create index i_key_name_sort on ca_metadata_alert_rule_labels(rule_id, name_sort);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (170, unix_timestamp());