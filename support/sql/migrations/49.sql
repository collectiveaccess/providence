/* 
	Date: 29 November 2011
	Migration: 49
	Description:
*/

/*==========================================================================*/
create table ca_storage_locations_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   location_left_id                 int unsigned               not null,
   location_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_storage_locations_x_storage_locations_location_left_id foreign key (location_left_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_storage_locations_location_right_id foreign key (location_right_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_location_left_id on ca_storage_locations_x_storage_locations(location_left_id);
create index i_location_right_id on ca_storage_locations_x_storage_locations(location_right_id);
create index i_type_id on ca_storage_locations_x_storage_locations(type_id);
create unique index u_all on ca_storage_locations_x_storage_locations
(
   location_left_id,
   location_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_storage_locations_x_storage_locations(label_left_id);
create index i_label_right_id on ca_storage_locations_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_occurrences_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned               not null,
   location_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_storage_locations_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_occurrences_x_storage_locations(occurrence_id);
create index i_location_id on ca_occurrences_x_storage_locations(location_id);
create index i_type_id on ca_occurrences_x_storage_locations(type_id);
create unique index u_all on ca_occurrences_x_storage_locations
(
   occurrence_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_occurrences_x_storage_locations(label_left_id);
create index i_label_right_id on ca_occurrences_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_places_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                  int unsigned               not null,
   location_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_storage_locations_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_places_x_storage_locations(place_id);
create index i_location_id on ca_places_x_storage_locations(location_id);
create index i_type_id on ca_places_x_storage_locations(type_id);
create unique index u_all on ca_places_x_storage_locations
(
   place_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_storage_locations(label_left_id);
create index i_label_right_id on ca_places_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_storage_locations_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   location_id                        int unsigned               not null,
   item_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_storage_locations_x_vocabulary_terms_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_location_id on ca_storage_locations_x_vocabulary_terms(location_id);
create index i_item_id on ca_storage_locations_x_vocabulary_terms(item_id);
create index i_type_id on ca_storage_locations_x_vocabulary_terms(type_id);
create unique index u_all on ca_storage_locations_x_vocabulary_terms
(
   location_id,
   item_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_storage_locations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_storage_locations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_loans_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   place_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_places_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_places_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_places(loan_id);
create index i_place_id on ca_loans_x_places(place_id);
create index i_type_id on ca_loans_x_places(type_id);
create unique index u_all on ca_loans_x_places
(
   loan_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_places(label_left_id);
create index i_label_right_id on ca_loans_x_places(label_right_id);


/*==========================================================================*/
create table ca_loans_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   occurrence_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_occurrences_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_occurrences(loan_id);
create index i_occurrence_id on ca_loans_x_occurrences(occurrence_id);
create index i_type_id on ca_loans_x_occurrences(type_id);
create unique index u_all on ca_loans_x_occurrences
(
   loan_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_occurrences(label_left_id);
create index i_label_right_id on ca_loans_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_loans_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   collection_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_collections_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_collections_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_collections(loan_id);
create index i_collection_id on ca_loans_x_collections(collection_id);
create index i_type_id on ca_loans_x_collections(type_id);
create unique index u_all on ca_loans_x_collections
(
   loan_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_collections(label_left_id);
create index i_label_right_id on ca_loans_x_collections(label_right_id);


/*==========================================================================*/
create table ca_loans_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   location_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_storage_locations_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_storage_locations(loan_id);
create index i_location_id on ca_loans_x_storage_locations(location_id);
create index i_type_id on ca_loans_x_storage_locations(type_id);
create unique index u_all on ca_loans_x_storage_locations
(
   loan_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_storage_locations(label_left_id);
create index i_label_right_id on ca_loans_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_loans_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   item_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_vocabulary_terms_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_vocabulary_terms(loan_id);
create index i_item_id on ca_loans_x_vocabulary_terms(item_id);
create index i_type_id on ca_loans_x_vocabulary_terms(type_id);
create unique index u_all on ca_loans_x_vocabulary_terms
(
   loan_id,
   item_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_loans_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_loans_x_object_lots
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned               not null,
   lot_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_object_lots_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_lots_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_lots_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_lots_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_lots_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_object_lots(loan_id);
create index i_lot_id on ca_loans_x_object_lots(lot_id);
create index i_type_id on ca_loans_x_object_lots(type_id);
create unique index u_all on ca_loans_x_object_lots
(
   loan_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_object_lots(label_left_id);
create index i_label_right_id on ca_loans_x_object_lots(label_right_id);


/*==========================================================================*/
create table ca_loans_x_loans
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_left_id                 int unsigned               not null,
   loan_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_loans_loan_left_id foreign key (loan_left_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_loans_loan_right_id foreign key (loan_right_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_loans_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_loans_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_loans_label_right_id foreign key (label_right_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_left_id on ca_loans_x_loans(loan_left_id);
create index i_loan_right_id on ca_loans_x_loans(loan_right_id);
create index i_type_id on ca_loans_x_loans(type_id);
create unique index u_all on ca_loans_x_loans
(
   loan_left_id,
   loan_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_loans(label_left_id);
create index i_label_right_id on ca_loans_x_loans(label_right_id);


/*==========================================================================*/
create table ca_movements_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                        int unsigned               not null,
   place_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_places_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_places_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_places(movement_id);
create index i_place_id on ca_movements_x_places(place_id);
create index i_type_id on ca_movements_x_places(type_id);
create unique index u_all on ca_movements_x_places
(
   movement_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_places(label_left_id);
create index i_label_right_id on ca_movements_x_places(label_right_id);


/*==========================================================================*/
create table ca_movements_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                        int unsigned               not null,
   occurrence_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_occurrences_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_occurrences(movement_id);
create index i_occurrence_id on ca_movements_x_occurrences(occurrence_id);
create index i_type_id on ca_movements_x_occurrences(type_id);
create unique index u_all on ca_movements_x_occurrences
(
   movement_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_occurrences(label_left_id);
create index i_label_right_id on ca_movements_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_movements_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                        int unsigned               not null,
   collection_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_collections_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_collections_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_collections(movement_id);
create index i_collection_id on ca_movements_x_collections(collection_id);
create index i_type_id on ca_movements_x_collections(type_id);
create unique index u_all on ca_movements_x_collections
(
   movement_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_collections(label_left_id);
create index i_label_right_id on ca_movements_x_collections(label_right_id);


/*==========================================================================*/
create table ca_movements_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                        int unsigned               not null,
   location_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_storage_locations_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_storage_locations(movement_id);
create index i_location_id on ca_movements_x_storage_locations(location_id);
create index i_type_id on ca_movements_x_storage_locations(type_id);
create unique index u_all on ca_movements_x_storage_locations
(
   movement_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_storage_locations(label_left_id);
create index i_label_right_id on ca_movements_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_movements_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_id                        int unsigned               not null,
   item_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_vocabulary_terms_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_id on ca_movements_x_vocabulary_terms(movement_id);
create index i_item_id on ca_movements_x_vocabulary_terms(item_id);
create index i_type_id on ca_movements_x_vocabulary_terms(type_id);
create unique index u_all on ca_movements_x_vocabulary_terms
(
   movement_id,
   item_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_movements_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_movements_x_movements
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   movement_left_id                 int unsigned               not null,
   movement_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_movements_movement_left_id foreign key (movement_left_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_movements_movement_right_id foreign key (movement_right_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_movements_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_movements_label_left_id foreign key (label_left_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_movements_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_movement_left_id on ca_movements_x_movements(movement_left_id);
create index i_movement_right_id on ca_movements_x_movements(movement_right_id);
create index i_type_id on ca_movements_x_movements(type_id);
create unique index u_all on ca_movements_x_movements
(
   movement_left_id,
   movement_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_movements(label_left_id);
create index i_label_right_id on ca_movements_x_movements(label_right_id);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (49, unix_timestamp());