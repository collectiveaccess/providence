/* 
	Date: 24 September 2010
	Migration: 25
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Remove deprecated browse cache tables
*/
DROP TABLE IF EXISTS ca_browse_results;
DROP TABLE IF EXISTS ca_browses;

/*
	Remove unused relationship table
*/
DROP TABLE ca_objects_x_object_events;

/*
	Add missing labels table
*/
create table ca_object_lot_event_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                       int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_object_lot_event_labels_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_name on ca_object_lot_event_labels(name);
create index i_event_id on ca_object_lot_event_labels(event_id);
create unique index u_all on ca_object_lot_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_lot_event_labels(name_sort);
create index i_type_id on ca_object_lot_event_labels(type_id);
create index i_locale_id on ca_object_lot_event_labels(locale_id);





/* -------------------------------------------------------------------------------- */
/*
	Collection - storage location relationship
*/

create table ca_collections_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_collections_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint ca_collections_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels(label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_id on ca_collections_x_storage_locations (collection_id);
create index i_location_id on ca_collections_x_storage_locations (location_id);
create index i_type_id on ca_collections_x_storage_locations (type_id);
create index i_label_left_id on ca_collections_x_storage_locations (label_left_id);
create index i_label_right_id on ca_collections_x_storage_locations (label_right_id);
create unique index u_all on ca_collections_x_storage_locations (
   collection_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);

/* -------------------------------------------------------------------------------- */
/*
	Support for specification of label to use when displaying relationship
*/
ALTER TABLE ca_object_representations_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_object_representation_labels(label_id);
ALTER TABLE ca_object_representations_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_object_representations_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_representations_x_occurrences(label_right_id);

ALTER TABLE ca_object_representations_x_places ADD COLUMN label_left_id int unsigned null references ca_object_representation_labels(label_id);
ALTER TABLE ca_object_representations_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_object_representations_x_places(label_left_id);
create index i_label_right_id on ca_object_representations_x_places(label_right_id);

ALTER TABLE ca_object_lot_events_x_storage_locations ADD COLUMN label_left_id int unsigned null references ca_object_lot_event_labels(label_id);
ALTER TABLE ca_object_lot_events_x_storage_locations ADD COLUMN label_right_id int unsigned null references ca_storage_location_labels(label_id);
create index i_label_left_id on ca_object_lot_events_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_lot_events_x_storage_locations(label_right_id);

ALTER TABLE ca_collections_x_collections ADD COLUMN label_left_id int unsigned null references ca_collection_labels(label_id);
ALTER TABLE ca_collections_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_collections_x_collections(label_left_id);
create index i_label_right_id on ca_collections_x_collections(label_right_id);

ALTER TABLE ca_objects_x_collections ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_objects_x_collections(label_left_id);
create index i_label_right_id on ca_objects_x_collections(label_right_id);

ALTER TABLE ca_objects_x_objects ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_objects ADD COLUMN label_right_id int unsigned null references ca_object_labels(label_id);
create index i_label_left_id on ca_objects_x_objects(label_left_id);
create index i_label_right_id on ca_objects_x_objects(label_right_id);

ALTER TABLE ca_objects_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_objects_x_occurrences(label_left_id);
create index i_label_right_id on ca_objects_x_occurrences(label_right_id);

ALTER TABLE ca_objects_x_places ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_objects_x_places(label_left_id);
create index i_label_right_id on ca_objects_x_places(label_right_id);

ALTER TABLE ca_object_events_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_object_event_labels(label_id);
ALTER TABLE ca_object_events_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_object_events_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_events_x_occurrences(label_right_id);

ALTER TABLE ca_object_events_x_places ADD COLUMN label_left_id int unsigned null references ca_object_event_labels(label_id);
ALTER TABLE ca_object_events_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_object_events_x_places(label_left_id);
create index i_label_right_id on ca_object_events_x_places(label_right_id);

ALTER TABLE ca_object_events_x_storage_locations ADD COLUMN label_left_id int unsigned null references ca_object_event_labels(label_id);
ALTER TABLE ca_object_events_x_storage_locations ADD COLUMN label_right_id int unsigned null references ca_storage_location_labels(label_id);
create index i_label_left_id on ca_object_events_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_events_x_storage_locations(label_right_id);

ALTER TABLE ca_object_lots_x_collections ADD COLUMN label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_object_lots_x_collections(label_left_id);
create index i_label_right_id on ca_object_lots_x_collections(label_right_id);

ALTER TABLE ca_object_lots_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_object_lots_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_lots_x_occurrences(label_right_id);

ALTER TABLE ca_occurrences_x_collections ADD COLUMN label_left_id int unsigned null references ca_occurrence_labels(label_id);
ALTER TABLE ca_occurrences_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_occurrences_x_collections(label_left_id);
create index i_label_right_id on ca_occurrences_x_collections(label_right_id);

ALTER TABLE ca_object_lots_x_places ADD COLUMN label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_object_lots_x_places(label_left_id);
create index i_label_right_id on ca_object_lots_x_places(label_right_id);

ALTER TABLE ca_occurrences_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_occurrence_labels(label_id);
ALTER TABLE ca_occurrences_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_occurrences_x_occurrences(label_left_id);
create index i_label_right_id on ca_occurrences_x_occurrences(label_right_id);

ALTER TABLE ca_entities_x_collections ADD COLUMN label_left_id int unsigned null references ca_entity_labels(label_id);
ALTER TABLE ca_entities_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_entities_x_collections(label_left_id);
create index i_label_right_id on ca_entities_x_collections(label_right_id);

ALTER TABLE ca_places_x_collections ADD COLUMN label_left_id int unsigned null references ca_place_labels(label_id);
ALTER TABLE ca_places_x_collections ADD COLUMN label_right_id int unsigned null references ca_collection_labels(label_id);
create index i_label_left_id on ca_places_x_collections(label_left_id);
create index i_label_right_id on ca_places_x_collections(label_right_id);

ALTER TABLE ca_places_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_place_labels(label_id);
ALTER TABLE ca_places_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_places_x_occurrences(label_left_id);
create index i_label_right_id on ca_places_x_occurrences(label_right_id);

ALTER TABLE ca_places_x_places ADD COLUMN label_left_id int unsigned null references ca_place_labels(label_id);
ALTER TABLE ca_places_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_places_x_places(label_left_id);
create index i_label_right_id on ca_places_x_places(label_right_id);

ALTER TABLE ca_entities_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_entity_labels(label_id);
ALTER TABLE ca_entities_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_entities_x_occurrences(label_left_id);
create index i_label_right_id on ca_entities_x_occurrences(label_right_id);

ALTER TABLE ca_entities_x_places ADD COLUMN label_left_id int unsigned null references ca_entity_labels(label_id);
ALTER TABLE ca_entities_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_entities_x_places(label_left_id);
create index i_label_right_id on ca_entities_x_places(label_right_id);

ALTER TABLE ca_object_representations_x_entities ADD COLUMN label_left_id int unsigned null references ca_object_representation_labels(label_id);
ALTER TABLE ca_object_representations_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_object_representations_x_entities(label_left_id);
create index i_label_right_id on ca_object_representations_x_entities(label_right_id);

ALTER TABLE ca_entities_x_entities ADD COLUMN label_left_id int unsigned null references ca_entity_labels(label_id);
ALTER TABLE ca_entities_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_entities_x_entities(label_left_id);
create index i_label_right_id on ca_entities_x_entities(label_right_id);

ALTER TABLE ca_representation_annotations_x_entities ADD COLUMN label_left_id int unsigned null references ca_representation_annotation_labels(label_id);
ALTER TABLE ca_representation_annotations_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_representation_annotations_x_entities(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_entities(label_right_id);

ALTER TABLE ca_representation_annotations_x_objects ADD COLUMN label_left_id int unsigned null references ca_representation_annotation_labels(label_id);
ALTER TABLE ca_representation_annotations_x_objects ADD COLUMN label_right_id int unsigned null references ca_object_labels(label_id);
create index i_label_left_id on ca_representation_annotations_x_objects(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_objects(label_right_id);

ALTER TABLE ca_representation_annotations_x_occurrences ADD COLUMN label_left_id int unsigned null references ca_representation_annotation_labels(label_id);
ALTER TABLE ca_representation_annotations_x_occurrences ADD COLUMN label_right_id int unsigned null references ca_occurrence_labels(label_id);
create index i_label_left_id on ca_representation_annotations_x_occurrences(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_occurrences(label_right_id);

ALTER TABLE ca_list_items_x_list_items ADD COLUMN label_left_id int unsigned null references ca_list_item_labels(label_id);
ALTER TABLE ca_list_items_x_list_items ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_list_items_x_list_items(label_left_id);
create index i_label_right_id on ca_list_items_x_list_items(label_right_id);

ALTER TABLE ca_objects_x_storage_locations ADD COLUMN  label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_storage_locations ADD COLUMN  label_right_id int unsigned null references ca_storage_location_labels(label_id);
create index i_label_left_id on ca_objects_x_storage_locations(label_left_id);
create index i_label_right_id on ca_objects_x_storage_locations(label_right_id);

ALTER TABLE ca_object_lots_x_storage_locations ADD COLUMN  label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_storage_locations ADD COLUMN  label_right_id int unsigned null references ca_storage_location_labels(label_id);
create index i_label_left_id on ca_object_lots_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_lots_x_storage_locations(label_right_id);

ALTER TABLE ca_object_lots_x_vocabulary_terms ADD COLUMN  label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_vocabulary_terms ADD COLUMN  label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_object_lots_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_lots_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_object_representations_x_vocabulary_terms ADD COLUMN  label_left_id int unsigned null references ca_object_representation_labels(label_id);
ALTER TABLE ca_object_representations_x_vocabulary_terms ADD COLUMN  label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_object_representations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_representations_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_object_events_x_vocabulary_terms ADD COLUMN  label_left_id int unsigned null references ca_object_event_labels(label_id);
ALTER TABLE ca_object_events_x_vocabulary_terms ADD COLUMN  label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_object_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_events_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_object_lot_events_x_vocabulary_terms ADD COLUMN  label_left_id int unsigned null references ca_object_lot_event_labels(label_id);
ALTER TABLE ca_object_lot_events_x_vocabulary_terms ADD COLUMN  label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_object_lot_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_lot_events_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_representation_annotations_x_places ADD COLUMN label_left_id int unsigned null references ca_representation_annotation_labels(label_id);
ALTER TABLE ca_representation_annotations_x_places ADD COLUMN label_right_id int unsigned null references ca_place_labels(label_id);
create index i_label_left_id on ca_representation_annotations_x_places(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_places(label_right_id);

ALTER TABLE ca_representation_annotations_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_representation_annotation_labels(label_id);
ALTER TABLE ca_representation_annotations_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_representation_annotations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_objects_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_objects_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_objects_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_object_lots_x_entities ADD COLUMN label_left_id int unsigned null references ca_object_lot_labels(label_id);
ALTER TABLE ca_object_lots_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_object_lots_x_entities(label_left_id);
create index i_label_right_id on ca_object_lots_x_entities(label_right_id);

ALTER TABLE ca_objects_x_entities ADD COLUMN label_left_id int unsigned null references ca_object_labels(label_id);
ALTER TABLE ca_objects_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_objects_x_entities(label_left_id);
create index i_label_right_id on ca_objects_x_entities(label_right_id);

ALTER TABLE ca_places_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_place_labels(label_id);
ALTER TABLE ca_places_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_places_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_places_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_occurrences_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_occurrence_labels(label_id);
ALTER TABLE ca_occurrences_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_occurrences_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_occurrences_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_object_events_x_entities ADD COLUMN label_left_id int unsigned null references ca_object_event_labels(label_id);
ALTER TABLE ca_object_events_x_entities ADD COLUMN label_right_id int unsigned null references ca_entity_labels(label_id);
create index i_label_left_id on ca_object_events_x_entities(label_left_id);
create index i_label_right_id on ca_object_events_x_entities(label_right_id);

ALTER TABLE ca_collections_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_collection_labels(label_id);
ALTER TABLE ca_collections_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_collections_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_collections_x_vocabulary_terms(label_right_id);

ALTER TABLE ca_entities_x_vocabulary_terms ADD COLUMN label_left_id int unsigned null references ca_entity_labels(label_id);
ALTER TABLE ca_entities_x_vocabulary_terms ADD COLUMN label_right_id int unsigned null references ca_list_item_labels(label_id);
create index i_label_left_id on ca_entities_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_entities_x_vocabulary_terms(label_right_id);

/* -------------------------------------------------------------------------------- */
/*
	Support for loans as first-class items
*/
create table ca_loans (
   loan_id                        int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned                   null,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   source_info                    longtext                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_loan_id                   int unsigned                   not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   primary key (loan_id),
   
   constraint fk_ca_loans_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_parent_id foreign key (parent_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_loans(parent_id);
create index i_type_id on ca_loans(type_id);
create index i_locale_id on ca_loans(locale_id);
create index idno on ca_loans(idno);
create index idno_sort on ca_loans(idno_sort);
create index hier_left on ca_loans(hier_left);
create index hier_right on ca_loans(hier_right);
create index hier_loan_id on ca_loans(hier_loan_id);

create table ca_loan_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   
   constraint fk_ca_loan_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loan_labels(loan_id);
create index i_locale_id_id on ca_loan_labels(locale_id);
create index i_type_id on ca_loan_labels(type_id);
create index i_name on ca_loan_labels(name);
create index i_name_sort on ca_loan_labels(name_sort);

create table ca_loans_x_objects (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   object_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_objects_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_objects (loan_id);
create index i_object_id on ca_loans_x_objects (object_id);
create index i_type_id on ca_loans_x_objects (type_id);
create index i_label_left_id on ca_loans_x_objects (label_left_id);
create index i_label_right_id on ca_loans_x_objects (label_right_id);
create unique index u_all on ca_loans_x_objects (
   loan_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);

create table ca_loans_x_entities (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   entity_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_entities_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_entities (loan_id);
create index i_entity_id on ca_loans_x_entities (entity_id);
create index i_type_id on ca_loans_x_entities (type_id);
create index i_label_left_id on ca_loans_x_entities (label_left_id);
create index i_label_right_id on ca_loans_x_entities (label_right_id);
create unique index u_all on ca_loans_x_entities (
   loan_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);

/* -------------------------------------------------------------------------------- */
/*
	Support for movement as first-class items
*/

create table ca_movements (
   movement_id                    int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    tinyint unsigned               not null,
   source_info                    longtext                       not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   primary key (movement_id),
   
    constraint fk_ca_movements_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
       constraint fk_ca_movements_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_movements(type_id);
create index i_locale_id on ca_movements(locale_id);
create index idno on ca_movements(idno);
create index idno_sort on ca_movements(idno_sort);


create table ca_movement_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   
   constraint fk_ca_movement_labels_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movement_labels(movement_id);
create index i_locale_id_id on ca_movement_labels(locale_id);
create index i_type_id on ca_movement_labels(type_id);
create index i_name on ca_movement_labels(name);
create index i_name_sort on ca_movement_labels(name_sort);


create table ca_movements_x_objects (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   object_id                      int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_objects_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_objects (movement_id);
create index i_object_id on ca_movements_x_objects (object_id);
create index i_type_id on ca_movements_x_objects (type_id);
create index i_label_left_id on ca_movements_x_objects (label_left_id);
create index i_label_right_id on ca_movements_x_objects (label_right_id);
create unique index u_all on ca_movements_x_objects (
   movement_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);

create table ca_movements_x_object_lots (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned               not null,
   type_id                        smallint unsigned              not null,
   lot_id                         int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_object_lots_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_lots_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_object_lots (movement_id);
create index i_lot_id on ca_movements_x_object_lots (lot_id);
create index i_type_id on ca_movements_x_object_lots (type_id);
create index i_label_left_id on ca_movements_x_object_lots (label_left_id);
create index i_label_right_id on ca_movements_x_object_lots (label_right_id);
create unique index u_all on ca_movements_x_object_lots (
   movement_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);

create table ca_movements_x_entities (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   entity_id                      int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_movements_x_entities_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_entities (movement_id);
create index i_entity_id on ca_movements_x_entities (entity_id);
create index i_type_id on ca_movements_x_entities (type_id);
create index i_label_left_id on ca_movements_x_entities (label_left_id);
create index i_label_right_id on ca_movements_x_entities (label_right_id);
create unique index u_all on ca_movements_x_entities (
   movement_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (25, unix_timestamp());
