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
   constraint fk_ca_rep_annot_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_user_id foreign key (user_id)
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
   constraint fk_ca_representation_annotation_labels_annotation_id foreign key (annotation_id)
      references ca_user_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_annotation_id on ca_representation_annotation_labels(annotation_id);
create index i_name on ca_representation_annotation_labels(name(128));
create unique index u_all on ca_representation_annotation_labels
(
   name(128),
   locale_id,
   type_id,
   annotation_id
);
create index i_locale_id on ca_representation_annotation_labels(locale_id);
create index i_name_sort on ca_representation_annotation_labels(name_sort(128));
create index i_type_id on ca_representation_annotation_labels(type_id);



/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (119, unix_timestamp());
