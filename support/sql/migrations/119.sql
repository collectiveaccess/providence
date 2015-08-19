/*
	Date: 30 June 2015
	Migration: 119
	Description: Add tables for user-generated annotations
*/

/*==========================================================================*/
create table ca_user_representation_annotations
(
   annotation_id                  int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned,
   user_id                        int unsigned                   null,
   type_code                      varchar(30)                    not null,
   props                          longtext                       not null,
   preview                        longblob                       not null,
   source_info                    longtext                       not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   primary key (annotation_id),
   constraint fk_ca_urep_annot_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_urep_annot_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_urep_annot_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_user_representation_annotations(representation_id);
create index i_locale_id on ca_user_representation_annotations(locale_id);
create index i_user_id on ca_user_representation_annotations(user_id);


/*==========================================================================*/
create table ca_user_representation_annotation_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           text		                     not null,
   name_sort                      text                  		 not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   constraint fk_ca_user_representation_annotation_labels_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_ca_user_representation_annotation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_user_representation_annotation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_annotation_id on ca_user_representation_annotation_labels(annotation_id);
create index i_name on ca_user_representation_annotation_labels(name(128));
create unique index u_all on ca_user_representation_annotation_labels
(
   name(128),
   locale_id,
   type_id,
   annotation_id
);
create index i_locale_id on ca_user_representation_annotation_labels(locale_id);
create index i_name_sort on ca_user_representation_annotation_labels(name_sort(128));
create index i_type_id on ca_user_representation_annotation_labels(type_id);


/*==========================================================================*/
create table ca_user_representation_annotations_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),

   constraint fk_ca_urep_annot_x_entities_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_entities_label_left_id foreign key (label_left_id)
      references ca_user_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on ca_user_representation_annotations_x_entities(entity_id);
create index i_annotation_id on ca_user_representation_annotations_x_entities(annotation_id);
create index i_type_id on ca_user_representation_annotations_x_entities(type_id);
create unique index u_all on ca_user_representation_annotations_x_entities
(
   entity_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_user_representation_annotations_x_entities(label_left_id);
create index i_label_right_id on ca_user_representation_annotations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_user_representation_annotations_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   
   constraint fk_ca_urep_annot_x_objects_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_objects_label_left_id foreign key (label_left_id)
      references ca_user_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_user_representation_annotations_x_objects(object_id);
create index i_annotation_id on ca_user_representation_annotations_x_objects(annotation_id);
create index i_type_id on ca_user_representation_annotations_x_objects(type_id);
create unique index u_all on ca_user_representation_annotations_x_objects
(
   object_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_user_representation_annotations_x_objects(label_left_id);
create index i_label_right_id on ca_user_representation_annotations_x_objects(label_right_id);


/*==========================================================================*/
create table ca_user_representation_annotations_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   occurrence_id                  int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_urep_annot_x_occurrences_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_user_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_occurrence_id on ca_user_representation_annotations_x_occurrences(occurrence_id);
create index i_annotation_id on ca_user_representation_annotations_x_occurrences(annotation_id);
create index i_type_id on ca_user_representation_annotations_x_occurrences(type_id);
create unique index u_all on ca_user_representation_annotations_x_occurrences
(
   occurrence_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_user_representation_annotations_x_occurrences(label_left_id);
create index i_label_right_id on ca_user_representation_annotations_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_user_representation_annotations_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                        int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   
   constraint fk_ca_urep_annot_x_places_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_places_label_left_id foreign key (label_left_id)
      references ca_user_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_place_id on ca_user_representation_annotations_x_places(place_id);
create index i_annotation_id on ca_user_representation_annotations_x_places(annotation_id);
create index i_type_id on ca_user_representation_annotations_x_places(type_id);
create unique index u_all on ca_user_representation_annotations_x_places
(
   place_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_user_representation_annotations_x_places(label_left_id);
create index i_label_right_id on ca_user_representation_annotations_x_places(label_right_id);


/*==========================================================================*/
create table ca_user_representation_annotations_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   annotation_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   
   constraint fk_ca_urep_annot_x_vocabulary_terms_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_user_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_urep_annot_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on ca_user_representation_annotations_x_vocabulary_terms(item_id);
create index i_annotation_id on ca_user_representation_annotations_x_vocabulary_terms(annotation_id);
create index i_type_id on ca_user_representation_annotations_x_vocabulary_terms(type_id);
create unique index u_all on ca_user_representation_annotations_x_vocabulary_terms
(
   type_id,
   annotation_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_user_representation_annotations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_user_representation_annotations_x_vocabulary_terms(label_right_id);



/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (119, unix_timestamp());
