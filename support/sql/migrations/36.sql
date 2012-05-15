/* 
	Date: 21 March 2011
	Migration: 36
	Description:
*/

/* -------------------------------------------------------------------------------- */
/* Split off oft-changing user variables from larger less-volatile ones to improve performance */
/* -------------------------------------------------------------------------------- */

ALTER TABLE ca_users ADD COLUMN  volatile_vars	text not null;

/*==========================================================================*/
/* Support for tour content
/*==========================================================================*/
create table ca_tours
(
   tour_id                       int unsigned                   not null AUTO_INCREMENT,
   tour_code                  varchar(100)                   not null,
   type_id                        int unsigned                   null,
   rank                           int unsigned              not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                        tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   user_id                        int unsigned                   null,
   primary key (tour_id),
   
   constraint fk_ca_tours_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_type_id on ca_tours(type_id);
create index i_user_id on ca_tours(user_id);
create index i_tour_code on ca_tours(tour_code);


/*==========================================================================*/
create table ca_tour_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   tour_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_labels_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_tour_id on ca_tour_labels(tour_id);
create index i_name on ca_tour_labels(name);
create unique index u_locale_id on ca_tour_labels(tour_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops
(
   stop_id                       int unsigned              not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   tour_id                        int unsigned              not null,
   type_id                        int unsigned                   null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   rank                           int unsigned              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_stop_id				int unsigned 				not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                         tinyint unsigned               not null,
   status                         tinyint unsigned               not null,
   deleted                        tinyint unsigned               not null,
   primary key (stop_id),
   
   constraint fk_ca_tour_stops_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_tour_id on ca_tour_stops(tour_id);
create index i_type_id on ca_tour_stops(type_id);
create index i_parent_id on ca_tour_stops(parent_id);
create index i_hier_stop_id on ca_tour_stops(hier_stop_id);
create index i_hier_left on ca_tour_stops(hier_left);
create index i_hier_right on ca_tour_stops(hier_right);
create index i_idno on ca_tour_stops(idno);
create index i_idno_sort on ca_tour_stops(idno_sort);


/*==========================================================================*/
create table ca_tour_stop_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   stop_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_stop_labels_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stop_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_stop_id on ca_tour_stop_labels(stop_id);
create index i_name on ca_tour_stop_labels(name);
create unique index u_locale_id on ca_tour_stop_labels(stop_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on  ca_tour_stops_x_objects(object_id);
create index i_stop_id on  ca_tour_stops_x_objects(stop_id);
create index i_type_id on  ca_tour_stops_x_objects(type_id);
create unique index u_all on  ca_tour_stops_x_objects
(
   object_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_objects(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_objects(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on  ca_tour_stops_x_entities(entity_id);
create index i_stop_id on  ca_tour_stops_x_entities(stop_id);
create index i_type_id on  ca_tour_stops_x_entities(type_id);
create unique index u_all on  ca_tour_stops_x_entities
(
   entity_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_entities(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_entities(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on  ca_tour_stops_x_places(place_id);
create index i_stop_id on  ca_tour_stops_x_places(stop_id);
create index i_type_id on  ca_tour_stops_x_places(type_id);
create unique index u_all on  ca_tour_stops_x_places
(
   place_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_places(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_places(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on  ca_tour_stops_x_occurrences(occurrence_id);
create index i_stop_id on  ca_tour_stops_x_occurrences(stop_id);
create index i_type_id on  ca_tour_stops_x_occurrences(type_id);
create unique index u_all on  ca_tour_stops_x_occurrences
(
   occurrence_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_occurrences(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_collection_id on  ca_tour_stops_x_collections(collection_id);
create index i_stop_id on  ca_tour_stops_x_collections(stop_id);
create index i_type_id on  ca_tour_stops_x_collections(type_id);
create unique index u_all on  ca_tour_stops_x_collections
(
   collection_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_collections(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_collections(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   item_id                      int unsigned               not null,
   stop_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on  ca_tour_stops_x_vocabulary_terms(item_id);
create index i_stop_id on  ca_tour_stops_x_vocabulary_terms(stop_id);
create index i_type_id on  ca_tour_stops_x_vocabulary_terms(type_id);
create unique index u_all on  ca_tour_stops_x_vocabulary_terms
(
   item_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_tour_stops_x_vocabulary_terms(label_left_id);
create index i_label_right_id on  ca_tour_stops_x_vocabulary_terms(label_right_id);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (36, unix_timestamp());