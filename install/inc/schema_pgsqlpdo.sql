/*==========================================================================*/
create table ca_locales
(
   locale_id                      serial,
   name                           varchar(255)                   not null,
   language                       varchar(3)                     not null,
   country                        char(2)                        not null,
   dialect                        varchar(8),
   dont_use_for_cataloguing	      integer                        not null,
   primary key (locale_id)
);

create index u_language_country on ca_locales(language, country);


/*==========================================================================*/
create table ca_users
(
   user_id                        serial,
   user_name                      varchar(255)                   not null,
   userclass                      integer                        not null,
   password                       varchar(100)                   not null,
   fname                          varchar(255)                   not null,
   lname                          varchar(255)                   not null,
   email                          varchar(255)                   not null,
   vars                           text                           not null,
   volatile_vars                  text                           not null,
   active                         integer                        not null,
   confirmed_on                   integer,
   confirmation_key               char(32),
   primary key (user_id)
);

create unique index u_user_name_ca_users on ca_users(user_name);
create unique index u_confirmation_key_ca_users on ca_users(confirmation_key);
create index i_userclass_ca_users on ca_users(userclass);


/*==========================================================================*/
create table ca_application_vars
(
   vars                           text                           not null
);


/*==========================================================================*/
create table ca_change_log
(
   log_id                         serial,
   log_datetime                   integer                        not null,
   user_id                        integer,
   changetype                     char(1)                        not null,
   logged_table_num               integer                        not null,
   logged_row_id                  integer                        not null,
   rolledback                     integer              not null default 0,
   unit_id                        char(32),
   primary key (log_id)
);

create index i_datetime_ca_change_log on ca_change_log(log_datetime);
create index i_user_id_ca_change_log on ca_change_log(user_id);
create index i_logged_ca_change_log on ca_change_log(logged_row_id, logged_table_num);
create index i_unit_id_ca_change_log on ca_change_log(unit_id);
create index i_table_num_ca_change_log  on ca_change_log (logged_table_num);


/*==========================================================================*/
create table ca_change_log_snapshots (
	log_id                         bigint                        not null,
    snapshot                       bytea                         not null,
    
   constraint fk_ca_change_log_snaphots_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
);
create index i_log_id_ca_change_log_snapshots  on ca_change_log_snapshots (log_id);


/*==========================================================================*/
create table ca_change_log_subjects
(
   log_id                         bigint		                 not null,
   subject_table_num              integer                        not null,
   subject_row_id                 integer                        not null,
   
   constraint fk_ca_change_log_subjects_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
      
);

create index i_log_id_ca_change_log_subjects on ca_change_log_subjects(log_id);
create index i_subject_ca_change_log_subjects on ca_change_log_subjects(subject_row_id, subject_table_num);


/*==========================================================================*/
create table ca_eventlog
(
   date_time                      integer			not null,
   code                           CHAR(4)                        not null,
   message                        text                           not null,
   source                         varchar(255)                   not null
);

create index i_when_ca_eventlog on ca_eventlog(date_time);
create index i_source_ca_eventlog on ca_eventlog(source);


/*==========================================================================*/
create table ca_lists
(
   list_id                        serial,
   list_code                      varchar(100)                   not null,
   is_system_list                 integer                        not null,
   is_hierarchical                integer                        not null,
   use_as_vocabulary              integer                        not null,
   default_sort                   integer                        not null,
   primary key (list_id)
);

create unique index u_code_ca_lists on ca_lists(list_code);


/*==========================================================================*/
create table ca_list_labels
(
   label_id                       serial,
   list_id                        smallint                       not null,
   locale_id                      smallint                       not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_list_labels_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_list_id_ca_list_labels on ca_list_labels(list_id);
create index i_name_ca_list_labels on ca_list_labels(name);
create unique index u_locale_id_ca_list_labels on ca_list_labels(list_id, locale_id);


/*==========================================================================*/
create table ca_list_items
(
   item_id                        serial,
   parent_id                      integer,
   list_id                        smallint                       not null,
   type_id                        integer                            null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   item_value                     varchar(255)                   not null,
   rank                           integer                        not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   is_enabled                     integer                        not null,
   is_default                     integer                        not null,
   validation_format              varchar(255)                   not null,
   color                          char(6)                            null,
   icon                           bytea                          not null,
   access                         integer                        not null,
   status                         integer                        not null,
   deleted                        integer                        not null,
   primary key (item_id),
   
   constraint fk_ca_list_items_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_parent_id foreign key (parent_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
);

create index i_list_id_ca_list_items on ca_list_items(list_id);
create index i_parent_id_ca_list_items on ca_list_items(parent_id);
create index i_idno_ca_list_items on ca_list_items(idno);
create index i_idno_sort_ca_list_items on ca_list_items(idno_sort);
create index i_hier_left_ca_list_items on ca_list_items(hier_left);
create index i_hier_right_ca_list_items on ca_list_items(hier_right);
create index i_value_text_ca_list_items on ca_list_items(item_value);
create index i_type_id_ca_list_items on ca_list_items(type_id);


/*==========================================================================*/
create table ca_list_item_labels
(
   label_id                       serial,
   item_id                        integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name_singular                  varchar(255)                   not null,
   name_plural                    varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   description                    text                           not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_list_item_labels_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_name_singular_ca_list_item_labels on ca_list_item_labels
(
   item_id,
   name_singular
);
create index i_name_ca_list_item_labels on ca_list_item_labels
(
   item_id,
   name_plural
);
create index i_item_id_ca_list_item_labels on ca_list_item_labels(item_id);
create unique index u_all_ca_list_item_labels on ca_list_item_labels
(
   item_id,
   name_singular,
   name_plural,
   type_id,
   locale_id
);
create index i_name_sort_ca_list_item_labels on ca_list_item_labels(name_sort);
create index i_type_id_ca_list_item_labels on ca_list_item_labels(type_id);


/*==========================================================================*/
create table ca_metadata_elements
(
   element_id                     serial,
   parent_id                      smallint,
   list_id                        smallint,
   element_code                   varchar(30)                    not null,
   documentation_url              varchar(255)                   not null,
   datatype                       integer               not null,
   settings                       text                       not null,
   rank                           smallint              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_element_id                smallint              null,
   primary key (element_id),
   
   constraint fk_ca_metadata_elements_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_elements_parent_id foreign key (parent_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
);

create index i_hier_element_id_ca_metadata_elements on ca_metadata_elements(hier_element_id);
create unique index u_name_short_ca_metadata_elements on ca_metadata_elements(element_code);
create index i_parent_id_ca_metadata_elements on ca_metadata_elements(parent_id);
create index i_hier_left_ca_metadata_elements on ca_metadata_elements(hier_left);
create index i_hier_right_ca_metadata_elements on ca_metadata_elements(hier_right);
create index i_list_id_ca_metadata_elements on ca_metadata_elements(list_id);


/*==========================================================================*/
create table ca_metadata_element_labels
(
   label_id                       serial,
   element_id                     smallint              not null,
   locale_id                      smallint              not null,
   name                           varchar(255)                   not null,
   description                    text                           not null,
   primary key (label_id),
   
   constraint fk_ca_metadata_element_labels_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_element_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_element_id_ca_metadata_element_labels on ca_metadata_element_labels(element_id);
create index i_name_ca_metadata_element_labels on ca_metadata_element_labels(name);
create index i_locale_id_ca_metadata_element_labels on ca_metadata_element_labels(locale_id);


/*==========================================================================*/
create table ca_metadata_type_restrictions
(
   restriction_id                 serial,
   table_num                      integer               not null,
   type_id                        integer,
   element_id                     smallint              not null,
   settings                       text                       not null,
   rank                           smallint              not null,
   primary key (restriction_id),
   
   constraint fk_ca_metadata_type_restrictions_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
);

create index i_table_num_ca_metadata_type_restrictions on ca_metadata_type_restrictions(table_num);
create index i_type_id_ca_metadata_type_restrictions on ca_metadata_type_restrictions(type_id);
create index i_element_id_ca_metadata_type_restrictions on ca_metadata_type_restrictions(element_id);


/*==========================================================================*/
create table ca_multipart_idno_sequences
(
   idno_stub                      varchar(255)			not null,
   format                         varchar(100)                   not null,
   element                        varchar(100)                   not null,
   seq                            integer                   not null,
   primary key (idno_stub, format, element)
);


/*==========================================================================*/
create table ca_object_lots
(
   lot_id                         serial,
   type_id                        integer                   not null,
   lot_status_id                  integer                   not null,
   idno_stub                      varchar(255)                   not null,
   idno_stub_sort                 varchar(255)                   not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   extent                         smallint              not null,
   extent_units                   varchar(255)                   not null,
   access                         integer                        not null,
   status                         integer               not null,
   source_info                    text                       not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (lot_id),
   
   constraint fk_ca_object_lots_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_lot_status_id foreign key (lot_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
);

create index i_admin_idno_stub_ca_object_lots on ca_object_lots(idno_stub);
create index i_type_id_ca_object_lots on ca_object_lots(type_id);
create index i_admin_idno_stub_sort_ca_object_lots on ca_object_lots(idno_stub_sort);
create index i_lot_status_id_ca_object_lots on ca_object_lots(lot_status_id);


/*==========================================================================*/
create table ca_object_representations
(
   representation_id              serial,
   locale_id                      smallint,
   type_id                        integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   md5                            varchar(32)                    not null,
   original_filename              varchar(1024)                  not null,
   media                          bytea                       not null,
   media_metadata                 bytea                       not null,
   media_content                  text                       not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   access                         integer               not null,
   status                         integer               not null,
   rank                             integer                     not null,
   primary key (representation_id),
   constraint fk_ca_object_representations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_locale_id_ca_object_representations on ca_object_representations(locale_id);
create index i_type_id_ca_object_representations on ca_object_representations(type_id);
create index i_idno_ca_object_representations on ca_object_representations(idno);
create index i_idno_sort_ca_object_representations on ca_object_representations(idno_sort);
create index i_md5_ca_object_representations on ca_object_representations(md5);
create index i_original_filename_ca_object_representations on ca_object_representations(original_filename);


/*==========================================================================*/
create table ca_object_representation_labels
(
   label_id                       serial,
   representation_id              integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   
   constraint fk_ca_object_representation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
      
);


/*==========================================================================*/
create table ca_object_representation_multifiles (
	multifile_id		serial,
	representation_id	integer not null references ca_object_representations(representation_id),
	resource_path		text not null,
	media				bytea not null,
	media_metadata		bytea not null,
	media_content		text not null,
	rank				integer not null,	
	primary key (multifile_id)
);

/*create index i_resource_path_ca_object_representation_multifiles on ca_object_representation_multifiles(resource_path(255))SEMICOLON*/
create index i_representation_id_ca_object_representation_multifiles on ca_object_representation_multifiles(representation_id);


/*==========================================================================*/
create table ca_occurrences
(
   occurrence_id                  serial,
   parent_id                      integer,
   locale_id                      smallint,
   type_id                        integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   source_id                      integer,
   source_info                    text                       not null,
   hier_occurrence_id             integer                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (occurrence_id),
   
   constraint fk_ca_occurrences_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_parent_id foreign key (parent_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
      
);

create index i_parent_id_ca_occurrences on ca_occurrences(parent_id);
create index i_source_id_ca_occurrences on ca_occurrences(source_id);
create index i_type_id_ca_occurrences on ca_occurrences(type_id);
create index i_locale_id_ca_occurrences on ca_occurrences(locale_id);
create index i_hier_left_ca_occurrences on ca_occurrences(hier_left);
create index i_hier_right_ca_occurrences on ca_occurrences(hier_right);
create index i_hier_occurrence_id_ca_occurrences on ca_occurrences(hier_occurrence_id);


/*==========================================================================*/
create table ca_occurrence_labels
(
   label_id                       serial,
   occurrence_id                  integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                   not null,
   name_sort                      varchar(1024)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_occurrence_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_occurrence_labels on ca_occurrence_labels(occurrence_id);
create index i_name_ca_occurrence_labels on ca_occurrence_labels(name);
create unique index u_all_ca_occurrence_labels on ca_occurrence_labels(
   occurrence_id,
   name(255),
   type_id,
   locale_id
);
create index i_locale_id_ca_occurrence_labels on ca_occurrence_labels(locale_id);
/*create index i_name_sort_ca_occurrence_labels on ca_occurrence_labels(name_sort(255))SEMICOLON*/
create index i_type_id_ca_occurrence_labels on ca_occurrence_labels(type_id);


/*==========================================================================*/
create table ca_collections
(
   collection_id                  serial,
   parent_id                      integer,
   locale_id                      smallint,
   type_id                        integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   source_id                      integer,
   source_info                    text                       not null,
   hier_collection_id             integer                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (collection_id),
   constraint fk_ca_collections_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_parent_id foreign key (parent_id)
      references ca_collections (collection_id) on delete restrict on update restrict
);

create index i_parent_id_ca_collections on ca_collections(parent_id);
create index i_type_id_ca_collections on ca_collections(type_id);
create index i_idno_ca_collections on ca_collections(idno);
create index i_idno_sort_ca_collections on ca_collections(idno_sort);
create index i_locale_id_ca_collections on ca_collections(locale_id);
create index i_source_id_ca_collections on ca_collections(source_id);
create index i_hier_collection_id_ca_collections on ca_collections(hier_collection_id);
create index i_hier_left_ca_collections on ca_collections(hier_left);
create index i_hier_right_ca_collections on ca_collections(hier_right);


/*==========================================================================*/
create table ca_collection_labels
(
   label_id                       serial,
   collection_id                  integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_collection_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict
);

create index i_collection_id_ca_collection_labels on ca_collection_labels(collection_id);
create index i_name_ca_collection_labels on ca_collection_labels(name);
create unique index u_all_ca_collection_labels on ca_collection_labels
(
   collection_id,
   name,
   type_id,
   locale_id
);
create index i_locale_id_ca_collection_labels on ca_collection_labels(locale_id);
create index i_type_id_ca_collection_labels on ca_collection_labels(type_id);
create index i_name_sort_ca_collection_labels on ca_collection_labels(name_sort);


/*==========================================================================*/
create table ca_places
(
   place_id                       serial,
   parent_id                      integer,
   locale_id                      smallint,
   type_id                        integer                   null,
   source_id                      integer,
   hierarchy_id                   integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   source_info                    text                       not null,
   lifespan_sdate                 decimal(30,20),
   lifespan_edate                 decimal(30,20),
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   rank                             integer                     not null,
   primary key (place_id),
   constraint fk_ca_places_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_hierarchy_id foreign key (hierarchy_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_parent_id foreign key (parent_id)
      references ca_places (place_id) on delete restrict on update restrict
);

create index i_hierarchy_id_ca_places on ca_places(hierarchy_id);
create index i_type_id_ca_places on ca_places(type_id);
create index i_idno_ca_places on ca_places(idno);
create index i_idno_sort_ca_places on ca_places(idno_sort);
create index i_locale_id_ca_places on ca_places(locale_id);
create index i_source_id_ca_places on ca_places(source_id);
create index i_life_sdatetime_ca_places on ca_places(lifespan_sdate);
create index i_life_edatetime_ca_places on ca_places(lifespan_edate);
create index i_parent_id_ca_places on ca_places(parent_id);


/*==========================================================================*/
create table ca_place_labels
(
   label_id                       serial,
   place_id                       integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_place_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict
);

create index i_place_id_ca_place_labels on ca_place_labels(place_id);
create index i_name_ca_place_labels on ca_place_labels(name);
create index i_name_sort_ca_place_labels on ca_place_labels(name_sort);
create unique index u_all_ca_place_labels on ca_place_labels
(
   place_id,
   name,
   type_id,
   locale_id
);
create index i_locale_id_ca_place_labels on ca_place_labels(locale_id);
create index i_type_id_ca_place_labels on ca_place_labels(type_id);


/*==========================================================================*/
create table ca_storage_locations
(
   location_id                    serial,
   parent_id                      integer,
   type_id                        integer,
   is_template                    integer               not null,
   source_info                    text                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (location_id),
   constraint fk_ca_storage_locations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_parent_id foreign key (parent_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict
);

create index i_parent_id_ca_storage_locations on ca_storage_locations(parent_id);
create index i_type_id_ca_storage_locations on ca_storage_locations(type_id);
create index i_hier_left_ca_storage_locations on ca_storage_locations(hier_left);
create index i_hier_right_ca_storage_locations on ca_storage_locations(hier_right);


/*==========================================================================*/
create table ca_storage_location_labels
(
   label_id                       serial,
   location_id                    integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_storage_location_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict
);

create index i_name_ca_storage_location_labels on ca_storage_location_labels(name);
create index i_location_id_ca_storage_location_labels on ca_storage_location_labels(location_id);
create unique index u_all_ca_storage_location_labels on ca_storage_location_labels
(
   location_id,
   name,
   locale_id,
   type_id
);
create index i_locale_id_ca_storage_location_labels on ca_storage_location_labels(locale_id);
create index i_type_id_ca_storage_location_labels on ca_storage_location_labels(type_id);
create index i_name_sort_ca_storage_location_labels on ca_storage_location_labels(name_sort);


/*==========================================================================*/
create table ca_loans (
   loan_id                        serial,
   parent_id                      integer                   null,
   type_id                        integer                   not null,
   locale_id                      smallint              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   source_info                    text                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_loan_id                   integer                   not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (loan_id),
   
   constraint fk_ca_loans_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_parent_id foreign key (parent_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_parent_id_ca_loans on ca_loans(parent_id);
create index i_type_id_ca_loans on ca_loans(type_id);
create index i_locale_id_ca_loans on ca_loans(locale_id);
create index idno_ca_loans on ca_loans(idno);
create index idno_sort_ca_loans on ca_loans(idno_sort);
create index hier_left_ca_loans on ca_loans(hier_left);
create index hier_right_ca_loans on ca_loans(hier_right);
create index hier_loan_id_ca_loans on ca_loans(hier_loan_id);


/*==========================================================================*/
create table ca_loan_labels (
   label_id                       serial,
   loan_id                        integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   
   constraint fk_ca_loan_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict
      
);

create index i_loan_id_ca_loan_labels on ca_loan_labels(loan_id);
create index i_locale_id_id_ca_loan_labels on ca_loan_labels(locale_id);
create index i_type_id_ca_loan_labels on ca_loan_labels(type_id);
create index i_name_ca_loan_labels on ca_loan_labels(name);
create index i_name_sort_ca_loan_labels on ca_loan_labels(name_sort);


/*==========================================================================*/
create table ca_movements (
   movement_id                    serial,
   type_id                        integer                   not null,
   locale_id                      smallint              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   source_info                    text                       not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (movement_id),
   
    constraint fk_ca_movements_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
       constraint fk_ca_movements_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
);

create index i_type_id_ca_movements on ca_movements(type_id);
create index i_locale_id_ca_movements on ca_movements(locale_id);
create index idno_ca_movements on ca_movements(idno);
create index idno_sort_ca_movements on ca_movements(idno_sort);


/*==========================================================================*/
create table ca_movement_labels (
   label_id                       serial,
   movement_id                    integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   
   constraint fk_ca_movement_labels_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
);

create index i_movement_id_ca_movement_labels on ca_movement_labels(movement_id);
create index i_locale_id_id_ca_movement_labels on ca_movement_labels(locale_id);
create index i_type_id_ca_movement_labels on ca_movement_labels(type_id);
create index i_name_ca_movement_labels on ca_movement_labels(name);
create index i_name_sort_ca_movement_labels on ca_movement_labels(name_sort);


/*==========================================================================*/
create table ca_relationship_types
(
   type_id                        serial,
   parent_id                      smallint,
   sub_type_left_id               integer,
   sub_type_right_id              integer,
   hier_left                      decimal(30,20)        not null,
   hier_right                     decimal(30,20)        not null,
   hier_type_id                   smallint,
   table_num                      integer               not null,
   type_code                      varchar(30)                    not null,
   rank                           smallint              not null,
   is_default                     integer               not null,
   primary key (type_id),
      
   constraint fk_ca_relationship_types_parent_id foreign key (parent_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
);

create unique index u_type_code_ca_relationship_types on ca_relationship_types(type_code, table_num);
create index i_table_num_ca_relationship_types on ca_relationship_types(table_num);
create index i_sub_type_left_id_ca_relationship_types on ca_relationship_types(sub_type_left_id);
create index i_sub_type_right_id_ca_relationship_types on ca_relationship_types(sub_type_right_id);
create index i_parent_id_ca_relationship_types on ca_relationship_types(parent_id);
create index i_hier_type_id_ca_relationship_types on ca_relationship_types(hier_type_id);
create index i_hier_left_ca_relationship_types on ca_relationship_types(hier_left);
create index i_hier_right_ca_relationship_types on ca_relationship_types(hier_right);


/*==========================================================================*/
create table ca_relationship_type_labels
(
   label_id                       serial,
   type_id                        smallint              not null,
   locale_id                      smallint              not null,
   typename                       varchar(255)                   not null,
   typename_reverse               varchar(255)                   not null,
   description                    text                           not null,
   description_reverse            text                           not null,
   primary key (label_id),
   constraint fk_ca_relationship_type_labels_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_ca_relationship_type_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
);

create index i_type_id_ca_relationship_type_labels on ca_relationship_type_labels(type_id);
create index i_locale_id_ca_relationship_type_labels on ca_relationship_type_labels(locale_id);
create unique index u_typename_ca_relationship_type_labels on ca_relationship_type_labels
(
   type_id,
   locale_id,
   typename
);
create unique index u_typename_reverse_ca_relationship_type_labels on ca_relationship_type_labels
(
   typename_reverse,
   type_id,
   locale_id
);


/*==========================================================================*/
create table ca_object_representations_x_occurrences
(
   relation_id                    serial,
   representation_id              integer                   not null,
   occurrence_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_occurrences_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences(representation_id);
create index i_occurrence_id_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences(occurrence_id);
create index i_type_id_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences(type_id);
create unique index u_all_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences
(
   type_id,
   representation_id,
   occurrence_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences(label_left_id);
create index i_label_right_id_ca_object_representations_x_occurrences on ca_object_representations_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_object_representations_x_places
(
   relation_id                    serial,
   representation_id              integer                   not null,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_places_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_places on ca_object_representations_x_places(representation_id);
create index i_place_id_ca_object_representations_x_places on ca_object_representations_x_places(place_id);
create index i_type_id_ca_object_representations_x_places on ca_object_representations_x_places(type_id);
create unique index u_all_ca_object_representations_x_places on ca_object_representations_x_places
(
   type_id,
   representation_id,
   place_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_places on ca_object_representations_x_places(label_left_id);
create index i_label_right_id_ca_object_representations_x_places on ca_object_representations_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_collections
(
   relation_id                    serial,
   representation_id              integer                   not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_collections_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_collections on ca_object_representations_x_collections(representation_id);
create index i_collection_id_ca_object_representations_x_collections on ca_object_representations_x_collections(collection_id);
create index i_type_id_ca_object_representations_x_collections on ca_object_representations_x_collections(type_id);
create unique index u_all_ca_object_representations_x_collections on ca_object_representations_x_collections
(
   type_id,
   representation_id,
   collection_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_collections on ca_object_representations_x_collections(label_left_id);
create index i_label_right_id_ca_object_representations_x_collections on ca_object_representations_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_storage_locations
(
   relation_id                    serial,
   representation_id              integer                   not null,
   location_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_storage_loc_rep_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations(representation_id);
create index i_location_id_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations(location_id);
create index i_type_id_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations(type_id);
create unique index u_all_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations
(
   type_id,
   representation_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations(label_left_id);
create index i_label_right_id_ca_object_representations_x_storage_locations on ca_object_representations_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations
(
   annotation_id                  serial,
   representation_id              integer                   not null,
   locale_id                      smallint,
   user_id                        integer                   null,
   type_code                      varchar(30)                    not null,
   props                          text                       not null,
   preview                        text                       not null,
   source_info                    text                       not null,
   status                         integer               not null,
   access                         integer               not null,
   primary key (annotation_id),
   constraint fk_ca_rep_annot_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
);

create index i_representation_id_ca_representation_annotations on ca_representation_annotations(representation_id);
create index i_locale_id_ca_representation_annotations on ca_representation_annotations(locale_id);
create index i_user_id_ca_representation_annotations on ca_representation_annotations(user_id);


/*==========================================================================*/
create table ca_representation_annotation_labels
(
   label_id                       serial,
   annotation_id                  integer                   not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_representation_annotation_labels_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_annotation_id_ca_representation_annotation_labels on ca_representation_annotation_labels(annotation_id);
create index i_name_ca_representation_annotation_labels on ca_representation_annotation_labels(name);
create unique index u_all_ca_representation_annotation_labels on ca_representation_annotation_labels
(
   name,
   locale_id,
   type_id,
   annotation_id
);
create index i_locale_id_ca_representation_annotation_labels on ca_representation_annotation_labels(locale_id);
create index i_name_sort_ca_representation_annotation_labels on ca_representation_annotation_labels(name_sort);
create index i_type_id_ca_representation_annotation_labels on ca_representation_annotation_labels(type_id);


/*==========================================================================*/
create table ca_task_queue
(
   task_id                        serial,
   user_id                        integer,
   row_key                        CHAR(32),
   entity_key                     CHAR(32),
   created_on                     integer                   not null,
   started_on                   integer,
   completed_on                   integer,
   priority                       smallint              not null,
   handler                        varchar(20)                    not null,
   parameters                     text                           not null,
   notes                          text                           not null,
   error_code                     smallint              not null,
   primary key (task_id)
);

create index i_user_id_ca_task_queue on ca_task_queue(user_id);
create index i_started_on_ca_task_queue on ca_task_queue(started_on);
create index i_completed_on_ca_task_queue on ca_task_queue(completed_on);
create index i_entity_key_ca_task_queue on ca_task_queue(entity_key);
create index i_row_key_ca_task_queue on ca_task_queue(row_key);
create index i_error_code_ca_task_queue on ca_task_queue(error_code);


/*==========================================================================*/
create table ca_user_groups
(
   group_id                       serial,
   parent_id                      integer,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   user_id                        integer                   null references ca_users(user_id),
   rank                           smallint              not null,
   vars                           text                           not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   primary key (group_id),
      
   constraint fk_ca_user_groups_parent_id foreign key (parent_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
);

create index i_hier_left_ca_user_groups on ca_user_groups(hier_left);
create index i_hier_right_ca_user_groups on ca_user_groups(hier_right);
create index i_parent_id_ca_user_groups on ca_user_groups(parent_id);
create index i_user_id_ca_user_groups on ca_user_groups(user_id);
create unique index u_name_ca_user_groups on ca_user_groups(name);
create unique index u_code_ca_user_groups on ca_user_groups(code);


/*==========================================================================*/
create table ca_user_roles
(
   role_id                        serial,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   rank                           smallint              not null,
   vars                           text                           not null,
   field_access                   text                           not null,
   primary key (role_id)
);

create unique index u_name_ca_user_roles on ca_user_roles(name);
create unique index u_code_ca_user_roles on ca_user_roles(code);


/*==========================================================================*/
create table ca_object_lot_events
(
   event_id                       serial,
   lot_id                         integer                   not null,
   type_id                        integer                   not null,
   is_template                    integer               not null,
   planned_sdatetime              decimal(30,20)                 not null,
   planned_edatetime              decimal(30,20)                 not null,
   event_sdatetime                decimal(30,20),
   event_edatetime                decimal(30,20),
   primary key (event_id),
   constraint fk_ca_object_lot_events_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lot_events on ca_object_lot_events(lot_id);
create index i_type_id_ca_object_lot_events on ca_object_lot_events(type_id);
create index i_planned_sdatetime_ca_object_lot_events on ca_object_lot_events(planned_sdatetime);
create index i_planned_edatetime_ca_object_lot_events on ca_object_lot_events(planned_edatetime);
create index i_event_sdatetime_ca_object_lot_events on ca_object_lot_events(event_sdatetime);
create index i_event_edatetime_ca_object_lot_events on ca_object_lot_events(event_edatetime);


/*==========================================================================*/
create table ca_object_lot_event_labels
(
   label_id                       serial,
   event_id                       integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_object_lot_event_labels_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_event_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_name_ca_object_lot_event_labels on ca_object_lot_event_labels(name);
create index i_event_id_ca_object_lot_event_labels on ca_object_lot_event_labels(event_id);
create unique index u_all_ca_object_lot_event_labels on ca_object_lot_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort_ca_object_lot_event_labels on ca_object_lot_event_labels(name_sort);
create index i_type_id_ca_object_lot_event_labels on ca_object_lot_event_labels(type_id);
create index i_locale_id_ca_object_lot_event_labels on ca_object_lot_event_labels(locale_id);




/*==========================================================================*/
create table ca_object_lot_events_x_storage_locations
(
   relation_id                    serial,
   event_id                       integer                   not null,
   location_id                    integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lot_events_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_lot_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations(event_id);
create index i_location_id_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations(location_id);
create index i_type_id_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations(type_id);
create unique index u_all_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations
(
   type_id,
   event_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations(label_left_id);
create index i_label_right_id_ca_object_lot_events_x_storage_locations on ca_object_lot_events_x_storage_locations(label_right_id);

/*==========================================================================*/
create table ca_object_lot_labels
(
   label_id                       serial,
   lot_id                         integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_object_lot_labels_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_name_ca_object_lot_labels on ca_object_lot_labels(name);
create index i_lot_id_ca_object_lot_labels on ca_object_lot_labels(lot_id);
create unique index u_all_ca_object_lot_labels on ca_object_lot_labels
(
   lot_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort_ca_object_lot_labels on ca_object_lot_labels(name_sort);
create index i_type_id_ca_object_lot_labels on ca_object_lot_labels(type_id);
create index i_locale_id_ca_object_lot_labels on ca_object_lot_labels(locale_id);


/*==========================================================================*/
create table ca_collections_x_collections
(
   relation_id                    serial,
   collection_left_id             integer                   not null,
   collection_right_id            integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_collections_x_collections_collection_left_id foreign key (collection_left_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_collection_right_id foreign key (collection_right_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_collection_left_id_ca_collections_x_collections on ca_collections_x_collections(collection_left_id);
create index i_collection_right_id_ca_collections_x_collections on ca_collections_x_collections(collection_right_id);
create index i_type_id_ca_collections_x_collections on ca_collections_x_collections(type_id);
create unique index u_all_ca_collections_x_collections on ca_collections_x_collections
(
   collection_left_id,
   collection_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_collections_x_collections on ca_collections_x_collections(label_left_id);
create index i_label_right_id_ca_collections_x_collections on ca_collections_x_collections(label_right_id);


/*==========================================================================*/
create table ca_collections_x_storage_locations (
   relation_id                    serial,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   location_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      references ca_storage_location_labels(label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
      
);

create index i_collection_id_ca_collections_x_storage_locations  on ca_collections_x_storage_locations (collection_id);
create index i_location_id_ca_collections_x_storage_locations  on ca_collections_x_storage_locations (location_id);
create index i_type_id_ca_collections_x_storage_locations  on ca_collections_x_storage_locations (type_id);
create unique index u_all_ca_collections_x_storage_locations  on ca_collections_x_storage_locations (
   collection_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_collections_x_storage_locations on ca_collections_x_storage_locations(label_left_id);
create index i_label_right_id_ca_collections_x_storage_locations on ca_collections_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_objects
(
   object_id                      serial,
   parent_id                      integer,
   lot_id                         integer,
   locale_id                      smallint,
   source_id                      integer,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   type_id                        integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   acquisition_type_id            integer,
   item_status_id                 integer,
   source_info                    text                       not null,
   hier_object_id                 integer                   not null,
   hier_left                      decimal(30,20)        not null,
   hier_right                     decimal(30,20)        not null,
   extent                         integer                   not null,
   extent_units                   varchar(255)                   not null,
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (object_id),
   constraint fk_ca_objects_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_acquisition_type_id foreign key (acquisition_type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_item_status_id foreign key (item_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_parent_id foreign key (parent_id)
      references ca_objects (object_id) on delete restrict on update restrict
);

create index i_parent_id_ca_objects on ca_objects(parent_id);
create index i_idno_ca_objects on ca_objects(idno);
create index i_idno_sort_ca_objects on ca_objects(idno_sort);
create index i_type_id_ca_objects on ca_objects(type_id);
create index i_hier_left_ca_objects on ca_objects(hier_left);
create index i_hier_right_ca_objects on ca_objects(hier_right);
create index i_lot_id_ca_objects on ca_objects(lot_id);
create index i_locale_id_ca_objects on ca_objects(locale_id);
create index i_hier_object_id_ca_objects on ca_objects(hier_object_id);
create index i_acqusition_type_id_ca_objects on ca_objects
(
   source_id,
   acquisition_type_id
);
create index i_source_id_ca_objects on ca_objects(source_id);
create index i_item_status_id_ca_objects on ca_objects(item_status_id);


/*==========================================================================*/
create table ca_object_labels
(
   label_id                       serial,
   object_id                      integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_object_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict
);

create index i_name_ca_object_labels on ca_object_labels(name);
create index i_object_id_ca_object_labels on ca_object_labels(object_id);
create unique index u_all_ca_object_labels on ca_object_labels
(
   object_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort_ca_object_labels on ca_object_labels(name_sort);
create index i_type_id_ca_object_labels on ca_object_labels(type_id);
create index i_locale_id_ca_object_labels on ca_object_labels(locale_id);


/*==========================================================================*/
create table ca_object_events
(
   event_id                       serial,
   type_id                        integer                   not null,
   object_id                      integer                   not null,
   is_template                    integer               not null,
   planned_sdatetime              decimal(30,20)                 not null,
   planned_edatetime              decimal(30,20)                 not null,
   event_sdatetime                decimal(30,20),
   event_edatetime                decimal(30,20),
   primary key (event_id),
   
   constraint fk_ca_object_events_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_object_id_ca_object_events on ca_object_events(object_id);
create index i_type_id_ca_object_events on ca_object_events(type_id);
create index i_planned_sdatetime_ca_object_events on ca_object_events(planned_sdatetime);
create index i_planned_edatetime_ca_object_events on ca_object_events(planned_edatetime);
create index i_event_sdatetime_ca_object_events on ca_object_events(event_sdatetime);
create index i_event_edatetime_ca_object_events on ca_object_events(event_edatetime);


/*==========================================================================*/
create table ca_object_event_labels
(
   label_id                       serial,
   event_id                       integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   name                           varchar(1024)                  not null,
   name_sort                      varchar(1024)                  not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_object_event_labels_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
   constraint fk_ca_object_event_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_event_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_name_ca_object_event_labels on ca_object_event_labels(name);
create index i_event_id_ca_object_event_labels on ca_object_event_labels(event_id);
create unique index u_all_ca_object_event_labels on ca_object_event_labels
(
   event_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort_ca_object_event_labels on ca_object_event_labels(name_sort);
create index i_type_id_ca_object_event_labels on ca_object_event_labels(type_id);
create index i_locale_id_ca_object_event_labels on ca_object_event_labels(locale_id);




/*==========================================================================*/
create table ca_objects_x_collections
(
   relation_id                    serial,
   object_id                      integer               not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_collections_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_objects_x_collections on ca_objects_x_collections(object_id);
create index i_collection_id_ca_objects_x_collections on ca_objects_x_collections(collection_id);
create index i_type_id_ca_objects_x_collections on ca_objects_x_collections(type_id);
create unique index u_all_ca_objects_x_collections on ca_objects_x_collections
(
   object_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_objects_x_collections on ca_objects_x_collections(label_left_id);
create index i_label_right_id_ca_objects_x_collections on ca_objects_x_collections(label_right_id);


/*==========================================================================*/
create table ca_objects_x_objects
(
   relation_id                    serial,
   object_left_id                 integer               not null,
   object_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_objects_object_left_id foreign key (object_left_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_object_right_id foreign key (object_right_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
);

create index i_object_left_id_ca_objects_x_objects on ca_objects_x_objects(object_left_id);
create index i_object_right_id_ca_objects_x_objects on ca_objects_x_objects(object_right_id);
create index i_type_id_ca_objects_x_objects on ca_objects_x_objects(type_id);
create unique index u_all_ca_objects_x_objects on ca_objects_x_objects
(
   object_left_id,
   object_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_objects_x_objects on ca_objects_x_objects(label_left_id);
create index i_label_right_id_ca_objects_x_objects on ca_objects_x_objects(label_right_id);


/*==========================================================================*/
create table ca_objects_x_object_representations
(
   relation_id                    serial,
   object_id                      integer                   not null,
   representation_id              integer                   not null,
   is_primary                     integer                        not null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_object_representations_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
   constraint fk_ca_objects_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
);

create index i_object_id_ca_objects_x_object_representations on ca_objects_x_object_representations(object_id);
create index i_representation_id_ca_objects_x_object_representations on ca_objects_x_object_representations(representation_id);
create unique index u_all_ca_objects_x_object_representations on ca_objects_x_object_representations
(
   object_id,
   representation_id
);


/*==========================================================================*/
create table ca_objects_x_occurrences
(
   relation_id                    serial,
   occurrence_id                  integer                   not null,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_objects_x_occurrences on ca_objects_x_occurrences(occurrence_id);
create index i_object_id_ca_objects_x_occurrences on ca_objects_x_occurrences(object_id);
create index i_type_id_ca_objects_x_occurrences on ca_objects_x_occurrences(type_id);
create unique index u_all_ca_objects_x_occurrences on ca_objects_x_occurrences
(
   occurrence_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_objects_x_occurrences on ca_objects_x_occurrences(label_left_id);
create index i_label_right_id_ca_objects_x_occurrences on ca_objects_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_objects_x_places
(
   relation_id                    serial,
   place_id                       integer               not null,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_place_id_ca_objects_x_places on ca_objects_x_places(place_id);
create index i_object_id_ca_objects_x_places on ca_objects_x_places(object_id);
create index i_type_id_ca_objects_x_places on ca_objects_x_places(type_id);
create unique index u_all_ca_objects_x_places on ca_objects_x_places
(
   place_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_objects_x_places on ca_objects_x_places(label_left_id);
create index i_label_right_id_ca_objects_x_places on ca_objects_x_places(label_right_id);


/*==========================================================================*/
create table ca_attributes
(
   attribute_id                   serial,
   element_id                     smallint              not null,
   locale_id                      smallint              null,
   table_num                      integer               not null,
   row_id                         integer                   not null,
   primary key (attribute_id),
   constraint fk_ca_attributes_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attributes_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
);

create index i_locale_id_ca_attributes on ca_attributes(locale_id);
create index i_row_id_ca_attributes on ca_attributes(row_id);
create index i_table_num_ca_attributes on ca_attributes(table_num);
create index i_element_id_ca_attributes on ca_attributes(element_id);


/*==========================================================================*/
create table ca_object_events_x_occurrences
(
   relation_id                    serial,
   event_id                       integer                   not null,
   occurrence_id                  integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_occurrences_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_events_x_occurrences on ca_object_events_x_occurrences(event_id);
create index i_occurrence_id_ca_object_events_x_occurrences on ca_object_events_x_occurrences(occurrence_id);
create index i_type_id_ca_object_events_x_occurrences on ca_object_events_x_occurrences(type_id);
create unique index u_all_ca_object_events_x_occurrences on ca_object_events_x_occurrences
(
   type_id,
   event_id,
   occurrence_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_events_x_occurrences on ca_object_events_x_occurrences(label_left_id);
create index i_label_right_id_ca_object_events_x_occurrences on ca_object_events_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_places
(
   relation_id                    serial,
   event_id                       integer                   not null,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_places_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_events_x_places on ca_object_events_x_places(event_id);
create index i_place_id_ca_object_events_x_places on ca_object_events_x_places(place_id);
create index i_type_id_ca_object_events_x_places on ca_object_events_x_places(type_id);
create unique index u_all_ca_object_events_x_places on ca_object_events_x_places
(
   type_id,
   event_id,
   place_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_events_x_places on ca_object_events_x_places(label_left_id);
create index i_label_right_id_ca_object_events_x_places on ca_object_events_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_storage_locations
(
   relation_id                    serial,
   event_id                       integer                   not null,
   location_id                    integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations(event_id);
create index i_location_id_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations(location_id);
create index i_type_id_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations(type_id);
create unique index u_all_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations
(
   type_id,
   event_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations(label_left_id);
create index i_label_right_id_ca_object_events_x_storage_locations on ca_object_events_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_data_import_events
(
   event_id                       serial,
   occurred_on                    integer                   not null,
   user_id                        integer,
   description                    text                           not null,
   type_code                      char(10)                       not null,
   source                         text                           not null,
   primary key (event_id),
   constraint fk_ca_data_import_events_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
);

create index i_user_id_ca_data_import_events on ca_data_import_events(user_id);


/*==========================================================================*/
create table ca_data_import_items
(
   item_id                        serial,
   event_id                       integer                  not null,
   source_ref                    varchar(255)                  not null,
   table_num                    integer            null,
   row_id                          integer                  null,
   type_code                     char(1)                          null,
   started_on                    integer                 not null,
   completed_on               integer                 null,
   elapsed_time                decimal(8,4)                  null,
   success                        integer            null,
   message                       text                              not null,
   primary key (item_id),
   constraint fk_ca_data_import_items_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict
);

create index i_event_id_ca_data_import_items on ca_data_import_items(event_id);
create index i_row_id_ca_data_import_items on ca_data_import_items(table_num, row_id);


/*==========================================================================*/
create table ca_data_import_event_log
(
   log_id                       serial,
   event_id                    integer                   not null,
   item_id                      integer                   null,
   type_code                  char(10)                       not null,
   date_time                  integer                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
);

create index i_event_id_ca_data_import_event_log on ca_data_import_event_log(event_id);
create index i_item_id_ca_data_import_event_log on ca_data_import_event_log(item_id);


/*==========================================================================*/
create table ca_object_lots_x_collections
(
   relation_id                    serial,
   lot_id                         integer               not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lots_x_collections on ca_object_lots_x_collections(lot_id);
create index i_collection_id_ca_object_lots_x_collections on ca_object_lots_x_collections(collection_id);
create index i_type_id_ca_object_lots_x_collections on ca_object_lots_x_collections(type_id);
create unique index u_all_ca_object_lots_x_collections on ca_object_lots_x_collections
(
   lot_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_lots_x_collections on ca_object_lots_x_collections(label_left_id);
create index i_label_right_id_ca_object_lots_x_collections on ca_object_lots_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_occurrences
(
   relation_id                    serial,
   occurrence_id                  integer                   not null,
   lot_id                         integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences(occurrence_id);
create index i_lot_id_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences(lot_id);
create index i_type_id_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences(type_id);
create unique index u_all_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences
(
   occurrence_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences(label_left_id);
create index i_label_right_id_ca_object_lots_x_occurrences on ca_object_lots_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_places
(
   relation_id                    serial,
   place_id                       integer               not null,
   lot_id                         integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lots_x_places on ca_object_lots_x_places(lot_id);
create index i_place_id_ca_object_lots_x_places on ca_object_lots_x_places(place_id);
create index i_type_id_ca_object_lots_x_places on ca_object_lots_x_places(type_id);
create unique index u_all_ca_object_lots_x_places on ca_object_lots_x_places
(
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_lots_x_places on ca_object_lots_x_places(label_left_id);
create index i_label_right_id_ca_object_lots_x_places on ca_object_lots_x_places(label_right_id);


/*==========================================================================*/
create table ca_acl
(
   aci_id                         serial,
   group_id                       integer,
   user_id                        integer,
   table_num                      integer               not null,
   row_id                         integer                   not null,
   access                         integer               not null,
   notes                          char(10)                       not null,
   primary key (aci_id),
   constraint fk_ca_acl_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_acl_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
);

create index i_row_id_ca_acl on ca_acl(row_id, table_num);
create index i_user_id_ca_acl on ca_acl(user_id);
create index i_group_id_ca_acl on ca_acl(group_id);


/*==========================================================================*/
create table ca_occurrences_x_collections
(
   relation_id                    serial,
   occurrence_id                  integer               not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_collections_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_occurrences_x_collections on ca_occurrences_x_collections(occurrence_id);
create index i_collection_id_ca_occurrences_x_collections on ca_occurrences_x_collections(collection_id);
create index i_type_id_ca_occurrences_x_collections on ca_occurrences_x_collections(type_id);
create unique index u_all_ca_occurrences_x_collections on ca_occurrences_x_collections
(
   occurrence_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_occurrences_x_collections on ca_occurrences_x_collections(label_left_id);
create index i_label_right_id_ca_occurrences_x_collections on ca_occurrences_x_collections(label_right_id);


/*==========================================================================*/
create table ca_occurrences_x_occurrences
(
   relation_id                    serial,
   occurrence_left_id             integer                   not null,
   occurrence_right_id            integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_occurrence_left_id foreign key (occurrence_left_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_occurrence_right_id foreign key (occurrence_right_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_left_id_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences(occurrence_left_id);
create index i_occurrence_right_id_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences(occurrence_right_id);
create index i_type_id_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences(type_id);
create unique index u_all_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences
(
   occurrence_left_id,
   occurrence_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences(label_left_id);
create index i_label_right_id_ca_occurrences_x_occurrences on ca_occurrences_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_entities
(
   entity_id                      serial,
   parent_id                      integer,
   locale_id                      smallint,
   source_id                      integer,
   type_id                        integer                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   is_template                    integer               not null,
   commenting_status              integer               not null,
   tagging_status                 integer               not null,
   rating_status                  integer               not null,
   source_info                    text                       not null,
   life_sdatetime                 decimal(30,20),
   life_edatetime                 decimal(30,20),
   hier_entity_id                 integer                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   rank                             integer                     not null,
   primary key (entity_id),
   constraint fk_ca_entities_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_parent_id foreign key (parent_id)
      references ca_entities (entity_id) on delete restrict on update restrict
);

create index i_source_id_ca_entities on ca_entities(source_id);
create index i_type_id_ca_entities on ca_entities(type_id);
create index i_idno_ca_entities on ca_entities(idno);
create index i_idno_sort_ca_entities on ca_entities(idno_sort);
create index i_hier_entity_id_ca_entities on ca_entities(hier_entity_id);
create index i_locale_id_ca_entities on ca_entities(locale_id);
create index i_parent_id_ca_entities on ca_entities(parent_id);
create index i_hier_left_ca_entities on ca_entities(hier_left);
create index i_hier_right_ca_entities on ca_entities(hier_right);
create index i_life_sdatetime_ca_entities on ca_entities(life_sdatetime);
create index i_life_edatetime_ca_entities on ca_entities(life_edatetime);


/*==========================================================================*/
create table ca_entity_labels
(
   label_id                       serial,
   entity_id                      integer               not null,
   locale_id                      smallint              not null,
   type_id                        integer                   null,
   displayname                    varchar(512)                   not null,
   forename                       varchar(100)                   not null,
   other_forenames                varchar(100)                   not null,
   middlename                     varchar(100)                   not null,
   surname                        varchar(100)                   not null,
   prefix                         varchar(100)                   not null,
   suffix                         varchar(100)                   not null,
   name_sort                      varchar(512)                   not null,
   source_info                    text                       not null,
   is_preferred                   integer               not null,
   primary key (label_id),
   constraint fk_ca_entity_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict
);

create index i_entity_id_ca_entity_labels on ca_entity_labels(entity_id);
create index i_forename_ca_entity_labels on ca_entity_labels(forename);
create index i_surname_ca_entity_labels on ca_entity_labels(surname);
create unique index u_all_ca_entity_labels on ca_entity_labels
(
   entity_id,
   forename,
   other_forenames,
   middlename,
   surname,
   type_id,
   locale_id
);
create index i_locale_id_ca_entity_labels on ca_entity_labels(locale_id);
create index i_type_id_ca_entity_labels on ca_entity_labels(type_id);
create index i_name_sort_ca_entity_labels on ca_entity_labels(name_sort);


/*==========================================================================*/
create table ca_entities_x_collections
(
   relation_id                    serial,
   entity_id                      integer               not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_entity_id_ca_entities_x_collections on ca_entities_x_collections(entity_id);
create index i_collection_id_ca_entities_x_collections on ca_entities_x_collections(collection_id);
create index i_type_id_ca_entities_x_collections on ca_entities_x_collections(type_id);
create unique index u_all_ca_entities_x_collections on ca_entities_x_collections
(
   entity_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_entities_x_collections on ca_entities_x_collections(label_left_id);
create index i_label_right_id_ca_entities_x_collections on ca_entities_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_collections
(
   relation_id                    serial,
   place_id                       integer               not null,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
);

create index i_place_id_ca_places_x_collections on ca_places_x_collections(place_id);
create index i_collection_id_ca_places_x_collections on ca_places_x_collections(collection_id);
create index i_type_id_ca_places_x_collections on ca_places_x_collections(type_id);
create unique index u_all_ca_places_x_collections on ca_places_x_collections
(
   place_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_places_x_collections on ca_places_x_collections(label_left_id);
create index i_label_right_id_ca_places_x_collections on ca_places_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_occurrences
(
   relation_id                    serial,
   occurrence_id                  integer                   not null,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_places_x_occurrences on ca_places_x_occurrences(occurrence_id);
create index i_place_id_ca_places_x_occurrences on ca_places_x_occurrences(place_id);
create index i_type_id_ca_places_x_occurrences on ca_places_x_occurrences(type_id);
create unique index u_all_ca_places_x_occurrences on ca_places_x_occurrences
(
   place_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_places_x_occurrences on ca_places_x_occurrences(label_left_id);
create index i_label_right_id_ca_places_x_occurrences on ca_places_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_places_x_places
(
   relation_id                    serial,
   place_left_id                  integer               not null,
   place_right_id                 integer               not null,
   type_id                        smallint              null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_place_left_id foreign key (place_left_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_place_right_id foreign key (place_right_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_place_left_id_ca_places_x_places on ca_places_x_places(place_left_id);
create index i_place_right_id_ca_places_x_places on ca_places_x_places(place_right_id);
create index i_type_id_ca_places_x_places on ca_places_x_places(type_id);
create unique index u_all_ca_places_x_places on ca_places_x_places
(
   place_left_id,
   place_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_places_x_places on ca_places_x_places(label_left_id);
create index i_label_right_id_ca_places_x_places on ca_places_x_places(label_right_id);


/*==========================================================================*/
create table ca_entities_x_occurrences
(
   relation_id                    serial,
   occurrence_id                  integer                   not null,
   type_id                        smallint              not null,
   entity_id                      integer               not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_occurrences_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_entity_id_ca_entities_x_occurrences on ca_entities_x_occurrences(entity_id);
create index i_occurrence_id_ca_entities_x_occurrences on ca_entities_x_occurrences(occurrence_id);
create index i_type_id_ca_entities_x_occurrences on ca_entities_x_occurrences(type_id);
create unique index u_all_ca_entities_x_occurrences on ca_entities_x_occurrences
(
   occurrence_id,
   type_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_entities_x_occurrences on ca_entities_x_occurrences(label_left_id);
create index i_label_right_id_ca_entities_x_occurrences on ca_entities_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_relationship_relationships
(
   reification_id                 serial,
   type_id                        smallint              not null,
   relationship_table_num         integer               not null,
   relation_id                    integer                   not null,
   table_num                      integer                        not null,
   row_id                         integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   primary key (reification_id),
   constraint ca_relationship_relationships_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
);

create index i_type_id_ca_relationship_relationships on ca_relationship_relationships(type_id);
create index i_relation_row_ca_relationship_relationships on ca_relationship_relationships
(
   relation_id,
   relationship_table_num
);
create index i_target_row_ca_relationship_relationships on ca_relationship_relationships
(
   row_id,
   table_num
);


/*==========================================================================*/
create table ca_entities_x_places
(
   relation_id                    serial,
   entity_id                      integer               not null,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_places_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_place_id_ca_entities_x_places on ca_entities_x_places(place_id);
create index i_entity_id_ca_entities_x_places on ca_entities_x_places(entity_id);
create index i_type_id_ca_entities_x_places on ca_entities_x_places(type_id);
create unique index u_all_ca_entities_x_places on ca_entities_x_places
(
   entity_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_entities_x_places on ca_entities_x_places(label_left_id);
create index i_label_right_id_ca_entities_x_places on ca_entities_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_entities
(
   relation_id                    serial,
   representation_id              integer                   not null,
   entity_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_entities_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_entities on ca_object_representations_x_entities(representation_id);
create index i_entity_id_ca_object_representations_x_entities on ca_object_representations_x_entities(entity_id);
create index i_type_id_ca_object_representations_x_entities on ca_object_representations_x_entities(type_id);
create unique index u_all_ca_object_representations_x_entities on ca_object_representations_x_entities
(
   type_id,
   representation_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_entities on ca_object_representations_x_entities(label_left_id);
create index i_label_right_id_ca_object_representations_x_entities on ca_object_representations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_object_representations
(
   relation_id                    serial,
   representation_left_id                 integer               not null,
   representation_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_object_reps_rep_left_id foreign key (representation_left_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_rep_right_id foreign key (representation_right_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
);

create index i_representation_left_id_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations(representation_left_id);
create index i_representation_right_id_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations(representation_right_id);
create index i_type_id_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations(type_id);
create unique index u_all_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations
(
   representation_left_id,
   representation_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations(label_left_id);
create index i_label_right_id_ca_object_representations_x_object_representations on ca_object_representations_x_object_representations(label_right_id);


/*==========================================================================*/
create table ca_entities_x_entities
(
   relation_id                    serial,
   entity_left_id                 integer               not null,
   entity_right_id                integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_entity_left_id foreign key (entity_left_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_entity_right_id foreign key (entity_right_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_entity_left_id_ca_entities_x_entities on ca_entities_x_entities(entity_left_id);
create index i_entity_right_id_ca_entities_x_entities on ca_entities_x_entities(entity_right_id);
create index i_type_id_ca_entities_x_entities on ca_entities_x_entities(type_id);
create unique index u_all_ca_entities_x_entities on ca_entities_x_entities
(
   entity_left_id,
   entity_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_entities_x_entities on ca_entities_x_entities(label_left_id);
create index i_label_right_id_ca_entities_x_entities on ca_entities_x_entities(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_entities
(
   relation_id                    serial,
   annotation_id                  integer                   not null,
   entity_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_entities_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_entity_id_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities(entity_id);
create index i_annotation_id_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities(annotation_id);
create index i_type_id_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities(type_id);
create unique index u_all_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities
(
   entity_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities(label_left_id);
create index i_label_right_id_ca_representation_annotations_x_entities on ca_representation_annotations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_groups_x_roles
(
   relation_id                    serial,
   group_id                       integer                   not null,
   role_id                        smallint              not null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_groups_x_roles_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_groups_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
);

create index i_group_id_ca_groups_x_roles on ca_groups_x_roles(group_id);
create index i_role_id_ca_groups_x_roles on ca_groups_x_roles(role_id);
create index u_all_ca_groups_x_roles on ca_groups_x_roles
(
   group_id,
   role_id
);


/*==========================================================================*/
create table ca_ips
(
   ip_id                          serial,
   user_id                        integer                   not null,
   ip1                            integer               not null,
   ip2                            integer,
   ip3                            integer,
   ip4s                           integer,
   ip4e                           integer,
   notes                          text                           not null,
   primary key (ip_id),
   constraint fk_ca_ips_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
);

create unique index u_ip_ca_ips on ca_ips
(
   ip1,
   ip2,
   ip3,
   ip4s,
   ip4e
);
create index i_user_id_ca_ips on ca_ips(user_id);


/*==========================================================================*/
create table ca_representation_annotations_x_objects
(
   relation_id                    serial,
   annotation_id                        integer                   not null,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_objects_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_objects_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects(object_id);
create index i_annotation_id_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects(annotation_id);
create index i_type_id_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects(type_id);
create unique index u_all_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects
(
   object_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects(label_left_id);
create index i_label_right_id_ca_representation_annotations_x_objects on ca_representation_annotations_x_objects(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_occurrences
(
   relation_id                    serial,
   annotation_id                        integer                   not null,
   occurrence_id                  integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_occurrences_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_occurrences_label_right_id foreign key (label_right_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict
);

create index i_occurrence_id_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences(occurrence_id);
create index i_annotation_id_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences(annotation_id);
create index i_type_id_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences(type_id);
create unique index u_all_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences
(
   occurrence_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences(label_left_id);
create index i_label_right_id_ca_representation_annotations_x_occurrences on ca_representation_annotations_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_list_items_x_list_items
(
   relation_id                    serial,
   term_left_id                   integer                   not null,
   term_right_id                  integer                   not null,
   type_id                        smallint              null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint ca_ca_list_items_x_list_items_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint ca_ca_list_items_x_list_items_term_left_id foreign key (term_left_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint ca_ca_list_items_x_list_items_term_right_id foreign key (term_right_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_x_list_items_label_left_id foreign key (label_left_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_x_list_items_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_term_left_id_ca_list_items_x_list_items on ca_list_items_x_list_items(term_left_id);
create index i_term_right_id_ca_list_items_x_list_items on ca_list_items_x_list_items(term_right_id);
create index i_type_id_ca_list_items_x_list_items on ca_list_items_x_list_items(type_id);
create unique index u_all_ca_list_items_x_list_items on ca_list_items_x_list_items
(
   term_left_id,
   term_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_list_items_x_list_items on ca_list_items_x_list_items(label_left_id);
create index i_label_right_id_ca_list_items_x_list_items on ca_list_items_x_list_items(label_right_id);


/*==========================================================================*/
create table ca_objects_x_storage_locations (
   relation_id                    serial,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   location_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_objects_x_storage_locations  on ca_objects_x_storage_locations (object_id);
create index i_location_id_ca_objects_x_storage_locations  on ca_objects_x_storage_locations (location_id);
create index i_type_id_ca_objects_x_storage_locations  on ca_objects_x_storage_locations (type_id);
create unique index u_all_ca_objects_x_storage_locations  on ca_objects_x_storage_locations (
   object_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id_ca_objects_x_storage_locations on ca_objects_x_storage_locations(label_left_id);
create index i_label_right_id_ca_objects_x_storage_locations on ca_objects_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_storage_locations (
   relation_id                    serial,
   lot_id                         integer               not null,
   type_id                        smallint              not null,
   location_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_storage_locations_relation_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lots_x_storage_locations  on ca_object_lots_x_storage_locations (lot_id);
create index i_location_id_ca_object_lots_x_storage_locations  on ca_object_lots_x_storage_locations (location_id);
create index i_type_id_ca_object_lots_x_storage_locations  on ca_object_lots_x_storage_locations (type_id);
create unique index u_all_ca_object_lots_x_storage_locations  on ca_object_lots_x_storage_locations (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id_ca_object_lots_x_storage_locations on ca_object_lots_x_storage_locations(label_left_id);
create index i_label_right_id_ca_object_lots_x_storage_locations on ca_object_lots_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_entities_x_storage_locations
(
   relation_id                    serial,
   entity_id                      integer               not null,
   location_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
);

create index i_entity_id_ca_entities_x_storage_locations on  ca_entities_x_storage_locations(entity_id);
create index i_location_id_ca_entities_x_storage_locations on  ca_entities_x_storage_locations(location_id);
create index i_type_id_ca_entities_x_storage_locations on  ca_entities_x_storage_locations(type_id);
create unique index u_all_ca_entities_x_storage_locations on  ca_entities_x_storage_locations
(
   entity_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_entities_x_storage_locations on  ca_entities_x_storage_locations(label_left_id);
create index i_label_right_id_ca_entities_x_storage_locations on  ca_entities_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_vocabulary_terms (
   relation_id                    serial,
   lot_id                         integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_vocabulary_terms_relation_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lots_x_vocabulary_terms  on ca_object_lots_x_vocabulary_terms (lot_id);
create index i_item_id_ca_object_lots_x_vocabulary_terms  on ca_object_lots_x_vocabulary_terms (item_id);
create index i_type_id_ca_object_lots_x_vocabulary_terms  on ca_object_lots_x_vocabulary_terms (type_id);
create unique index u_all_ca_object_lots_x_vocabulary_terms  on ca_object_lots_x_vocabulary_terms (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_object_lots_x_vocabulary_terms on ca_object_lots_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_object_lots_x_vocabulary_terms on ca_object_lots_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_vocabulary_terms (
   relation_id                    serial,
   representation_id              integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_obj_rep_x_voc_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_obj_rep_x_voc_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_representation_id_ca_object_representations_x_vocabulary_terms  on ca_object_representations_x_vocabulary_terms (representation_id);
create index i_item_id_ca_object_representations_x_vocabulary_terms  on ca_object_representations_x_vocabulary_terms (item_id);
create index i_type_id_ca_object_representations_x_vocabulary_terms  on ca_object_representations_x_vocabulary_terms (type_id);
create unique index u_all_ca_object_representations_x_vocabulary_terms  on ca_object_representations_x_vocabulary_terms (
   representation_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_object_representations_x_vocabulary_terms on ca_object_representations_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_object_representations_x_vocabulary_terms on ca_object_representations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_vocabulary_terms (
   relation_id                    serial,
   event_id                         integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_events_x_vocabulary_terms  on ca_object_events_x_vocabulary_terms (event_id);
create index i_item_id_ca_object_events_x_vocabulary_terms  on ca_object_events_x_vocabulary_terms (item_id);
create index i_type_id_ca_object_events_x_vocabulary_terms  on ca_object_events_x_vocabulary_terms (type_id);
create unique index u_all_ca_object_events_x_vocabulary_terms  on ca_object_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_object_events_x_vocabulary_terms on ca_object_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_object_events_x_vocabulary_terms on ca_object_events_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lot_events_x_vocabulary_terms (
   relation_id                    serial,
   event_id                         integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lot_events_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_event_id foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_lot_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lot_events_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_lot_events_x_vocabulary_terms  on ca_object_lot_events_x_vocabulary_terms (event_id);
create index i_item_id_ca_object_lot_events_x_vocabulary_terms  on ca_object_lot_events_x_vocabulary_terms (item_id);
create index i_type_id_ca_object_lot_events_x_vocabulary_terms  on ca_object_lot_events_x_vocabulary_terms (type_id);
create unique index u_all_ca_object_lot_events_x_vocabulary_terms  on ca_object_lot_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_object_lot_events_x_vocabulary_terms on ca_object_lot_events_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_object_lot_events_x_vocabulary_terms on ca_object_lot_events_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_users_x_groups
(
   relation_id                    serial,
   user_id                        integer                   not null,
   group_id                       integer                   not null,
   primary key (relation_id),
   constraint fk_ca_users_x_groups_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_groups_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
);

create index i_user_id_ca_users_x_groups on ca_users_x_groups(user_id);
create index i_group_id_ca_users_x_groups on ca_users_x_groups(group_id);
create unique index u_all_ca_users_x_groups on ca_users_x_groups
(
   user_id,
   group_id
);


/*==========================================================================*/
create table ca_users_x_roles
(
   relation_id                    serial,
   user_id                        integer                   not null,
   role_id                        smallint              not null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_users_x_roles_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
);

create index i_user_id_ca_users_x_roles on ca_users_x_roles(user_id);
create index i_role_id_ca_users_x_roles on ca_users_x_roles(role_id);
create unique index u_all_ca_users_x_roles on ca_users_x_roles
(
   user_id,
   role_id
);


/*==========================================================================*/
create table ca_representation_annotations_x_places
(
   relation_id                    serial,
   annotation_id                        integer                   not null,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_places_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_places_label_right_id foreign key (label_right_id)
      references ca_place_labels (label_id) on delete restrict on update restrict
);

create index i_place_id_ca_representation_annotations_x_places on ca_representation_annotations_x_places(place_id);
create index i_annotation_id_ca_representation_annotations_x_places on ca_representation_annotations_x_places(annotation_id);
create index i_type_id_ca_representation_annotations_x_places on ca_representation_annotations_x_places(type_id);
create unique index u_all_ca_representation_annotations_x_places on ca_representation_annotations_x_places
(
   place_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_representation_annotations_x_places on ca_representation_annotations_x_places(label_left_id);
create index i_label_right_id_ca_representation_annotations_x_places on ca_representation_annotations_x_places(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_vocabulary_terms
(
   relation_id                    serial,
   annotation_id                  integer                   not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_rep_annot_x_vocabulary_terms_annotation_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_representation_annotation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_rep_annot_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_item_id_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms(item_id);
create index i_annotation_id_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms(annotation_id);
create index i_type_id_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms(type_id);
create unique index u_all_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms
(
   type_id,
   annotation_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_representation_annotations_x_vocabulary_terms on ca_representation_annotations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_objects_x_vocabulary_terms
(
   relation_id                    serial,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms(object_id);
create index i_item_id_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms(item_id);
create index i_type_id_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms(type_id);
create unique index u_all_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms
(
   object_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_objects_x_vocabulary_terms on ca_objects_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_entities
(
   relation_id                    serial,
   entity_id                      integer               not null,
   lot_id                         integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_lot_id_ca_object_lots_x_entities on ca_object_lots_x_entities(lot_id);
create index i_entity_id_ca_object_lots_x_entities on ca_object_lots_x_entities(entity_id);
create index i_type_id_ca_object_lots_x_entities on ca_object_lots_x_entities(type_id);
create unique index u_all_ca_object_lots_x_entities on ca_object_lots_x_entities
(
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_lots_x_entities on ca_object_lots_x_entities(label_left_id);
create index i_label_right_id_ca_object_lots_x_entities on ca_object_lots_x_entities(label_right_id);


/*==========================================================================*/
create table ca_objects_x_entities
(
   relation_id                    serial,
   entity_id                      integer               not null,
   object_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_objects_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_entity_id_ca_objects_x_entities on ca_objects_x_entities(entity_id);
create index i_object_id_ca_objects_x_entities on ca_objects_x_entities(object_id);
create index i_type_id_ca_objects_x_entities on ca_objects_x_entities(type_id);
create unique index u_all_ca_objects_x_entities on ca_objects_x_entities
(
   entity_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_objects_x_entities on ca_objects_x_entities(label_left_id);
create index i_label_right_id_ca_objects_x_entities on ca_objects_x_entities(label_right_id);


/*==========================================================================*/
create table ca_places_x_vocabulary_terms
(
   relation_id                    serial,
   place_id                       integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_places_x_vocabulary_terms_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_place_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_places_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_place_id_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms(place_id);
create index i_item_id_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms(item_id);
create index i_type_id_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms(type_id);
create unique index u_all_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms
(
   place_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_places_x_vocabulary_terms on ca_places_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_loans_x_objects (
   relation_id                    serial,
   loan_id                         integer               not null,
   type_id                        smallint              not null,
   object_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      
);

create index i_loan_id_ca_loans_x_objects  on ca_loans_x_objects (loan_id);
create index i_object_id_ca_loans_x_objects  on ca_loans_x_objects (object_id);
create index i_type_id_ca_loans_x_objects  on ca_loans_x_objects (type_id);
create unique index u_all_ca_loans_x_objects  on ca_loans_x_objects (
   loan_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_objects  on ca_loans_x_objects (label_left_id);
create index i_label_right_id_ca_loans_x_objects  on ca_loans_x_objects (label_right_id);


/*==========================================================================*/
create table ca_loans_x_entities (
   relation_id                    serial,
   loan_id                         integer               not null,
   type_id                        smallint              not null,
   entity_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      
);

create index i_loan_id_ca_loans_x_entities  on ca_loans_x_entities (loan_id);
create index i_entity_id_ca_loans_x_entities  on ca_loans_x_entities (entity_id);
create index i_type_id_ca_loans_x_entities  on ca_loans_x_entities (type_id);
create unique index u_all_ca_loans_x_entities  on ca_loans_x_entities (
   loan_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_entities  on ca_loans_x_entities (label_left_id);
create index i_label_right_id_ca_loans_x_entities  on ca_loans_x_entities (label_right_id);


/*==========================================================================*/
create table ca_movements_x_objects (
   relation_id                    serial,
   movement_id                    integer                   not null,
   type_id                        smallint              not null,
   object_id                      integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      
);

create index i_movement_id_ca_movements_x_objects  on ca_movements_x_objects (movement_id);
create index i_object_id_ca_movements_x_objects  on ca_movements_x_objects (object_id);
create index i_type_id_ca_movements_x_objects  on ca_movements_x_objects (type_id);
create unique index u_all_ca_movements_x_objects  on ca_movements_x_objects (
   movement_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_objects  on ca_movements_x_objects (label_left_id);
create index i_label_right_id_ca_movements_x_objects  on ca_movements_x_objects (label_right_id);


/*==========================================================================*/
create table ca_movements_x_object_lots (
   relation_id                    serial,
   movement_id                    integer               not null,
   type_id                        smallint              not null,
   lot_id                         integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      
);

create index i_movement_id_ca_movements_x_object_lots  on ca_movements_x_object_lots (movement_id);
create index i_lot_id_ca_movements_x_object_lots  on ca_movements_x_object_lots (lot_id);
create index i_type_id_ca_movements_x_object_lots  on ca_movements_x_object_lots (type_id);
create unique index u_all_ca_movements_x_object_lots  on ca_movements_x_object_lots (
   movement_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_object_lots  on ca_movements_x_object_lots (label_left_id);
create index i_label_right_id_ca_movements_x_object_lots  on ca_movements_x_object_lots (label_right_id);


/*==========================================================================*/
create table ca_movements_x_entities (
   relation_id                    serial,
   movement_id                    integer                   not null,
   type_id                        smallint              not null,
   entity_id                      integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
      
);

create index i_movement_id_ca_movements_x_entities  on ca_movements_x_entities (movement_id);
create index i_entity_id_ca_movements_x_entities  on ca_movements_x_entities (entity_id);
create index i_type_id_ca_movements_x_entities  on ca_movements_x_entities (type_id);
create unique index u_all_ca_movements_x_entities  on ca_movements_x_entities (
   movement_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_entities  on ca_movements_x_entities (label_left_id);
create index i_label_right_id_ca_movements_x_entities  on ca_movements_x_entities (label_right_id);


/*==========================================================================*/
create table ca_loans_x_movements (
   relation_id                    serial,
   loan_id                         integer               not null,
   type_id                        smallint              not null,
   movement_id                    integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_movements_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movement_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
      
);

create index i_loan_id_ca_loans_x_movements  on ca_loans_x_movements (loan_id);
create index i_movement_id_ca_loans_x_movements  on ca_loans_x_movements (movement_id);
create index i_type_id_ca_loans_x_movements  on ca_loans_x_movements (type_id);
create unique index u_all_ca_loans_x_movements  on ca_loans_x_movements (
   loan_id,
   movement_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_movements  on ca_loans_x_movements (label_left_id);
create index i_label_right_id_ca_loans_x_movements  on ca_loans_x_movements (label_right_id);


/*==========================================================================*/
create table ca_attribute_values
(
   value_id                   	  serial,
   element_id                     smallint              not null,
   attribute_id                   integer                   not null,
   item_id                        integer,
   value_text1                text,
   value_text2                text,
   value_blob                     bytea,
   value_decimal1                 decimal(40,20),
   value_decimal2                 decimal(40,20),
   value_integer1                 integer,
   source_info                    text                       not null,
   primary key (value_id),
   constraint fk_ca_attribute_values_attribute_id foreign key (attribute_id)
      references ca_attributes (attribute_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict
);

create index i_element_id_ca_attribute_values on ca_attribute_values(element_id);
create index i_attribute_id_ca_attribute_values on ca_attribute_values(attribute_id);
create index i_value_integer1_ca_attribute_values on ca_attribute_values(value_integer1);
create index i_value_decimal1_ca_attribute_values on ca_attribute_values(value_decimal1);
create index i_value_decimal2_ca_attribute_values on ca_attribute_values(value_decimal2);
create index i_item_id_ca_attribute_values on ca_attribute_values(item_id);
/*create index i_value_text1_ca_attribute_values on ca_attribute_values
(
   value_text1(1024)
)SEMICOLON
create index i_value_text2_ca_attribute_values on ca_attribute_values
(
   value_text2(1024)
)SEMICOLON*/


/*==========================================================================*/
create table ca_occurrences_x_vocabulary_terms
(
   relation_id                    serial,
   occurrence_id                  integer                   not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_occurrences_x_vocabulary_terms_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_occurrence_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms(occurrence_id);
create index i_item_id_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms(item_id);
create index i_type_id_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms(type_id);
create unique index u_all_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms
(
   occurrence_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_occurrences_x_vocabulary_terms on ca_occurrences_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_events_x_entities
(
   relation_id                    serial,
   event_id                       integer                   not null,
   entity_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_object_events_x_entities_event_id foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_label_left_id foreign key (label_left_id)
      references ca_object_event_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_events_x_entities_label_right_id foreign key (label_right_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict
);

create index i_event_id_ca_object_events_x_entities on ca_object_events_x_entities(event_id);
create index i_entity_id_ca_object_events_x_entities on ca_object_events_x_entities(entity_id);
create index i_type_id_ca_object_events_x_entities on ca_object_events_x_entities(type_id);
create unique index u_all_ca_object_events_x_entities on ca_object_events_x_entities
(
   type_id,
   event_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_object_events_x_entities on ca_object_events_x_entities(label_left_id);
create index i_label_right_id_ca_object_events_x_entities on ca_object_events_x_entities(label_right_id);


/*==========================================================================*/
create table ca_collections_x_vocabulary_terms
(
   relation_id                    serial,
   collection_id                  integer                   not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_collections_x_vocabulary_terms_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_item_id_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms(item_id);
create index i_collection_id_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms(collection_id);
create index i_type_id_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms(type_id);
create unique index u_all_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms
(
   collection_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_collections_x_vocabulary_terms on ca_collections_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_entities_x_vocabulary_terms
(
   relation_id                    serial,
   entity_id                      integer               not null,
   type_id                        smallint              not null,
   item_id                        integer                   not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_vocabulary_terms_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict
);

create index i_object_id_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms(entity_id);
create index i_item_id_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms(item_id);
create index i_type_id_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms(type_id);
create unique index u_all_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms
(
   entity_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_entities_x_vocabulary_terms on ca_entities_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_editor_uis (
	ui_id serial,
	user_id integer null references ca_users(user_id),		/* owner of ui */
	is_system_ui integer not null,
	editor_type integer not null,							/* tablenum of editor */
	editor_code varchar(100) null,
	color char(6) null,
	icon bytea not null,
	
	primary key 				(ui_id)
);
create index i_user_id_ca_editor_uis on ca_editor_uis(user_id);

create unique index u_code_ca_editor_uis on ca_editor_uis(editor_code);

/*==========================================================================*/
create table ca_editor_ui_labels (
	label_id serial,
	ui_id integer not null references ca_editor_uis(ui_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id)
);
create index labels_i_ui_id_ca_editor_ui on ca_editor_ui_labels(ui_id);
create index labels_i_locale_id_ca_editor_ui on  ca_editor_ui_labels(locale_id);


/*==========================================================================*/
create table ca_editor_uis_x_user_groups (
	relation_id serial,
	ui_id integer not null references ca_editor_uis(ui_id),
	group_id integer not null references ca_user_groups(group_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);
create index i_ui_id_ca_editor_uis_x_user_groups on ca_editor_uis_x_user_groups(ui_id);
create index i_group_id_ca_editor_uis_x_user_groups on ca_editor_uis_x_user_groups(group_id);


/*==========================================================================*/
create table ca_editor_uis_x_users (
	relation_id serial,
	ui_id integer not null references ca_editor_uis(ui_id),
	user_id integer not null references ca_users(user_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);
create index i_ui_id_ca_editor_uis_x_users on ca_editor_uis_x_users(ui_id);
create index i_user_id_ca_editor_uis_x_users on ca_editor_uis_x_users(user_id);


/*==========================================================================*/
create table ca_editor_ui_screens (
	screen_id serial,
	parent_id integer null,
	ui_id integer not null references ca_editor_uis(ui_id),
	idno varchar(255) not null,
	rank smallint not null,
	is_default integer not null,
	color char(6) null,
	icon bytea not null,
	
	hier_left decimal(30,20) not null,
	hier_right decimal (30,20) not null,
	
	primary key 				(screen_id),
      
   constraint fk_ca_editor_ui_screens_parent_id foreign key (parent_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
);
create index i_ui_id_ca_editor_ui_screens on ca_editor_ui_screens(ui_id);
create	index i_parent_id_ca_editor_ui_screens on ca_editor_ui_screens			(parent_id);
create	index i_hier_left_ca_editor_ui_screens on ca_editor_ui_screens			(hier_left);
create	index i_hier_right_ca_editor_ui_screens on ca_editor_ui_screens			(hier_right);


/*==========================================================================*/
create table ca_editor_ui_screen_labels (
	label_id serial,
	screen_id integer not null references ca_editor_ui_screens(screen_id),
	name varchar(255) not null,
	description text not null,
	locale_id smallint not null references ca_locales(locale_id),
	
	primary key 				(label_id)
);
create	index i_screen_id_ca_editor_ui_screen_labels on ca_editor_ui_screen_labels			(screen_id);
create	index i_locale_id_ca_editor_ui_screen_labels on ca_editor_ui_screen_labels			(locale_id);


/*==========================================================================*/
create table ca_editor_ui_bundle_placements (
	placement_id serial,
	screen_id integer not null references ca_editor_ui_screens(screen_id),
	placement_code varchar(255) not null,
	bundle_name varchar(255) not null,
	
	rank smallint not null,
    settings text not null,
	
	primary key 				(placement_id)
);
create	index i_screen_id_ca_editor_ui_bundle_placements on ca_editor_ui_bundle_placements			(screen_id);
create	unique index u_bundle_name_ca_editor_ui_bundle_placements on ca_editor_ui_bundle_placements	(bundle_name, screen_id, placement_code);


/*==========================================================================*/
create table ca_editor_ui_screen_type_restrictions (
   restriction_id                 serial,
   table_num                      integer               not null,
   type_id                        integer,
   screen_id                      integer                   not null,
   settings                       text                       not null,
   rank                           smallint              not null,
   primary key (restriction_id),
   constraint fk_ca_editor_ui_screen_type_restrictions_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
);


/*==========================================================================*/
create table ca_sets (
	set_id		serial,
	parent_id	integer,
	hier_set_id integer not null,
	user_id		integer null references ca_users(user_id),
    type_id     integer not null,
    commenting_status integer not null,
    tagging_status integer not null,
    rating_status integer not null,
	set_code    varchar(100) null,
	table_num	integer not null,
	status		integer not null,
	access		integer not null,	
	hier_left	decimal(30,20) not null,
	hier_right	decimal(30,20) not null,
   rank                             integer                     not null,
	
	primary key (set_id),
      
      
   constraint fk_ca_sets_parent_id foreign key (parent_id)
      references ca_sets (set_id) on delete restrict on update restrict
);
create	index i_user_id_ca_sets on ca_sets (user_id);
create	index i_type_id_ca_sets on ca_sets (type_id);
create	unique index u_set_code_ca_sets on ca_sets (set_code);
create	index i_hier_left_ca_sets on ca_sets (hier_left);
create	index i_hier_right_ca_sets on ca_sets (hier_right);
create	index i_parent_id_ca_sets on ca_sets (parent_id);
create	index i_hier_set_id_ca_sets on ca_sets (hier_set_id);
create	index i_table_num_ca_sets on ca_sets (table_num);


/*==========================================================================*/
create table ca_set_labels (
	label_id	serial,
	set_id		integer not null references ca_sets(set_id),
	locale_id	smallint not null references ca_locales(locale_id),
	
	name		varchar(255) not null,
	
	primary key (label_id)
);
create	index i_set_id_ca_set_labels on ca_set_labels (set_id);
create	index i_locale_id_ca_set_labels on ca_set_labels (locale_id);


/*==========================================================================*/
create table ca_set_items (
	item_id		serial,
	set_id		integer not null references ca_sets(set_id),
	table_num	integer not null,
	row_id		integer not null,
    type_id     integer not null,
	rank		integer not null,
	
	primary key (item_id)
);
create	index i_set_id_ca_set_items on ca_set_items (set_id);
create	index i_type_id_ca_set_items on ca_set_items (type_id);
create	index i_row_id_ca_set_items on ca_set_items (row_id);
create	index i_table_num_ca_set_items on ca_set_items (table_num);


/*==========================================================================*/
create table ca_set_item_labels (
	label_id	serial,
	item_id		integer not null references ca_set_items(item_id),
	
	locale_id	smallint not null references ca_locales(locale_id),
	
	caption		text not null,
	
	primary key (label_id)
);
create	index i_set_id_ca_set_item_labels on ca_set_item_labels (item_id);
create	index i_locale_id_ca_set_item_labels on ca_set_item_labels (locale_id);


/*==========================================================================*/
create table ca_sets_x_user_groups (
	relation_id serial,
	set_id integer not null references ca_sets(set_id),
	group_id integer not null references ca_user_groups(group_id),
	access integer not null,
	sdatetime integer null,
	edatetime integer null,
	
	primary key 				(relation_id)
);
create	index i_set_id_ca_sets_x_user_groups on ca_sets_x_user_groups				(set_id);
create	index i_group_id_ca_sets_x_user_groups on ca_sets_x_user_groups			(group_id);


/*==========================================================================*/
create table ca_sets_x_users (
	relation_id serial,
	set_id integer not null references ca_sets(set_id),
	user_id integer not null references ca_users(user_id),
	access integer not null,
	sdatetime integer null,
	edatetime integer null,
	
	primary key 				(relation_id)
);
create	index i_set_id_ca_sets_x_users on ca_sets_x_users				(set_id);
create	index i_user_id_ca_sets_x_users on ca_sets_x_users			(user_id);


/*==========================================================================*/
create table ca_item_comments (
	comment_id	serial,
	table_num	integer not null,
	row_id		integer not null,
	
	user_id		integer null references ca_users(user_id),
	locale_id	smallint not null references ca_locales(locale_id),
	
	media1 bytea not null,
	media2 bytea not null,
	media3 bytea not null,
	media4 bytea not null,
	
	comment		text null,
	rating		integer null,
	email		varchar(255),
	name		varchar(255),
	created_on	integer not null,
	access		integer not null,
	ip_addr		varchar(39) null,
	moderated_on integer null,
	moderated_by_user_id integer null references ca_users(user_id),
	
	primary key (comment_id)
);
create	index i_row_id_ca_item_comments on ca_item_comments (row_id);
create	index i_table_num_ca_item_comments on ca_item_comments (table_num);
create	index i_email_ca_item_comments on ca_item_comments (email);
create	index i_user_id_ca_item_comments on ca_item_comments (user_id);
create	index i_created_on_ca_item_comments on ca_item_comments (created_on);
create	index i_access_ca_item_comments on ca_item_comments (access);
create	index i_moderated_on_ca_item_comments on ca_item_comments (moderated_on);


/*==========================================================================*/
create table ca_item_tags (
	tag_id		serial,

	locale_id	smallint not null references ca_locales(locale_id),
	tag			varchar(255) not null,
	
	primary key (tag_id)
);
create index u_tag_ca_item_tags on ca_item_tags(tag, locale_id);


/*==========================================================================*/
create table ca_items_x_tags (
	relation_id	serial,
	table_num	integer not null,
	row_id		integer not null,
	
	tag_id		integer not null references ca_item_tags(tag_id),
	
	user_id		integer null references ca_users(user_id),
	access		integer not null,
	
	ip_addr		char(39) null,
	
	created_on	integer not null,
	
	moderated_on integer null,
	moderated_by_user_id integer null references ca_users(user_id),
	
	primary key (relation_id)
);
create	index i_row_id_ca_items_x_tags on ca_items_x_tags (row_id);
create	index i_table_num_ca_items_x_tags on ca_items_x_tags (table_num);
create	index i_tag_id_ca_items_x_tags on ca_items_x_tags (tag_id);
create	index i_user_id_ca_items_x_tags on ca_items_x_tags (user_id);
create	index i_access_ca_items_x_tags on ca_items_x_tags (access);
create	index i_created_on_ca_items_x_tags on ca_items_x_tags (created_on);
create	index i_moderated_on_ca_items_x_tags on ca_items_x_tags (moderated_on);


/*==========================================================================*/
create table ca_item_views (
	view_id		serial,
	table_num	integer not null,
	row_id		integer not null,
	
	user_id		integer null references ca_users(user_id),
	locale_id	smallint not null references ca_locales(locale_id),
	
	viewed_on	integer not null,
	ip_addr		varchar(39) null,
	
	primary key (view_id)
);
create	index i_row_id_ca_item_views on ca_item_views (row_id);
create	index i_table_num_ca_item_views on ca_item_views (table_num);
create	index i_user_id_ca_item_views on ca_item_views (user_id);
create	index i_created_on_ca_item_views on ca_item_views (viewed_on);


/*==========================================================================*/
create table ca_item_view_counts (
	table_num	smallint not null,
	row_id		integer not null,
	view_count	integer not null
	
);
create	index u_row_ca_item_view_counts on ca_item_view_counts (row_id, table_num);
create	index i_row_id_ca_item_view_counts on ca_item_view_counts (row_id);
create	index i_table_num_ca_item_view_counts on ca_item_view_counts (table_num);
create	index i_view_count_ca_item_view_counts on ca_item_view_counts (view_count);


/*==========================================================================*/
create table ca_search_forms (
	form_id			serial,
	user_id			integer null references ca_users(user_id),
	
	form_code		varchar(100) null,
	table_num		integer not null,
	
	is_system		integer not null,
	
	settings		text not null,
	primary key 		(form_id)
	
);
create	UNIQUE index u_form_code_ca_search_forms on ca_search_forms (form_code);
create	index i_user_id_ca_search_forms on ca_search_forms (user_id);
create	index i_table_num_ca_search_forms on ca_search_forms (table_num);


/*==========================================================================*/
create table ca_search_form_labels (
	label_id		serial,
	form_id			integer null references ca_search_forms(form_id),
	locale_id		smallint not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		text not null,
	is_preferred	integer not null
	
);
create	index i_form_id_ca_search_form_labels on ca_search_form_labels (form_id);
create	index i_locale_id_ca_search_form_labels on ca_search_form_labels (locale_id);


/*==========================================================================*/
create table ca_search_form_placements (
	placement_id	serial,
	form_id		integer not null references ca_search_forms(form_id),
	
	bundle_name 	varchar(255) not null,
	rank			integer not null,
	settings		text not null
	
);
/*	KEY i_bundle_name (bundle_name),*/
/*	KEY i_rank (rank),*/
/*	KEY i_form_id (form_id)*/


/*==========================================================================*/
create table ca_search_forms_x_user_groups (
	relation_id 	serial,
	form_id 		integer not null references ca_search_forms(form_id),
	group_id 		integer not null references ca_user_groups(group_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);


/*==========================================================================*/
create table ca_search_forms_x_users (
	relation_id 	serial,
	form_id 		integer not null references ca_search_forms(form_id),
	user_id 		integer not null references ca_users(user_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);
/*	index i_form_id			(form_id),*/
/*	index i_user_id			(user_id)*/


/*==========================================================================*/
create table ca_search_log (
	search_id			serial,
	log_datetime		integer not null,
	user_id				integer null references ca_users(user_id),
	table_num			integer not null,
	search_expression	varchar(1024) not null,
	num_hits			integer not null,
	form_id				integer null references ca_search_forms(form_id),
	ip_addr				char(15) null,
	details				text not null,
	execution_time 		decimal(7,3) not null,
	search_source 		varchar(40) not null
	
);
/*	KEY i_log_datetime (log_datetime),*/
/*	KEY i_user_id (user_id),*/
/*	KEY i_form_id (form_id)*/


/*==========================================================================*/
create table ca_bundle_displays (
	display_id		serial primary key,
	user_id			integer null references ca_users(user_id),
	
	display_code	varchar(100) null,
	table_num		integer not null,
	
	is_system		integer not null,
	
	settings		text not null
	
);
/*	UNIQUE KEY u_display_code (display_code),*/
/*	KEY i_user_id (user_id),*/
/*	KEY i_table_num (table_num)*/


/*==========================================================================*/
create table ca_bundle_display_labels (
	label_id		serial,
	display_id		integer null references ca_bundle_displays(display_id),
	locale_id		smallint not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		text not null,
	is_preferred	integer not null
	
);
/*	KEY i_display_id (display_id),*/
/*	KEY i_locale_id (locale_id)*/


/*==========================================================================*/
create table ca_bundle_display_placements (
	placement_id	serial,
	display_id		integer not null references ca_bundle_displays(display_id),
	
	bundle_name 	varchar(255) not null,
	rank			integer not null,
	settings		text not null
	
);
/*	KEY i_bundle_name (bundle_name),*/
/*	KEY i_rank (rank),*/
/*	KEY i_display_id (display_id)*/


/*==========================================================================*/
create table ca_bundle_displays_x_user_groups (
	relation_id 	serial,
	display_id 		integer not null references ca_bundle_displays(display_id),
	group_id 		integer not null references ca_user_groups(group_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);
/*	index i_display_id			(display_id),*/
/*	index i_group_id			(group_id)*/


/*==========================================================================*/
create table ca_bundle_displays_x_users (
	relation_id 	serial,
	display_id 	integer not null references ca_bundle_displays(display_id),
	user_id 		integer not null references ca_users(user_id),
	access 			integer not null,
	
	primary key 				(relation_id)
);
/*	index i_display_id			(display_id),*/
/*	index i_user_id			(user_id)*/


/*==========================================================================*/
create table ca_bundle_mappings (
	mapping_id		serial primary key,
	
	direction		char(1) not null,
	table_num		integer not null,
	mapping_code	varchar(100) null,
	target			varchar(100) not null,
    access          integer not null,
	settings		text not null
	
);
/*	UNIQUE KEY u_mapping_code (mapping_code),*/
/*	KEY i_target (target)*/


/*==========================================================================*/
create table ca_bundle_mapping_labels (
	label_id		serial,
	mapping_id		integer null references ca_bundle_mappings(mapping_id),
	locale_id		smallint not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		text not null,
	is_preferred	integer not null
	
);
/*	KEY i_mapping_id (mapping_id),*/
/*	KEY i_locale_id (locale_id)*/


/*==========================================================================*/
create table ca_bundle_mapping_groups (
	group_id				serial primary key,
	mapping_id			integer not null references ca_bundle_mappings(mapping_id),
	
	group_code			varchar(100) not null,
	
	ca_base_path 			varchar(512) not null,
	external_base_path	varchar(512) not null,	
	
	settings			text not null,
	notes				text not null,
	rank                 integer not null
	
);
/*	KEY i_mapping_id (mapping_id),*/
/*	UNIQUE KEY i_group_code (group_code, mapping_id)*/


/*==========================================================================*/
create table ca_bundle_mapping_group_labels (
	label_id		serial,
	group_id		integer null references ca_bundle_mapping_groups(group_id),
	locale_id		smallint not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		text not null,
	is_preferred	integer not null
	
);
/*	KEY i_group_id (group_id),*/
/*	KEY i_locale_id (locale_id)*/


/*==========================================================================*/
create table ca_bundle_mapping_rules (
	rule_id				serial,
	group_id			integer not null references ca_bundle_mapping_groups(group_id),
	
	ca_path_suffix 				varchar(512) not null,
	external_path_suffix		varchar(512) not null,	
	
	settings			text not null,
	notes				text not null,
	
	rank                 integer not null
	
);
/*	KEY i_group_id (group_id)*/


/*==========================================================================*/
/* Support for tour content */
/*==========================================================================*/
create table ca_tours
(
   tour_id                       serial,
   tour_code                  varchar(100)                   not null,
   type_id                        integer                   null,
   rank                           integer              not null,
   color                          char(6)                        null,
   icon                           bytea                       not null,
   access                        integer               not null,
   status                         integer               not null,
   user_id                        integer                   null,
   primary key (tour_id),
   
   constraint fk_ca_tours_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
);

create index i_type_id_ca_tours on ca_tours(type_id);
create index i_user_id_ca_tours on ca_tours(user_id);
create index i_tour_code_ca_tours on ca_tours(tour_code);


/*==========================================================================*/
create table ca_tour_labels
(
   label_id                       serial,
   tour_id                        integer              not null,
   locale_id                      smallint              not null,
   name_sort                      varchar(255)                   not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_labels_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_tour_id_ca_tour_labels on ca_tour_labels(tour_id);
create index i_name_ca_tour_labels on ca_tour_labels(name);
create index i_name_sort_ca_tour_labels on ca_tour_labels(name_sort);
create unique index u_locale_id_ca_tour_labels on ca_tour_labels(tour_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops
(
   stop_id                       serial,
   parent_id                      integer,
   tour_id                        integer              not null,
   type_id                        integer                   null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   rank                           integer              not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_stop_id				integer 				not null,
   color                          char(6)                        null,
   icon                           bytea                       not null,
   access                         integer               not null,
   status                         integer               not null,
   deleted                        integer               not null,
   primary key (stop_id),
   
   constraint fk_ca_tour_stops_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
);

create index i_tour_id_ca_tour_stops on ca_tour_stops(tour_id);
create index i_type_id_ca_tour_stops on ca_tour_stops(type_id);
create index i_parent_id_ca_tour_stops on ca_tour_stops(parent_id);
create index i_hier_stop_id_ca_tour_stops on ca_tour_stops(hier_stop_id);
create index i_hier_left_ca_tour_stops on ca_tour_stops(hier_left);
create index i_hier_right_ca_tour_stops on ca_tour_stops(hier_right);
create index i_idno_ca_tour_stops on ca_tour_stops(idno);
create index i_idno_sort_ca_tour_stops on ca_tour_stops(idno_sort);


/*==========================================================================*/
create table ca_tour_stop_labels
(
   label_id                       serial,
   stop_id                        integer              not null,
   locale_id                      smallint              not null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_stop_labels_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stop_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
);

create index i_stop_id_ca_tour_stop_labels on ca_tour_stop_labels(stop_id);
create index i_name_ca_tour_stop_labels on ca_tour_stop_labels(name);
create index i_name_sort_ca_tour_stop_labels on ca_tour_stop_labels(name_sort);
create unique index u_locale_id_ca_tour_stop_labels on ca_tour_stop_labels(stop_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops_x_objects
(
   relation_id                    serial,
   object_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_object_id_ca_tour_stops_x_objects on  ca_tour_stops_x_objects(object_id);
create index i_stop_id_ca_tour_stops_x_objects on  ca_tour_stops_x_objects(stop_id);
create index i_type_id_ca_tour_stops_x_objects on  ca_tour_stops_x_objects(type_id);
create unique index u_all_ca_tour_stops_x_objects on  ca_tour_stops_x_objects
(
   object_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_objects on  ca_tour_stops_x_objects(label_left_id);
create index i_label_right_id_ca_tour_stops_x_objects on  ca_tour_stops_x_objects(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_entities
(
   relation_id                    serial,
   entity_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_entity_id_ca_tour_stops_x_entities on  ca_tour_stops_x_entities(entity_id);
create index i_stop_id_ca_tour_stops_x_entities on  ca_tour_stops_x_entities(stop_id);
create index i_type_id_ca_tour_stops_x_entities on  ca_tour_stops_x_entities(type_id);
create unique index u_all_ca_tour_stops_x_entities on  ca_tour_stops_x_entities
(
   entity_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_entities on  ca_tour_stops_x_entities(label_left_id);
create index i_label_right_id_ca_tour_stops_x_entities on  ca_tour_stops_x_entities(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_places
(
   relation_id                    serial,
   place_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_place_id_ca_tour_stops_x_places on  ca_tour_stops_x_places(place_id);
create index i_stop_id_ca_tour_stops_x_places on  ca_tour_stops_x_places(stop_id);
create index i_type_id_ca_tour_stops_x_places on  ca_tour_stops_x_places(type_id);
create unique index u_all_ca_tour_stops_x_places on  ca_tour_stops_x_places
(
   place_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_places on  ca_tour_stops_x_places(label_left_id);
create index i_label_right_id_ca_tour_stops_x_places on  ca_tour_stops_x_places(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_occurrences
(
   relation_id                    serial,
   occurrence_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_occurrence_id_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences(occurrence_id);
create index i_stop_id_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences(stop_id);
create index i_type_id_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences(type_id);
create unique index u_all_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences
(
   occurrence_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences(label_left_id);
create index i_label_right_id_ca_tour_stops_x_occurrences on  ca_tour_stops_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_collections
(
   relation_id                    serial,
   collection_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_collection_id_ca_tour_stops_x_collections on  ca_tour_stops_x_collections(collection_id);
create index i_stop_id_ca_tour_stops_x_collections on  ca_tour_stops_x_collections(stop_id);
create index i_type_id_ca_tour_stops_x_collections on  ca_tour_stops_x_collections(type_id);
create unique index u_all_ca_tour_stops_x_collections on  ca_tour_stops_x_collections
(
   collection_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_collections on  ca_tour_stops_x_collections(label_left_id);
create index i_label_right_id_ca_tour_stops_x_collections on  ca_tour_stops_x_collections(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_vocabulary_terms
(
   relation_id                    serial,
   item_id                      integer               not null,
   stop_id                      integer               not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_item_id_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms(item_id);
create index i_stop_id_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms(stop_id);
create index i_type_id_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms(type_id);
create unique index u_all_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms
(
   item_id,
   stop_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_tour_stops_x_vocabulary_terms on  ca_tour_stops_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_tour_stops_x_tour_stops
(
   relation_id                    serial,
   stop_left_id                 integer               not null,
   stop_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_tour_stops_stop_left_id foreign key (stop_left_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_stop_right_id foreign key (stop_right_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_right_id foreign key (label_right_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict
);

create index i_stop_left_id_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops(stop_left_id);
create index i_stop_right_id_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops(stop_right_id);
create index i_type_id_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops(type_id);
create unique index u_all_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops
(
   stop_left_id,
   stop_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops(label_left_id);
create index i_label_right_id_ca_tour_stops_x_tour_stops on ca_tour_stops_x_tour_stops(label_right_id);


/*==========================================================================*/
create table ca_storage_locations_x_storage_locations
(
   relation_id                    serial,
   location_left_id                 integer               not null,
   location_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_location_left_id_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations(location_left_id);
create index i_location_right_id_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations(location_right_id);
create index i_type_id_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations(type_id);
create unique index u_all_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations
(
   location_left_id,
   location_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations(label_left_id);
create index i_label_right_id_ca_storage_locations_x_storage_locations on ca_storage_locations_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_occurrences_x_storage_locations
(
   relation_id                    serial,
   occurrence_id                  integer               not null,
   location_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_occurrence_id_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations(occurrence_id);
create index i_location_id_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations(location_id);
create index i_type_id_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations(type_id);
create unique index u_all_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations
(
   occurrence_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations(label_left_id);
create index i_label_right_id_ca_occurrences_x_storage_locations on ca_occurrences_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_places_x_storage_locations
(
   relation_id                    serial,
   place_id                  integer               not null,
   location_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_place_id_ca_places_x_storage_locations on ca_places_x_storage_locations(place_id);
create index i_location_id_ca_places_x_storage_locations on ca_places_x_storage_locations(location_id);
create index i_type_id_ca_places_x_storage_locations on ca_places_x_storage_locations(type_id);
create unique index u_all_ca_places_x_storage_locations on ca_places_x_storage_locations
(
   place_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_places_x_storage_locations on ca_places_x_storage_locations(label_left_id);
create index i_label_right_id_ca_places_x_storage_locations on ca_places_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_loans_x_places
(
   relation_id                    serial,
   loan_id                        integer               not null,
   place_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_places on ca_loans_x_places(loan_id);
create index i_place_id_ca_loans_x_places on ca_loans_x_places(place_id);
create index i_type_id_ca_loans_x_places on ca_loans_x_places(type_id);
create unique index u_all_ca_loans_x_places on ca_loans_x_places
(
   loan_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_places on ca_loans_x_places(label_left_id);
create index i_label_right_id_ca_loans_x_places on ca_loans_x_places(label_right_id);


/*==========================================================================*/
create table ca_loans_x_occurrences
(
   relation_id                    serial,
   loan_id                        integer               not null,
   occurrence_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_occurrences on ca_loans_x_occurrences(loan_id);
create index i_occurrence_id_ca_loans_x_occurrences on ca_loans_x_occurrences(occurrence_id);
create index i_type_id_ca_loans_x_occurrences on ca_loans_x_occurrences(type_id);
create unique index u_all_ca_loans_x_occurrences on ca_loans_x_occurrences
(
   loan_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_occurrences on ca_loans_x_occurrences(label_left_id);
create index i_label_right_id_ca_loans_x_occurrences on ca_loans_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_loans_x_collections
(
   relation_id                    serial,
   loan_id                        integer               not null,
   collection_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_collections on ca_loans_x_collections(loan_id);
create index i_collection_id_ca_loans_x_collections on ca_loans_x_collections(collection_id);
create index i_type_id_ca_loans_x_collections on ca_loans_x_collections(type_id);
create unique index u_all_ca_loans_x_collections on ca_loans_x_collections
(
   loan_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_collections on ca_loans_x_collections(label_left_id);
create index i_label_right_id_ca_loans_x_collections on ca_loans_x_collections(label_right_id);


/*==========================================================================*/
create table ca_loans_x_storage_locations
(
   relation_id                    serial,
   loan_id                        integer               not null,
   location_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_storage_locations on ca_loans_x_storage_locations(loan_id);
create index i_location_id_ca_loans_x_storage_locations on ca_loans_x_storage_locations(location_id);
create index i_type_id_ca_loans_x_storage_locations on ca_loans_x_storage_locations(type_id);
create unique index u_all_ca_loans_x_storage_locations on ca_loans_x_storage_locations
(
   loan_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_storage_locations on ca_loans_x_storage_locations(label_left_id);
create index i_label_right_id_ca_loans_x_storage_locations on ca_loans_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_loans_x_vocabulary_terms
(
   relation_id                    serial,
   loan_id                        integer               not null,
   item_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms(loan_id);
create index i_item_id_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms(item_id);
create index i_type_id_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms(type_id);
create unique index u_all_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms
(
   loan_id,
   item_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_loans_x_vocabulary_terms on ca_loans_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_loans_x_object_lots
(
   relation_id                    serial,
   loan_id                        integer               not null,
   lot_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_id_ca_loans_x_object_lots on ca_loans_x_object_lots(loan_id);
create index i_lot_id_ca_loans_x_object_lots on ca_loans_x_object_lots(lot_id);
create index i_type_id_ca_loans_x_object_lots on ca_loans_x_object_lots(type_id);
create unique index u_all_ca_loans_x_object_lots on ca_loans_x_object_lots
(
   loan_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_object_lots on ca_loans_x_object_lots(label_left_id);
create index i_label_right_id_ca_loans_x_object_lots on ca_loans_x_object_lots(label_right_id);


/*==========================================================================*/
create table ca_loans_x_loans
(
   relation_id                    serial,
   loan_left_id                 integer               not null,
   loan_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_loan_left_id_ca_loans_x_loans on ca_loans_x_loans(loan_left_id);
create index i_loan_right_id_ca_loans_x_loans on ca_loans_x_loans(loan_right_id);
create index i_type_id_ca_loans_x_loans on ca_loans_x_loans(type_id);
create unique index u_all_ca_loans_x_loans on ca_loans_x_loans
(
   loan_left_id,
   loan_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_loans_x_loans on ca_loans_x_loans(label_left_id);
create index i_label_right_id_ca_loans_x_loans on ca_loans_x_loans(label_right_id);


/*==========================================================================*/
create table ca_movements_x_places
(
   relation_id                    serial,
   movement_id                        integer               not null,
   place_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_id_ca_movements_x_places on ca_movements_x_places(movement_id);
create index i_place_id_ca_movements_x_places on ca_movements_x_places(place_id);
create index i_type_id_ca_movements_x_places on ca_movements_x_places(type_id);
create unique index u_all_ca_movements_x_places on ca_movements_x_places
(
   movement_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_places on ca_movements_x_places(label_left_id);
create index i_label_right_id_ca_movements_x_places on ca_movements_x_places(label_right_id);


/*==========================================================================*/
create table ca_movements_x_occurrences
(
   relation_id                    serial,
   movement_id                        integer               not null,
   occurrence_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_id_ca_movements_x_occurrences on ca_movements_x_occurrences(movement_id);
create index i_occurrence_id_ca_movements_x_occurrences on ca_movements_x_occurrences(occurrence_id);
create index i_type_id_ca_movements_x_occurrences on ca_movements_x_occurrences(type_id);
create unique index u_all_ca_movements_x_occurrences on ca_movements_x_occurrences
(
   movement_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_occurrences on ca_movements_x_occurrences(label_left_id);
create index i_label_right_id_ca_movements_x_occurrences on ca_movements_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_movements_x_collections
(
   relation_id                    serial,
   movement_id                        integer               not null,
   collection_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_id_ca_movements_x_collections on ca_movements_x_collections(movement_id);
create index i_collection_id_ca_movements_x_collections on ca_movements_x_collections(collection_id);
create index i_type_id_ca_movements_x_collections on ca_movements_x_collections(type_id);
create unique index u_all_ca_movements_x_collections on ca_movements_x_collections
(
   movement_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_collections on ca_movements_x_collections(label_left_id);
create index i_label_right_id_ca_movements_x_collections on ca_movements_x_collections(label_right_id);


/*==========================================================================*/
create table ca_movements_x_storage_locations
(
   relation_id                    serial,
   movement_id                        integer               not null,
   location_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_id_ca_movements_x_storage_locations on ca_movements_x_storage_locations(movement_id);
create index i_location_id_ca_movements_x_storage_locations on ca_movements_x_storage_locations(location_id);
create index i_type_id_ca_movements_x_storage_locations on ca_movements_x_storage_locations(type_id);
create unique index u_all_ca_movements_x_storage_locations on ca_movements_x_storage_locations
(
   movement_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_storage_locations on ca_movements_x_storage_locations(label_left_id);
create index i_label_right_id_ca_movements_x_storage_locations on ca_movements_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_movements_x_vocabulary_terms
(
   relation_id                    serial,
   movement_id                        integer               not null,
   item_id                       integer                   not null,
   type_id                        smallint              not null,
   source_info                    text                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_id_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms(movement_id);
create index i_item_id_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms(item_id);
create index i_type_id_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms(type_id);
create unique index u_all_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms
(
   movement_id,
   item_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms(label_left_id);
create index i_label_right_id_ca_movements_x_vocabulary_terms on ca_movements_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_movements_x_movements
(
   relation_id                    serial,
   movement_left_id                 integer               not null,
   movement_right_id                integer               not null,
   type_id                        smallint              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  integer                   null,
   label_right_id                 integer                   null,
   rank                           integer                   not null,
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
);

create index i_movement_left_id_ca_movements_x_movements on ca_movements_x_movements(movement_left_id);
create index i_movement_right_id_ca_movements_x_movements on ca_movements_x_movements(movement_right_id);
create index i_type_id_ca_movements_x_movements on ca_movements_x_movements(type_id);
create unique index u_all_ca_movements_x_movements on ca_movements_x_movements
(
   movement_left_id,
   movement_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id_ca_movements_x_movements on ca_movements_x_movements(label_left_id);
create index i_label_right_id_ca_movements_x_movements on ca_movements_x_movements(label_right_id);


/*==========================================================================*/
create table ca_mysql_fulltext_search (
	index_id			serial,
	
	table_num			integer 	not null,
	row_id				integer 		not null,
	
	field_table_num		integer	not null,
	field_num			integer	not null,
	field_row_id		integer		not null,
	rel_type_id		smallint not null default '0',
	
	fieldtext			text 			not null,
	
	boost				integer 				not null default 1,
	
	PRIMARY KEY								(index_id)
);
/*	FULLTEXT INDEX		f_fulltext			(fieldtext),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_boost				(boost),
	INDEX				i_field_row_id		(field_row_id),
	INDEX				i_rel_type_id		(rel_type_id)	*/


/*==========================================================================*/
create table ca_did_you_mean_phrases (
	phrase_id			serial,
	
	table_num			integer 	not null,
	
	phrase				varchar(255) 		not null,
	num_words			integer	not null,
	
	PRIMARY KEY			(phrase_id)
	
);
/*	INDEX				i_table_num			(table_num)
	INDEX				i_num_words			(num_words),
	UNIQUE INDEX		u_all				(table_num, phrase)*/


/*==========================================================================*/
create table ca_did_you_mean_ngrams (
	phrase_id			serial,
	ngram				varchar(255)		not null,
	endpoint			integer	not null
	
);
/*	INDEX				i_phrase_id			(phrase_id),
	INDEX				i_ngram				(ngram)*/


/*==========================================================================*/
create table ca_watch_list
(
   watch_id                       serial,
   table_num                      integer               not null,
   row_id                         integer                   not null,
   user_id                        integer                   not null,
   primary key (watch_id),
   
   constraint fk_ca_watch_list_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
);

create index i_row_id_ca_watch_list on ca_watch_list(row_id, table_num);
create index i_user_id_ca_watch_list on ca_watch_list(user_id);
create unique index u_all_ca_watch_list on ca_watch_list(row_id, table_num, user_id);


/*==========================================================================*/
create table ca_user_notes
(
   note_id                       serial,
   table_num                     integer               not null,
   row_id                        integer                   not null,
   user_id                       integer                   not null,
   bundle_name                   varchar(255)                   not null,
   note                          text                       not null,
   created_on                    integer                   not null,
   primary key (note_id),
   
   constraint fk_ca_user_notes_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
);

create index i_row_id_ca_user_notes on ca_user_notes(row_id, table_num);
create index i_user_id_ca_user_notes on ca_user_notes(user_id);
create index i_bundle_name_ca_user_notes on ca_user_notes(bundle_name);


/*==========================================================================*/
create table ca_bookmark_folders 
(
  folder_id integer not null,
  name varchar(255) not null,
  user_id integer not null references ca_users(user_id),
  rank smallint not null,
  
  primary key (folder_id)
);

create index i_user_id_ca_bookmark_folders on ca_bookmark_folders(user_id);


/*==========================================================================*/
create table ca_bookmarks 
(
  bookmark_id integer not null,
  folder_id integer not null references ca_bookmark_folders(folder_id),
  table_num integer not null,
  row_id integer not null,
  notes text not null,
  rank smallint not null,
  created_on integer not null,
  
  primary key (bookmark_id)
);

create index i_row_id_ca_bookmarks on ca_bookmarks(row_id);
create index i_folder_id_ca_bookmarks on ca_bookmarks(folder_id);


/*==========================================================================*/
create table ca_commerce_transactions 
(
  transaction_id integer not null,
  user_id integer not null,
  short_description text not null,
  notes text not null,
  created_on integer not null,
  
  primary key (transaction_id),
  
   constraint fk_ca_commerce_transactions_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
);

create index i_user_id_ca_commerce_transactions on ca_commerce_transactions(user_id);


/*==========================================================================*/
create table ca_commerce_communications
(
  communication_id serial,
  transaction_id integer not null,
  source char(1) not null, 
  created_on integer not null,
  subject varchar(255) not null,
  message text not null,
  set_id integer null,
  
  primary key (communication_id),
   constraint fk_ca_commerce_communications_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_communications_transaction_id foreign key (transaction_id)
      references ca_commerce_transactions (transaction_id) on delete restrict on update restrict
);

create index i_transaction_id_ca_commerce_communications on ca_commerce_communications(transaction_id);
create index i_set_id_ca_commerce_communications on ca_commerce_communications(set_id);


/*==========================================================================*/
create table ca_commerce_orders
(
  order_id serial,
  transaction_id integer not null,
  created_on integer not null,
  
  order_status integer not null,	
   
  shipping_fname varchar(255) not null,
  shipping_lname varchar(255) not null,
  shipping_organization varchar(255) not null,
  shipping_address1 varchar(255) not null,
  shipping_address2 varchar(255) not null,
  shipping_city varchar(255) not null,
  shipping_zone varchar(255) not null,
  shipping_postal_code varchar(255) not null,
  shipping_country varchar(255) not null,
  shipping_phone varchar(255) not null,
  shipping_fax varchar(255) not null,
  shipping_email varchar(255) not null,
  
  billing_fname varchar(255) not null,
  billing_lname varchar(255) not null,
  billing_organization varchar(255) not null,
  billing_address1 varchar(255) not null,
  billing_address2 varchar(255) not null,
  billing_city varchar(255) not null,
  billing_zone varchar(255) not null,
  billing_postal_code varchar(255) not null,
  billing_country varchar(255) not null,
  billing_phone varchar(255) not null,
  billing_fax varchar(255) not null,
  billing_email varchar(255) not null,
  
  payment_method varchar(100) null,
  payment_status integer not null,
  payment_details bytea not null,
  payment_response bytea not null,
  payment_received_on integer null,
  
  shipping_method integer null,	
  shipping_cost decimal(8,2) null,
  handling_cost decimal(8,2) null,
  shipping_notes text not null,
  shipping_date integer null,
  shipped_on_date integer null,
  
  primary key (order_id),
   constraint fk_ca_commerce_orders_transaction_id foreign key (transaction_id)
      references ca_commerce_transactions (transaction_id) on delete restrict on update restrict
);

create index i_transaction_id_ca_commerce_orders on ca_commerce_orders(transaction_id);


/*==========================================================================*/
create table ca_commerce_order_items
(
   item_id                        serial,
   order_id                       integer                   not null,
   object_id                      integer                   null,
   service                    	  integer                   null,	
   fullfillment_method      integer                   null,	
   fee                               decimal(8,2) null,
   tax                               decimal(8,2) null,
   notes                            text                                 not null,
   restrictions                   text                                 not null,
   rank                           integer                   not null,
   primary key (item_id),
   constraint fk_ca_commerce_order_items_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_order_items_order_id foreign key (order_id)
      references ca_commerce_orders (order_id) on delete restrict on update restrict
);

create index i_object_id_ca_commerce_order_items on ca_commerce_order_items(object_id);
create index i_order_id_ca_commerce_order_items on ca_commerce_order_items(order_id);


/*==========================================================================*/
create table ca_commerce_fulfillment_events
(
   event_id                       serial,
   order_id                       integer                   not null,
   item_id                        integer                   null,
   fullfillment_method     integer              null,		
   fullfillment_details       bytea not null,								
   occurred_on                integer not null,
   notes                          text not null,
   
   primary key (event_id),
   constraint fk_ca_commerce_fulfillment_events_order_id foreign key (order_id)
      references ca_commerce_orders (order_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_fulfillment_events_item_id foreign key (item_id)
      references ca_commerce_order_items (item_id) on delete restrict on update restrict
);

create index i_order_id_ca_commerce_fulfillment_events on ca_commerce_fulfillment_events(order_id);
create index i_item_id_ca_commerce_fulfillment_events on ca_commerce_fulfillment_events(item_id);


/*==========================================================================*/
create table ca_sql_search_words 
(
  word_id integer not null,
  word varchar(255) not null,
  stem varchar(255) not null,
  locale_id smallint default null,
  
  primary key (word_id)
);

create unique index u_word_ca_sql_search_words on ca_sql_search_words(word);
create index i_stem_ca_sql_search_words on ca_sql_search_words(stem);
create index i_locale_id_ca_sql_search_words on ca_sql_search_words(locale_id);


/*==========================================================================*/
create table ca_sql_search_word_index (
  table_num smallint,
  row_id integer not null,
  field_table_num integer not null,
  field_num integer not null,
  field_row_id integer not null,
  rel_type_id smallint not null default '0',
  word_id integer not null,
  boost integer not null default '1',
  access integer not null default '1'
);

create index i_row_id_ca_sql_search_word_index on ca_sql_search_word_index(row_id, table_num);
create index i_word_id_ca_sql_search_word_index on ca_sql_search_word_index(word_id, access);
create index i_field_row_id_ca_sql_search_word_index on ca_sql_search_word_index(field_row_id, field_table_num);
create index i_rel_type_id_ca_sql_search_word_index on ca_sql_search_word_index(rel_type_id);


/*==========================================================================*/
create table ca_sql_search_ngrams (
  word_id integer not null,
  ngram char(4) not null,
  seq integer not null,
  
  primary key (word_id,seq)
);

create index i_ngram_ca_sql_search_ngrams on ca_sql_search_ngrams(ngram);


/*==========================================================================*/
/* Schema update tracking                                                   */
/*==========================================================================*/
create table ca_schema_updates (
	version_num		integer not null,
	datetime		integer not null
	
);
