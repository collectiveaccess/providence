/*==========================================================================*/
create table ca_locales
(
   locale_id                      smallint unsigned              not null AUTO_INCREMENT,
   name                           varchar(255)                   not null,
   language                       varchar(3)                     not null,
   country                        char(2)                        not null,
   dialect                        varchar(8),
   dont_use_for_cataloguing	tinyint unsigned not null,
   primary key (locale_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index u_language_country on ca_locales(language, country);


/*==========================================================================*/
create table ca_application_vars
(
   vars                           longtext                       not null
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_change_log
(
   log_id                         bigint                         not null AUTO_INCREMENT,
   log_datetime                   int unsigned                   not null,
   user_id                        int unsigned,
   changetype                     char(1)                        not null,
   logged_table_num               tinyint unsigned               not null,
   logged_row_id                  int unsigned                   not null,
   rolledback                     tinyint unsigned               not null default 0,
   unit_id                        char(32),
   batch_id                       int unsigned                   null,
   primary key (log_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_datetime on ca_change_log(log_datetime);
create index i_user_id on ca_change_log(user_id);
create index i_logged on ca_change_log(logged_row_id, logged_table_num);
create index i_unit_id on ca_change_log(unit_id);
create index i_table_num on ca_change_log (logged_table_num);
create index i_batch_id on ca_change_log (batch_id);
CREATE INDEX i_date_unit on ca_change_log(log_datetime, unit_id); 
create index i_created_on on ca_change_log(logged_table_num, changetype, log_datetime);
create index i_modified_on on ca_change_log(logged_table_num, log_datetime);


/*==========================================================================*/
create table ca_change_log_snapshots (
	log_id                         bigint                         not null,
    snapshot                       longblob                       not null,
    
   constraint fk_ca_change_log_snaphots_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
create index i_log_id on ca_change_log_snapshots (log_id);


/*==========================================================================*/
create table ca_change_log_subjects
(
   log_id                         bigint                         not null,
   subject_table_num              tinyint unsigned               not null,
   subject_row_id                 int unsigned                   not null,
   
   constraint fk_ca_change_log_subjects_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_log_id on ca_change_log_subjects(log_id);
create index i_subject on ca_change_log_subjects(subject_row_id, subject_table_num);
CREATE INDEX i_log_plus on ca_change_log_subjects (log_id, subject_table_num, subject_row_id);
create index i_modified_on on ca_change_log_subjects(log_id, subject_table_num);


/*==========================================================================*/
create table ca_eventlog
(
   date_time                      int unsigned                   not null,
   code                           CHAR(4)                        not null,
   message                        text                           not null,
   source                         varchar(255)                   not null
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_when on ca_eventlog(date_time);
create index i_source on ca_eventlog(source);


/*==========================================================================*/
create table ca_lists
(
   list_id                        smallint unsigned              not null AUTO_INCREMENT,
   list_code                      varchar(100)                   not null,
   is_system_list                 tinyint unsigned               not null default 0,
   is_hierarchical                tinyint unsigned               not null default 0,
   use_as_vocabulary              tinyint unsigned               not null default 0,
   default_sort                   tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   primary key (list_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_code on ca_lists(list_code);


/*==========================================================================*/
create table ca_list_labels
(
   label_id                       smallint unsigned              not null AUTO_INCREMENT,
   list_id                        smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_list_labels_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_list_id on ca_list_labels(list_id);
create index i_name on ca_list_labels(name(128));
create unique index u_locale_id on ca_list_labels(list_id, locale_id);


/*==========================================================================*/
create table ca_list_items
(
   item_id                        int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   list_id                        smallint unsigned              not null,
   type_id                        int unsigned                   null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   item_value                     varchar(255)                   not null,
   `rank`                           int unsigned              not null default 0,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   is_enabled                     tinyint unsigned               not null default 0,
   is_default                     tinyint unsigned               not null default 0,
   validation_format              varchar(255)                   not null,
   settings                       longtext                       not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   primary key (item_id),
   
   constraint fk_ca_list_items_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_list_items_parent_id foreign key (parent_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_list_id on ca_list_items(list_id);
create index i_parent_id on ca_list_items(parent_id);
create index i_idno on ca_list_items(idno);
create index i_idno_sort on ca_list_items(idno_sort);
create index i_idno_sort_num on ca_list_items(idno_sort_num);
create index i_hier_left on ca_list_items(hier_left);
create index i_hier_right on ca_list_items(hier_right);
create index i_value_text on ca_list_items(item_value);
create index i_type_id on ca_list_items(type_id);
create index i_source_id on ca_list_items(source_id);
create index i_item_filter on ca_list_items(item_id, deleted, access); 


/*==========================================================================*/
create table ca_list_item_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   item_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name_singular                  varchar(255)                   not null,
   name_plural                    varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   description                    text                           not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null default 0,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_list_item_labels_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_list_item_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_name_singular on ca_list_item_labels
(
   item_id,
   name_singular(128)
);
create index i_name on ca_list_item_labels
(
   item_id,
   name_plural(128)
);
create index i_item_id on ca_list_item_labels(item_id);
create unique index u_all on ca_list_item_labels
(
   item_id,
   name_singular,
   name_plural,
   type_id,
   locale_id
);
create index i_name_sort on ca_list_item_labels(name_sort(128));
create index i_type_id on ca_list_item_labels(type_id);
create index i_effective_date ON ca_list_item_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_users
(
   user_id                        int unsigned                   not null AUTO_INCREMENT,
   user_name                      varchar(255)                   not null,
   userclass                      tinyint unsigned               not null,
   password                       varchar(100)                   not null,
   fname                          varchar(255)                   not null,
   lname                          varchar(255)                   not null,
   email                          varchar(255)                   not null,
   sms_number                     varchar(30)                    not null,
   vars                           longtext                       not null,
   volatile_vars                  text                           not null,
   active                         tinyint unsigned               not null,
   confirmed_on                   int unsigned,
   confirmation_key               char(32),
   registered_on                  int unsigned,
   entity_id                      int unsigned,
   primary key (user_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_user_name on ca_users(user_name);
create unique index u_confirmation_key on ca_users(confirmation_key);
create index i_userclass on ca_users(userclass);
create index i_entity_id on ca_users(entity_id);


/*==========================================================================*/
create table ca_user_groups
(
   group_id                       int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   for_public_use                 tinyint unsigned               not null default 0,
   user_id                        int unsigned                   null,
   `rank`                         smallint unsigned              not null default 0,
   vars                           text                           not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   primary key (group_id),
      
   constraint fk_ca_user_groups_parent_id foreign key (parent_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
      
   constraint fk_ca_user_groups_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_hier_left on ca_user_groups(hier_left);
create index i_hier_right on ca_user_groups(hier_right);
create index i_parent_id on ca_user_groups(parent_id);
create index i_user_id on ca_user_groups(user_id);
create unique index u_name on ca_user_groups(name);
create unique index u_code on ca_user_groups(code);


/*==========================================================================*/
create table ca_user_roles
(
   role_id                        smallint unsigned              not null AUTO_INCREMENT,
   name                           varchar(255)                   not null,
   code                           varchar(20)                    not null,
   description                    text                           not null,
   `rank`                           smallint unsigned              not null default 0,
   vars                           longtext                       not null,
   field_access                   longtext                       not null,
   primary key (role_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_name on ca_user_roles(name);
create unique index u_code on ca_user_roles(code);


/*==========================================================================*/
create table if not exists ca_media_upload_sessions (
   session_id                int unsigned                   not null AUTO_INCREMENT,
   user_id                   int unsigned                   not null,
   session_key               char(36)                       not null,
   created_on                int unsigned                   not null,
   submitted_on              int unsigned                   null,
   completed_on              int unsigned                   null,
   last_activity_on          int unsigned                   null,
   error_code                smallint unsigned              not null default 0,
   source                    varchar(30)                    not null default 'UPLOADER',
   status                    varchar(30)                    not null default 'IN_PROGRESS',
   
   num_files		         int unsigned                   not null,
   total_bytes		         bigint unsigned                not null default 0,
   metadata		             longtext                       null,
   
   primary key (session_id),

   index i_session_id               (session_id),
   index i_created_on			    (created_on),
   index i_completed_on			    (completed_on),
   index i_last_activity_on			(last_activity_on),
   index i_error_code      	        (error_code),
   index i_status   	            (status),
   unique index i_session_key      	(session_key),
  
   constraint fk_ca_media_upload_sessions_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table if not exists ca_media_upload_session_files (
   file_id                   int unsigned                   not null AUTO_INCREMENT,
   session_id                int unsigned                   not null,
   created_on                int unsigned                   not null,
   completed_on              int unsigned                   null,
   last_activity_on          int unsigned                   null,
   filename                  varchar(1024)                  not null,
   
   bytes_received		     bigint unsigned                not null default 0,
   total_bytes		         bigint unsigned                not null default 0,
   error_code                smallint unsigned              not null default 0,
   
   primary key (file_id),

   index i_session_id               (session_id),
   index i_created_on			    (created_on),
   index i_completed_on			    (completed_on),
   index i_last_activity_on			(last_activity_on),
   index i_error_code      	        (error_code),
   
   constraint fk_ca_media_upload_session_files_session_id foreign key (session_id)
      references ca_media_upload_sessions (session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_entities
(
   entity_id                      int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   source_id                      int unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_info                    longtext                       not null,
   life_sdatetime                 decimal(30,20),
   life_edatetime                 decimal(30,20),
   hier_entity_id                 int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                         int unsigned                   not null default 0,
   submission_user_id             int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id           int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   
   primary key (entity_id),
   constraint fk_ca_entities_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_parent_id foreign key (parent_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_entities_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_entities_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_entities_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_source_id on ca_entities(source_id);
create index i_type_id on ca_entities(type_id);
create index i_idno on ca_entities(idno);
create index i_idno_sort on ca_entities(idno_sort);
create index i_idno_sort_num on ca_entities(idno_sort_num);
create index i_hier_entity_id on ca_entities(hier_entity_id);
create index i_locale_id on ca_entities(locale_id);
create index i_parent_id on ca_entities(parent_id);
create index i_hier_left on ca_entities(hier_left);
create index i_hier_right on ca_entities(hier_right);
create index i_life_sdatetime on ca_entities(life_sdatetime);
create index i_life_edatetime on ca_entities(life_edatetime);
create index i_view_count on ca_entities(view_count);
create index i_entity_filter on ca_entities(entity_id, deleted, access);
create index i_submission_user_id on ca_entities(submission_user_id);
create index i_submission_group_id on ca_entities(submission_group_id);
create index i_submission_status_id on ca_entities(submission_status_id);
create index i_submission_via_form on ca_entities(submission_via_form);
create index i_submission_session_id on ca_entities(submission_session_id);

alter table ca_users add constraint fk_ca_users_entity_id foreign key (entity_id) references ca_entities (entity_id) on delete restrict on update restrict;


/*==========================================================================*/
create table ca_metadata_elements
(
   element_id                     smallint unsigned              not null AUTO_INCREMENT,
   parent_id                      smallint unsigned,
   list_id                        smallint unsigned,
   element_code                   varchar(30)                    not null,
   documentation_url              varchar(255)                   not null,
   datatype                       tinyint unsigned               not null,
   settings                       longtext                       not null,
   `rank`                           smallint unsigned              not null default 0,
   deleted              tinyint unsigned     not null default 0,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_element_id                smallint unsigned              null,
   primary key (element_id),
   
   constraint fk_ca_metadata_elements_list_id foreign key (list_id)
      references ca_lists (list_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_elements_parent_id foreign key (parent_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_hier_element_id on ca_metadata_elements(hier_element_id);
create unique index u_name_short on ca_metadata_elements(element_code);
create index i_parent_id on ca_metadata_elements(parent_id);
create index i_hier_left on ca_metadata_elements(hier_left);
create index i_hier_right on ca_metadata_elements(hier_right);
create index i_list_id on ca_metadata_elements(list_id);
create index i_deleted on ca_metadata_elements(deleted);


/*==========================================================================*/
create table ca_metadata_element_labels
(
   label_id                       smallint unsigned              not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(255)                   not null,
   description                    text                           not null,
   is_preferred                   tinyint unsigned               not null,
   primary key (label_id),
   
   constraint fk_ca_metadata_element_labels_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_element_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_element_id on ca_metadata_element_labels(element_id);
create index i_name on ca_metadata_element_labels(name(128));
create index i_locale_id on ca_metadata_element_labels(locale_id);


/*==========================================================================*/
create table ca_metadata_type_restrictions
(
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   element_id                     smallint unsigned              not null,
   settings                       longtext                       not null,
   include_subtypes               tinyint unsigned               not null default 0,
   `rank`                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   constraint fk_ca_metadata_type_restrictions_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_table_num on ca_metadata_type_restrictions(table_num);
create index i_type_id on ca_metadata_type_restrictions(type_id);
create index i_element_id on ca_metadata_type_restrictions(element_id);
create index i_include_subtypes on ca_metadata_type_restrictions(include_subtypes);


/*==========================================================================*/
create table ca_multipart_idno_sequences
(
   idno_stub                      varchar(255)                   not null,
   format                         varchar(100)                   not null,
   element                        varchar(100)                   not null,
   seq                            int unsigned                   not null,
   primary key (idno_stub, format, element)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_storage_locations
(
   location_id                    int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   type_id                        int unsigned,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   color                          char(6)                        null,
   icon                           longblob                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   is_enabled                     tinyint unsigned               not null default 1,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   
   primary key (location_id),
   constraint fk_ca_storage_locations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_parent_id foreign key (parent_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_storage_locations_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_storage_locations_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_storage_locations_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_storage_locations(parent_id);
create index i_source_id on ca_storage_locations(source_id);
create index i_idno on ca_storage_locations(idno);
create index i_idno_sort on ca_storage_locations(idno_sort);
create index i_idno_sort_num on ca_storage_locations(idno_sort_num);
create index i_type_id on ca_storage_locations(type_id);
create index i_hier_left on ca_storage_locations(hier_left);
create index i_hier_right on ca_storage_locations(hier_right);
create index i_view_count on ca_storage_locations(view_count);
create index i_loc_filter on ca_storage_locations(location_id, deleted, access); 
create index i_submission_user_id on ca_storage_locations(submission_user_id);
create index i_submission_group_id on ca_storage_locations(submission_group_id);
create index i_submission_status_id on ca_storage_locations(submission_status_id);
create index i_submission_via_form on ca_storage_locations(submission_via_form);
create index i_submission_session_id on ca_storage_locations(submission_session_id);


/*==========================================================================*/
create table ca_object_lots
(
   lot_id                         int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   lot_status_id                  int unsigned                   not null,
   idno_stub                      varchar(255)                   not null,
   idno_stub_sort                 varchar(255)                   not null,
   idno_stub_sort_num             bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   extent                         smallint unsigned              not null,
   extent_units                   varchar(255)                   not null,
   access                         tinyint                        not null default 0,
   status                         tinyint unsigned               not null default 0,
   home_location_id               int unsigned null,
   accession_sdatetime            decimal(30,20),
   accession_edatetime            decimal(30,20),
   deaccession_sdatetime          decimal(30,20),
   deaccession_edatetime          decimal(30,20),
   deaccession_disposal_sdatetime decimal(30,20),
   deaccession_disposal_edatetime decimal(30,20),
   is_deaccessioned               tinyint                        not null default 0,
   deaccession_notes              text                           not null,
   deaccession_authorized_by      varchar(255)                   not null default '',
   deaccession_type_id            int unsigned                   null,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   primary key (lot_id),
   
   constraint fk_ca_object_lots_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
    constraint fk_ca_object_lots_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_lot_status_id foreign key (lot_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   
   constraint fk_ca_object_lots_deaccession_type_id foreign key (deaccession_type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_object_lots_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_object_lots_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_object_lots_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_admin_idno_stub on ca_object_lots(idno_stub);
create index i_type_id on ca_object_lots(type_id);
create index i_source_id on ca_object_lots(source_id);
create index i_admin_idno_stub_sort on ca_object_lots(idno_stub_sort);
create index i_lot_status_id on ca_object_lots(lot_status_id);
create index i_view_count on ca_object_lots(view_count);
create index i_lot_filter on ca_object_lots(lot_id, deleted, access); 
create index i_home_location_id on ca_object_lots(home_location_id);
create index i_accession_sdatetime on ca_object_lots(accession_sdatetime);
create index i_accession_edatetime on ca_object_lots(accession_edatetime);
create index i_deaccession_sdatetime on ca_object_lots(deaccession_sdatetime);
create index i_deaccession_edatetime on ca_object_lots(deaccession_edatetime);
create index i_deaccession_disposal_sdatetime on ca_object_lots(deaccession_disposal_sdatetime);
create index i_deaccession_disposal_edatetime on ca_object_lots(deaccession_disposal_edatetime);
create index i_deaccession_auth_by on ca_object_lots(deaccession_authorized_by);
create index i_deaccession_type_id on ca_object_lots(deaccession_type_id);
create index i_is_deaccessioned on ca_object_lots(is_deaccessioned);
create index i_submission_user_id on ca_object_lots(submission_user_id);
create index i_submission_group_id on ca_object_lots(submission_group_id);
create index i_submission_status_id on ca_object_lots(submission_status_id);
create index i_submission_via_form on ca_object_lots(submission_via_form);
create index i_submission_session_id on ca_object_lots(submission_session_id);


/*==========================================================================*/
create table ca_object_representations
(
   representation_id              int unsigned                   not null AUTO_INCREMENT,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   md5                            varchar(32)                    not null,
   mimetype                       varchar(255)                   null,
   media_class                    varchar(20)                    null,
   original_filename              varchar(1024)                  not null,
   media                          longblob                       not null,
   media_metadata                 longblob                       null,
   media_content                  longtext                       null,
   deleted                        tinyint unsigned               not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   home_location_id               int unsigned null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   submission_user_id             int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id           int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   is_transcribable               tinyint unsigned               not null default 0,
   
   primary key (representation_id),
   constraint fk_ca_object_representations_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   
   constraint fk_ca_object_representations_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_reps_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_object_reps_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_object_reps_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_object_reps_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_locale_id on ca_object_representations(locale_id);
create index i_type_id on ca_object_representations(type_id);
create index i_idno on ca_object_representations(idno);
create index i_idno_sort on ca_object_representations(idno_sort);
create index i_idno_sort_num on ca_object_representations(idno_sort_num);
create index i_md5 on ca_object_representations(md5);
create index i_mimetype on ca_object_representations(mimetype);
create index i_original_filename on ca_object_representations(original_filename(128));
create index i_rank on ca_object_representations(`rank`);
create index i_source_id on ca_object_representations(source_id);
create index i_view_count on ca_object_representations(view_count);
create index i_rep_filter on ca_object_representations(representation_id, deleted, access); 
create index i_submission_user_id on ca_object_representations(submission_user_id);
create index i_submission_group_id on ca_object_representations(submission_group_id);
create index i_submission_status_id on ca_object_representations(submission_status_id);
create index i_submission_via_form on ca_object_representations(submission_via_form);
create index i_submission_session_id on ca_object_representations(submission_session_id);
create index i_is_transcribable on ca_object_representations(is_transcribable);
create index i_home_location_id on ca_object_representations(home_location_id);
create index i_media_class on ca_object_representations(media_class);


/*==========================================================================*/
create table ca_object_representation_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   
   constraint fk_ca_object_representation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_labels_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representation_labels(representation_id);
create index i_name on ca_object_representation_labels(name(128));
create unique index u_all on ca_object_representation_labels(
   representation_id,
   name(255),
   type_id,
   locale_id
);
create index i_locale_id on ca_object_representation_labels(locale_id);
create index i_name_sort on ca_object_representation_labels(name_sort(255));
create index i_type_id on ca_object_representation_labels(type_id);
create index i_effective_date ON ca_object_representation_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_object_representation_multifiles (
	multifile_id		int unsigned not null auto_increment,
	representation_id	int unsigned not null,
	resource_path		text not null,
	media				longblob not null,
	media_metadata		longblob not null,
	media_content		longtext not null,
	`rank`				int unsigned not null default 0,	
	primary key (multifile_id),
	
   constraint fk_ca_object_representation_mf_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_resource_path on ca_object_representation_multifiles(resource_path(255));
create index i_representation_id on ca_object_representation_multifiles(representation_id);


/*==========================================================================*/
create table ca_object_representation_captions (
	caption_id			int unsigned not null auto_increment,
	representation_id	int unsigned not null,
	locale_id			smallint unsigned not null,
	caption_file		longblob not null,
	caption_content		longtext not null,
	primary key (caption_id),
      
    index i_representation_id	(representation_id),
    index i_locale_id			(locale_id),
   constraint fk_ca_object_rep_captions_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representation_cap_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_object_representation_sidecars (
	sidecar_id			int unsigned not null auto_increment,
	representation_id	int unsigned not null,
	sidecar_file		longblob not null,
	sidecar_content		longtext not null,
	notes               text not null,
    mimetype            varchar(255) null,
	primary key (sidecar_id),
      
    index i_representation_id	(representation_id),
    
   constraint fk_ca_object_representation_sc_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_media_content_locations
(
   table_num                      tinyint unsigned            not null,
   row_id                         int unsigned                not null,
   content                        text                        not null,
   loc                            longtext                    not null
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_media_content_locations(row_id, table_num);
create index i_content on ca_media_content_locations(content(255));


/*==========================================================================*/
create table ca_occurrences
(
   occurrence_id                  int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   hier_occurrence_id             int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   primary key (occurrence_id),
   
   constraint fk_ca_occurrences_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_parent_id foreign key (parent_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict,
      
   constraint fk_ca_occurrences_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_occurrences_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_occurrences_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_occurrences_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_occurrences(parent_id);
create index i_source_id on ca_occurrences(source_id);
create index i_type_id on ca_occurrences(type_id);
create index i_locale_id on ca_occurrences(locale_id);
create index i_idno on ca_occurrences(idno);
create index i_idno_sort on ca_occurrences(idno_sort);
create index i_idno_sort_num on ca_occurrences(idno_sort_num);
create index i_hier_left on ca_occurrences(hier_left);
create index i_hier_right on ca_occurrences(hier_right);
create index i_hier_occurrence_id on ca_occurrences(hier_occurrence_id);
create index i_view_count on ca_occurrences(view_count);
create index i_occ_filter on ca_occurrences(occurrence_id, deleted, access); 
create index i_submission_user_id on ca_occurrences(submission_user_id);
create index i_submission_group_id on ca_occurrences(submission_group_id);
create index i_submission_status_id on ca_occurrences(submission_status_id);
create index i_submission_via_form on ca_occurrences(submission_via_form);
create index i_submission_session_id on ca_occurrences(submission_session_id);


/*==========================================================================*/
create table ca_occurrence_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_occurrence_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_occurrence_labels_occurrence_id foreign key (occurrence_id)
      references ca_occurrences (occurrence_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_occurrence_labels(occurrence_id);
create index i_name on ca_occurrence_labels(name(128));
create unique index u_all on ca_occurrence_labels(
   occurrence_id,
   name(255),
   type_id,
   locale_id
);
create index i_locale_id on ca_occurrence_labels(locale_id);
create index i_name_sort on ca_occurrence_labels(name_sort(255));
create index i_type_id on ca_occurrence_labels(type_id);
create index i_effective_date ON ca_occurrence_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_collections
(
   collection_id                  int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   home_location_id               int unsigned null,
   accession_sdatetime            decimal(30,20),
   accession_edatetime            decimal(30,20),
   deaccession_sdatetime          decimal(30,20),
   deaccession_edatetime          decimal(30,20),
   deaccession_disposal_sdatetime decimal(30,20),
   deaccession_disposal_edatetime decimal(30,20),
   is_deaccessioned               tinyint                        not null default 0,
   deaccession_notes              text                           not null,
   deaccession_authorized_by      varchar(255)                   not null default '',
   deaccession_type_id            int unsigned                   null,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   hier_collection_id             int unsigned                   not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   acl_inherit_from_parent        tinyint unsigned               not null default 0,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   
   primary key (collection_id),
   constraint fk_ca_collections_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_parent_id foreign key (parent_id)
      references ca_collections (collection_id) on delete restrict on update restrict,

   constraint fk_ca_collections_deaccession_type_id foreign key (deaccession_type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_collections_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,

   constraint fk_ca_collections_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_collections_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_collections_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_collections_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_collections(parent_id);
create index i_type_id on ca_collections(type_id);
create index i_idno on ca_collections(idno);
create index i_idno_sort on ca_collections(idno_sort);
create index i_idno_sort_num on ca_collections(idno_sort_num);
create index i_locale_id on ca_collections(locale_id);
create index i_source_id on ca_collections(source_id);
create index i_hier_collection_id on ca_collections(hier_collection_id);
create index i_hier_left on ca_collections(hier_left);
create index i_hier_right on ca_collections(hier_right);
create index i_acl_inherit_from_parent on ca_collections(acl_inherit_from_parent);
create index i_view_count on ca_collections(view_count);
create index i_home_location_id on ca_collections(home_location_id);
create index i_accession_sdatetime on ca_collections(accession_sdatetime);
create index i_accession_edatetime on ca_collections(accession_edatetime);
create index i_deaccession_sdatetime on ca_collections(deaccession_sdatetime);
create index i_deaccession_edatetime on ca_collections(deaccession_edatetime);
create index i_deaccession_disposal_sdatetime on ca_collections(deaccession_disposal_sdatetime);
create index i_deaccession_disposal_edatetime on ca_collections(deaccession_disposal_edatetime);
create index i_deaccession_auth_by on ca_collections(deaccession_authorized_by);
create index i_deaccession_type_id on ca_collections(deaccession_type_id);
create index i_is_deaccessioned on ca_collections(is_deaccessioned);
create index i_collection_filter on ca_collections(collection_id, deleted, access); 
create index i_submission_user_id on ca_collections(submission_user_id);
create index i_submission_group_id on ca_collections(submission_group_id);
create index i_submission_status_id on ca_collections(submission_status_id);
create index i_submission_via_form on ca_collections(submission_via_form);
create index i_submission_session_id on ca_collections(submission_session_id);


/*==========================================================================*/
create table ca_collection_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_collection_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_collection_labels_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_collection_id on ca_collection_labels(collection_id);
create index i_name on ca_collection_labels(name(128));
create unique index u_all on ca_collection_labels
(
   collection_id,
   name(255),
   type_id,
   locale_id
);
create index i_locale_id on ca_collection_labels(locale_id);
create index i_type_id on ca_collection_labels(type_id);
create index i_name_sort on ca_collection_labels(name_sort(128));
create index i_effective_date ON ca_collection_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_places
(
   place_id                       int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   locale_id                      smallint unsigned,
   type_id                        int unsigned                   null,
   source_id                      int unsigned,
   hierarchy_id                   int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_info                    longtext                       not null,
   lifespan_sdate                 decimal(30,20),
   lifespan_edate                 decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   `rank`                           int unsigned                   not null default 0,
   floorplan                      longblob                       not null,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   
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
      references ca_places (place_id) on delete restrict on update restrict,
   
   constraint fk_ca_places_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_places_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_places_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_places_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_hierarchy_id on ca_places(hierarchy_id);
create index i_type_id on ca_places(type_id);
create index i_idno on ca_places(idno);
create index i_idno_sort on ca_places(idno_sort);
create index i_idno_sort_num on ca_places(idno_sort_num);
create index i_locale_id on ca_places(locale_id);
create index i_source_id on ca_places(source_id);
create index i_life_sdatetime on ca_places(lifespan_sdate);
create index i_life_edatetime on ca_places(lifespan_edate);
create index i_parent_id on ca_places(parent_id);
create index i_hier_left on ca_places(hier_left);
create index i_hier_right on ca_places(hier_right);
create index i_view_count on ca_places(view_count);
create index i_place_filter on ca_places(place_id, deleted, access); 
create index i_submission_user_id on ca_places(submission_user_id);
create index i_submission_group_id on ca_places(submission_group_id);
create index i_submission_status_id on ca_places(submission_status_id);
create index i_submission_via_form on ca_places(submission_via_form);
create index i_submission_session_id on ca_places(submission_session_id);


/*==========================================================================*/
create table ca_place_labels
(
   label_id                       int unsigned               not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_place_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_place_labels_place_id foreign key (place_id)
      references ca_places (place_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_place_labels(place_id);
create index i_name on ca_place_labels(name(128));
create index i_name_sort on ca_place_labels(name_sort(128));
create unique index u_all on ca_place_labels
(
   place_id,
   name,
   type_id,
   locale_id
);
create index i_locale_id on ca_place_labels(locale_id);
create index i_type_id on ca_place_labels(type_id);
create index i_effective_date ON ca_place_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_storage_location_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   location_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(255)                   not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_storage_location_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_storage_location_labels_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_name on ca_storage_location_labels(name(128));
create index i_location_id on ca_storage_location_labels(location_id);
create unique index u_all on ca_storage_location_labels
(
   location_id,
   name,
   locale_id,
   type_id
);
create index i_locale_id on ca_storage_location_labels(locale_id);
create index i_type_id on ca_storage_location_labels(type_id);
create index i_name_sort on ca_storage_location_labels(name_sort(128));
create index i_effective_date ON ca_storage_location_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_loans (
   loan_id                        int unsigned                   not null AUTO_INCREMENT,
   parent_id                      int unsigned                   null,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   hier_left                      decimal(30,20)                 not null,
   hier_right                     decimal(30,20)                 not null,
   hier_loan_id                   int unsigned                   not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   primary key (loan_id),
   
   constraint fk_ca_loans_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_parent_id foreign key (parent_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_loans_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_loans_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_loans_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_loans(parent_id);
create index i_type_id on ca_loans(type_id);
create index i_source_id on ca_loans(source_id);
create index i_locale_id on ca_loans(locale_id);
create index i_idno on ca_loans(idno);
create index i_idno_sort on ca_loans(idno_sort);
create index i_idno_sort_num on ca_loans(idno_sort_num);
create index hier_left on ca_loans(hier_left);
create index hier_right on ca_loans(hier_right);
create index hier_loan_id on ca_loans(hier_loan_id);
create index i_view_count on ca_loans(view_count);
create index i_loan_filter on ca_loans(loan_id, deleted, access); 
create index i_submission_user_id on ca_loans(submission_user_id);
create index i_submission_group_id on ca_loans(submission_group_id);
create index i_submission_status_id on ca_loans(submission_status_id);
create index i_submission_via_form on ca_loans(submission_via_form);
create index i_submission_session_id on ca_loans(submission_session_id);


/*==========================================================================*/
create table ca_loan_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   loan_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   
   constraint fk_ca_loan_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_loan_labels_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_loan_id on ca_loan_labels(loan_id);
create index i_locale_id_id on ca_loan_labels(locale_id);
create index i_type_id on ca_loan_labels(type_id);
create index i_name on ca_loan_labels(name(128));
create index i_name_sort on ca_loan_labels(name_sort(128));
create index i_effective_date ON ca_loan_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_movements (
   movement_id                    int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned                   not null,
   locale_id                      smallint unsigned              null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   is_template                    tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   source_id                      int unsigned,
   source_info                    longtext                       not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   primary key (movement_id),
   
    constraint fk_ca_movements_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
    
    constraint fk_ca_movements_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
    constraint fk_ca_movements_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_movements_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_movements_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_movements_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_type_id on ca_movements(type_id);
create index i_source_id on ca_movements(source_id);
create index i_locale_id on ca_movements(locale_id);
create index i_idno on ca_movements(idno);
create index i_idno_sort on ca_movements(idno_sort);
create index i_idno_sort_num on ca_movements(idno_sort_num);
create index i_view_count on ca_movements(view_count);
create index i_movement_filter on ca_movements(movement_id, deleted, access);
create index i_submission_user_id on ca_movements(submission_user_id);
create index i_submission_group_id on ca_movements(submission_group_id);
create index i_submission_status_id on ca_movements(submission_status_id);
create index i_submission_via_form on ca_movements(submission_via_form);
create index i_submission_session_id on ca_movements(submission_session_id);


/*==========================================================================*/
create table ca_movement_labels (
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   movement_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   
   constraint fk_ca_movement_labels_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_movement_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_movement_id on ca_movement_labels(movement_id);
create index i_locale_id_id on ca_movement_labels(locale_id);
create index i_type_id on ca_movement_labels(type_id);
create index i_name on ca_movement_labels(name(128));
create index i_name_sort on ca_movement_labels(name_sort(128));
create index i_effective_date ON ca_movement_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_relationship_types
(
   type_id                        smallint unsigned              not null AUTO_INCREMENT,
   parent_id                      smallint unsigned,
   sub_type_left_id               int unsigned,
   include_subtypes_left          tinyint unsigned               not null default 0,
   sub_type_right_id              int unsigned,
   include_subtypes_right         tinyint unsigned               not null default 0,
   hier_left                      decimal(30,20) unsigned        not null,
   hier_right                     decimal(30,20) unsigned        not null,
   hier_type_id                   smallint unsigned,
   table_num                      tinyint unsigned               not null,
   type_code                      varchar(30)                    not null,
   `rank`                           smallint unsigned              not null default 0,
   is_default                     tinyint unsigned               not null,
   primary key (type_id),
      
   constraint fk_ca_relationship_types_parent_id foreign key (parent_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_type_code on ca_relationship_types(type_code, table_num);
create index i_table_num on ca_relationship_types(table_num);
create index i_sub_type_left_id on ca_relationship_types(sub_type_left_id);
create index i_sub_type_right_id on ca_relationship_types(sub_type_right_id);
create index i_parent_id on ca_relationship_types(parent_id);
create index i_hier_type_id on ca_relationship_types(hier_type_id);
create index i_hier_left on ca_relationship_types(hier_left);
create index i_hier_right on ca_relationship_types(hier_right);


/*==========================================================================*/
create table ca_relationship_type_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   type_id                        smallint unsigned              not null,
   locale_id                      smallint unsigned              not null,
   typename                       varchar(255)                   not null,
   typename_reverse               varchar(255)                   not null,
   description                    text                           not null,
   description_reverse            text                           not null,
   primary key (label_id),
   constraint fk_ca_relationship_type_labels_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_ca_relationship_type_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_type_id on ca_relationship_type_labels(type_id);
create index i_locale_id on ca_relationship_type_labels(locale_id);
create unique index u_typename on ca_relationship_type_labels
(
   type_id,
   locale_id,
   typename
);
create unique index u_typename_reverse on ca_relationship_type_labels
(
   typename_reverse,
   type_id,
   locale_id
);


/*==========================================================================*/
create table ca_object_representations_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_occurrences(representation_id);
create index i_occurrence_id on ca_object_representations_x_occurrences(occurrence_id);
create index i_type_id on ca_object_representations_x_occurrences(type_id);
create unique index u_all on ca_object_representations_x_occurrences
(
   type_id,
   representation_id,
   occurrence_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_representations_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_object_representations_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_places(representation_id);
create index i_place_id on ca_object_representations_x_places(place_id);
create index i_type_id on ca_object_representations_x_places(type_id);
create unique index u_all on ca_object_representations_x_places
(
   type_id,
   representation_id,
   place_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_places(label_left_id);
create index i_label_right_id on ca_object_representations_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_collections(representation_id);
create index i_collection_id on ca_object_representations_x_collections(collection_id);
create index i_type_id on ca_object_representations_x_collections(type_id);
create unique index u_all on ca_object_representations_x_collections
(
   type_id,
   representation_id,
   collection_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_collections(label_left_id);
create index i_label_right_id on ca_object_representations_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   location_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_storage_locations(representation_id);
create index i_location_id on ca_object_representations_x_storage_locations(location_id);
create index i_type_id on ca_object_representations_x_storage_locations(type_id);
create unique index u_all on ca_object_representations_x_storage_locations
(
   type_id,
   representation_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_representations_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations
(
   annotation_id                  int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   locale_id                      smallint unsigned,
   user_id                        int unsigned                   null,
   type_code                      varchar(30)                    not null,
   props                          longtext                       not null,
   preview                        longblob                       not null,
   source_info                    longtext                       not null,
   view_count                     int unsigned                   not null default 0,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   primary key (annotation_id),
   constraint fk_ca_rep_annot_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_rep_annot_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_representation_annotations(representation_id);
create index i_locale_id on ca_representation_annotations(locale_id);
create index i_user_id on ca_representation_annotations(user_id);
create index i_view_count on ca_representation_annotations(view_count);


/*==========================================================================*/
create table ca_representation_annotation_labels
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
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_representation_annotation_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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


/*==========================================================================*/
create table ca_task_queue
(
   task_id                        int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned,
   row_key                        CHAR(32),
   entity_key                     CHAR(32),
   created_on                     int unsigned                   not null,
   started_on                   int unsigned,
   completed_on                   int unsigned,
   priority                       smallint unsigned              not null default 0,
   handler                        varchar(20)                    not null,
   parameters                     longtext                           not null,
   notes                          longtext                       null,
   error_code                     smallint unsigned              not null default 0,
   primary key (task_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_user_id on ca_task_queue(user_id);
create index i_started_on on ca_task_queue(started_on);
create index i_completed_on on ca_task_queue(completed_on);
create index i_entity_key on ca_task_queue(entity_key);
create index i_row_key on ca_task_queue(row_key);
create index i_error_code on ca_task_queue(error_code);
create index i_handler on ca_task_queue(handler);


/*==========================================================================*/
create table ca_object_lot_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_object_lot_labels_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_lot_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_name on ca_object_lot_labels(name(128));
create index i_lot_id on ca_object_lot_labels(lot_id);
create unique index u_all on ca_object_lot_labels
(
   lot_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_lot_labels(name_sort(128));
create index i_type_id on ca_object_lot_labels(type_id);
create index i_locale_id on ca_object_lot_labels(locale_id);
create index i_effective_date ON ca_object_lot_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_collections_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_left_id             int unsigned                   not null,
   collection_right_id            int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_collection_left_id on ca_collections_x_collections(collection_left_id);
create index i_collection_right_id on ca_collections_x_collections(collection_right_id);
create index i_type_id on ca_collections_x_collections(type_id);
create unique index u_all on ca_collections_x_collections
(
   collection_left_id,
   collection_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_collections_x_collections(label_left_id);
create index i_label_right_id on ca_collections_x_collections(label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_collection_id on ca_collections_x_storage_locations (collection_id);
create index i_location_id on ca_collections_x_storage_locations (location_id);
create index i_type_id on ca_collections_x_storage_locations (type_id);
create unique index u_all on ca_collections_x_storage_locations (
   collection_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_collections_x_storage_locations(label_left_id);
create index i_label_right_id on ca_collections_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_objects
(
   object_id                      int unsigned               not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   lot_id                         int unsigned,
   locale_id                      smallint unsigned,
   source_id                      int unsigned,
   is_template                    tinyint unsigned               not null default 0,
   commenting_status              tinyint unsigned               not null default 0,
   tagging_status                 tinyint unsigned               not null default 0,
   rating_status                  tinyint unsigned               not null default 0,
   view_count                     int unsigned                   not null default 0,
   type_id                        int unsigned                   not null,
   idno                           varchar(255)                   not null,
   idno_sort                      varchar(255)                   not null,
   idno_sort_num                  bigint                         not null default 0,
   acquisition_type_id            int unsigned,
   item_status_id                 int unsigned,
   source_info                    longtext                       not null,
   hier_object_id                 int unsigned                   not null,
   hier_left                      decimal(30,20) unsigned        not null,
   hier_right                     decimal(30,20) unsigned        not null,
   extent                         int unsigned                   not null,
   extent_units                   varchar(255)                   not null,
   access                         tinyint unsigned               not null default 0,
   status                         tinyint unsigned               not null default 0,
   deleted                        tinyint unsigned               not null default 0,
   `rank`                           int unsigned                   not null default 0,
   acl_inherit_from_ca_collections tinyint unsigned              not null default 0,
   acl_inherit_from_parent         tinyint unsigned              not null default 0,
   access_inherit_from_parent      tinyint unsigned              not null default 0,
   home_location_id               int unsigned null,
   accession_sdatetime            decimal(30,20),
   accession_edatetime            decimal(30,20),
   deaccession_sdatetime          decimal(30,20),
   deaccession_edatetime          decimal(30,20),
   deaccession_disposal_sdatetime decimal(30,20),
   deaccession_disposal_edatetime decimal(30,20),
   is_deaccessioned               tinyint                        not null default 0,
   deaccession_notes              text                           not null,
   deaccession_authorized_by      varchar(255)                   not null default '',
   deaccession_type_id            int unsigned                   null,
   current_loc_class              tinyint unsigned               null,
   current_loc_subclass           int unsigned                   null,
   current_loc_id                 int unsigned                   null,
   circulation_status_id          int unsigned                   null,
   submission_user_id               int unsigned                   null,
   submission_group_id            int unsigned                   null,
   submission_status_id              int unsigned                   null,
   submission_via_form            varchar(100)                   null,
   submission_session_id          int unsigned                   null,
   
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
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_deaccession_type_id foreign key (deaccession_type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_objects_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,

   constraint fk_ca_objects_circulation_status_id foreign key (circulation_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_objects_submission_user_id foreign key (submission_user_id)
      references ca_users (user_id) on delete restrict on update restrict,

   constraint fk_ca_objects_submission_group_id foreign key (submission_group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,

   constraint fk_ca_objects_submission_status_id foreign key (submission_status_id)
      references ca_list_items (item_id) on delete restrict on update restrict,

   constraint fk_ca_objects_submission_session_id foreign key (submission_session_id)
      references ca_media_upload_sessions(session_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_objects(parent_id);
create index i_idno on ca_objects(idno);
create index i_idno_sort on ca_objects(idno_sort);
create index i_idno_sort_num on ca_objects(idno_sort_num);
create index i_type_id on ca_objects(type_id);
create index i_hier_left on ca_objects(hier_left);
create index i_hier_right on ca_objects(hier_right);
create index i_lot_id on ca_objects(lot_id);
create index i_locale_id on ca_objects(locale_id);
create index i_hier_object_id on ca_objects(hier_object_id);
create index i_acqusition_type_id on ca_objects
(
   source_id,
   acquisition_type_id
);
create index i_source_id on ca_objects(source_id);
create index i_item_status_id on ca_objects(item_status_id);
create index i_acl_inherit_from_parent on ca_objects(acl_inherit_from_parent);
create index i_acl_inherit_from_ca_collections on ca_objects(acl_inherit_from_ca_collections);
create index i_home_location_id on ca_objects(home_location_id);
create index i_accession_sdatetime on ca_objects(accession_sdatetime);
create index i_accession_edatetime on ca_objects(accession_edatetime);
create index i_deaccession_sdatetime on ca_objects(deaccession_sdatetime);
create index i_deaccession_edatetime on ca_objects(deaccession_edatetime);
create index i_deaccession_disposal_sdatetime on ca_objects(deaccession_disposal_sdatetime);
create index i_deaccession_disposal_edatetime on ca_objects(deaccession_disposal_edatetime);
create index i_deaccession_auth_by on ca_objects(deaccession_authorized_by);
create index i_deaccession_type_id on ca_objects(deaccession_type_id);
create index i_is_deaccessioned on ca_objects(is_deaccessioned);
create index i_current_loc_class on ca_objects(current_loc_class);
create index i_current_loc_subclass on ca_objects(current_loc_subclass);
create index i_current_loc_id on ca_objects(current_loc_id);
create index i_view_count on ca_objects(view_count);
create index i_obj_filter on ca_objects(object_id, deleted, access); 
create index i_submission_user_id on ca_objects(submission_user_id);
create index i_submission_group_id on ca_objects(submission_group_id);
create index i_submission_status_id on ca_objects(submission_status_id);
create index i_submission_via_form on ca_objects(submission_via_form);
create index i_submission_session_id on ca_objects(submission_session_id);


/*==========================================================================*/
create table ca_object_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_object_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_object_labels_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_name on ca_object_labels(name(128));
create index i_object_id on ca_object_labels(object_id);
create unique index u_all on ca_object_labels
(
   object_id,
   name(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_object_labels(name_sort(128));
create index i_type_id on ca_object_labels(type_id);
create index i_locale_id on ca_object_labels(locale_id);
create index i_effective_date ON ca_object_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_objects_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_objects_x_collections(object_id);
create index i_collection_id on ca_objects_x_collections(collection_id);
create index i_type_id on ca_objects_x_collections(type_id);
create unique index u_all on ca_objects_x_collections
(
   object_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_collections(label_left_id);
create index i_label_right_id on ca_objects_x_collections(label_right_id);


/*==========================================================================*/
create table ca_objects_x_objects
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_left_id                 int unsigned               not null,
   object_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_left_id on ca_objects_x_objects(object_left_id);
create index i_object_right_id on ca_objects_x_objects(object_right_id);
create index i_type_id on ca_objects_x_objects(type_id);
create unique index u_all on ca_objects_x_objects
(
   object_left_id,
   object_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_objects(label_left_id);
create index i_label_right_id on ca_objects_x_objects(label_right_id);


/*==========================================================================*/
create table ca_objects_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned                   not null,
   representation_id              int unsigned                   not null,
   is_primary                     tinyint                        not null,
   `rank`                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_objects_x_object_representations_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
   constraint fk_ca_objects_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_objects_x_object_representations(object_id);
create index i_representation_id on ca_objects_x_object_representations(representation_id);
create unique index u_all on ca_objects_x_object_representations
(
   object_id,
   representation_id
);


/*==========================================================================*/
create table ca_objects_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_objects_x_occurrences(occurrence_id);
create index i_object_id on ca_objects_x_occurrences(object_id);
create index i_type_id on ca_objects_x_occurrences(type_id);
create unique index u_all on ca_objects_x_occurrences
(
   occurrence_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_occurrences(label_left_id);
create index i_label_right_id on ca_objects_x_occurrences(label_right_id);

/*==========================================================================*/
create table ca_objects_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_objects_x_places(place_id);
create index i_object_id on ca_objects_x_places(object_id);
create index i_type_id on ca_objects_x_places(type_id);
create unique index u_all on ca_objects_x_places
(
   place_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_places(label_left_id);
create index i_label_right_id on ca_objects_x_places(label_right_id);


/*==========================================================================*/
create table ca_attributes
(
   attribute_id                   int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   locale_id                      smallint unsigned              null,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   value_source                   varchar(1024)                   null,
   primary key (attribute_id),
   constraint fk_ca_attributes_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attributes_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_locale_id on ca_attributes(locale_id);
create index i_row_id on ca_attributes(row_id);
create index i_table_num on ca_attributes(table_num);
create index i_element_id on ca_attributes(element_id);
create index i_row_table_num on ca_attributes(row_id, table_num);
create index i_prefetch ON ca_attributes(row_id, element_id, table_num);
create index i_value_source on ca_attributes(value_source(255));


/*==========================================================================*/
create table ca_data_import_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   occurred_on                    int unsigned                   not null,
   user_id                        int unsigned,
   description                    text                           not null,
   type_code                      char(50)                       not null,
   source                         text                           not null,
   primary key (event_id),
   constraint fk_ca_data_import_events_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_user_id on ca_data_import_events(user_id);


/*==========================================================================*/
create table ca_data_import_items
(
   item_id                        int unsigned                  not null AUTO_INCREMENT,
   event_id                       int unsigned                  not null,
   source_ref                    varchar(255)                  not null,
   table_num                    tinyint unsigned            null,
   row_id                          int unsigned                  null,
   type_code                     char(1)                          null,
   started_on                    int unsigned                 not null,
   completed_on               int unsigned                 null,
   elapsed_time                decimal(8,4)                  null,
   success                        tinyint unsigned            null,
   message                       text                              not null,
   primary key (item_id),
   constraint fk_ca_data_import_items_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_event_id on ca_data_import_items(event_id);
create index i_row_id on ca_data_import_items(table_num, row_id);


/*==========================================================================*/
create table ca_data_import_event_log
(
   log_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                    int unsigned                   not null,
   item_id                      int unsigned                   null,
   type_code                  char(10)                       not null,
   date_time                  int unsigned                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_event_id on ca_data_import_event_log(event_id);
create index i_item_id on ca_data_import_event_log(item_id);


/*==========================================================================*/
create table ca_data_importers (
   importer_id          int unsigned         not null AUTO_INCREMENT,
   importer_code        varchar(100)         not null,
   table_num            tinyint unsigned     not null,
   settings             longtext             not null,
   rules                longtext             not null,
   worksheet            longblob             not null,
   deleted              tinyint unsigned     not null,
   primary key (importer_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_importer_code on ca_data_importers(importer_code);
create index i_table_num on ca_data_importers(table_num);


/*==========================================================================*/
create table ca_data_importer_labels (
   label_id          int unsigned         not null AUTO_INCREMENT,
   importer_id          int unsigned         not null,
   locale_id            smallint unsigned    not null,
   name              varchar(255)         not null,
   name_sort            varchar(255)         not null,
   description          text              not null,
   source_info          longtext             not null,
   is_preferred         tinyint unsigned     not null,

   primary key (label_id),

   constraint fk_ca_data_importer_labels_importer_id foreign key (importer_id)
      references ca_data_importers (importer_id) on delete restrict on update restrict,

   constraint fk_ca_data_importer_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_importer_id on ca_data_importer_labels(importer_id);
create index i_locale_id on ca_data_importer_labels(locale_id);
create index i_name_sort on ca_data_importer_labels(name_sort(128));
create unique index u_all on ca_data_importer_labels
(
   importer_id,
   locale_id,
   name,
   is_preferred
);


/*==========================================================================*/
create table ca_data_importer_groups (
   group_id             int unsigned         not null AUTO_INCREMENT,
   importer_id          int unsigned         not null,
   group_code           varchar(100)         not null,
   destination          varchar(1024)        not null,
   settings             longtext             not null,

   primary key (group_id),

   constraint fk_ca_data_importer_groups_importer_id foreign key (importer_id)
      references ca_data_importers (importer_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_importer_id on ca_data_importer_groups(importer_id);
create unique index u_group_code on ca_data_importer_groups(importer_id, group_code);


/*==========================================================================*/
create table ca_data_importer_items (
   item_id           int unsigned         not null AUTO_INCREMENT,
   importer_id          int unsigned         not null,
   group_id             int unsigned         not null,
   source               varchar(8192)         not null,
   destination          varchar(1024)         not null,
   settings          longtext          not null,

   primary key (item_id),

   constraint fk_ca_data_importer_items_importer_id foreign key (importer_id)
      references ca_data_importers (importer_id) on delete restrict on update restrict,
   constraint fk_ca_data_importer_items_group_id foreign key (group_id)
      references ca_data_importer_groups (group_id) on delete restrict on update restrict

)  engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_importer_id on ca_data_importer_items(importer_id);
create index i_group_id on ca_data_importer_items(group_id);

/*==========================================================================*/
create table ca_data_exporters (
   exporter_id          int unsigned         not null AUTO_INCREMENT,
   exporter_code        varchar(100)         not null,
   table_num            tinyint unsigned     not null,
   settings             longtext             not null,
   primary key (exporter_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_exporter_code on ca_data_exporters(exporter_code);
create index i_table_num on ca_data_exporters(table_num);

/*==========================================================================*/
create table ca_data_exporter_labels (
   label_id          int unsigned         not null AUTO_INCREMENT,
   exporter_id          int unsigned         not null,
   locale_id            smallint unsigned    not null,
   name              varchar(255)         not null,
   name_sort            varchar(255)         not null,
   description          text              not null,
   source_info          longtext             not null,
   is_preferred         tinyint unsigned     not null,

   primary key (label_id),

   constraint fk_ca_data_exporter_labels_exporter_id foreign key (exporter_id)
      references ca_data_exporters (exporter_id) on delete restrict on update restrict,

   constraint fk_ca_data_exporter_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_exporter_id on ca_data_exporter_labels(exporter_id);
create index i_locale_id on ca_data_exporter_labels(locale_id);
create index i_name_sort on ca_data_exporter_labels(name_sort(128));
create unique index u_all on ca_data_exporter_labels
(
   exporter_id,
   locale_id,
   name,
   is_preferred
);

/*==========================================================================*/
create table ca_data_exporter_items (
   item_id           int unsigned      not null AUTO_INCREMENT,
   parent_id         int unsigned      null,
   exporter_id       int unsigned      not null,
   element           varchar(1024)     not null,
   context           varchar(1024)     null,
   source            varchar(1024)     null,
   settings          longtext          not null,
   hier_item_id      int unsigned      not null,
   hier_left         decimal(30,20)    unsigned not null,
   hier_right        decimal(30,20)    unsigned not null,
   `rank`              int unsigned      not null default 0,

   primary key (item_id),

   constraint fk_ca_data_exporter_items_exporter_id foreign key (exporter_id)
      references ca_data_exporters (exporter_id) on delete restrict on update restrict,
   constraint fk_ca_data_exporter_items_parent_id foreign key (parent_id)
      references ca_data_exporter_items (item_id) on delete restrict on update restrict

)  engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_parent_id on ca_data_exporter_items(parent_id);
create index i_exporter_id on ca_data_exporter_items(exporter_id);
create index i_hier_left on ca_data_exporter_items(hier_left);
create index i_hier_right on ca_data_exporter_items(hier_right);
create index i_hier_item_id on ca_data_exporter_items(hier_item_id);

/*==========================================================================*/
create table ca_data_importer_log
(
   log_id                         int unsigned                   not null AUTO_INCREMENT,
   importer_id                    int unsigned                   not null,
   user_id                        int unsigned,
   log_datetime                   int unsigned                   not null,
   notes                          text                           not null,
   table_num                      tinyint unsigned               not null,
   datafile                       longblob                       not null,
   primary key (log_id),
   
   index i_user_id (user_id),
   index i_importer_id (importer_id),
   index i_log_datetime (log_datetime),
   
   constraint fk_ca_data_importer_log_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_data_importers_log_importer_id foreign key (importer_id)
      references ca_data_importers (importer_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_data_importer_log_items
(
   log_item_id                   int unsigned                   not null AUTO_INCREMENT,
   log_id                        int unsigned                   not null,
   log_datetime                  int unsigned                   not null,
   table_num                     tinyint unsigned               not null,
   row_id                        int unsigned                   not null,
   type_code                     char(10)                       not null,
   notes                         text                           not null,
   primary key (log_item_id),
   
   index i_log_id (log_id),
   index i_row_id (row_id),
   index i_log_datetime (log_datetime),
   
   constraint fk_ca_data_importer_log_items_log_id foreign key (log_id)
      references ca_data_importer_log (log_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_object_lots_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_id on ca_object_lots_x_collections(lot_id);
create index i_collection_id on ca_object_lots_x_collections(collection_id);
create index i_type_id on ca_object_lots_x_collections(type_id);
create unique index u_all on ca_object_lots_x_collections
(
   lot_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_collections(label_left_id);
create index i_label_right_id on ca_object_lots_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_object_lots_x_occurrences(occurrence_id);
create index i_lot_id on ca_object_lots_x_occurrences(lot_id);
create index i_type_id on ca_object_lots_x_occurrences(type_id);
create unique index u_all on ca_object_lots_x_occurrences
(
   occurrence_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_occurrences(label_left_id);
create index i_label_right_id on ca_object_lots_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_id on ca_object_lots_x_places(lot_id);
create index i_place_id on ca_object_lots_x_places(place_id);
create index i_type_id on ca_object_lots_x_places(type_id);
create unique index u_all on ca_object_lots_x_places
(
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_places(label_left_id);
create index i_label_right_id on ca_object_lots_x_places(label_right_id);


/*==========================================================================*/
create table ca_acl
(
   acl_id                         int unsigned                   not null AUTO_INCREMENT,
   group_id                       int unsigned,
   user_id                        int unsigned,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   access                         tinyint unsigned               not null default 0,
   notes                          char(10)                       not null,
   inherited_from_table_num       tinyint unsigned               null,
   inherited_from_row_id          int unsigned                   null,
   primary key (acl_id),
   constraint fk_ca_acl_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_acl_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_acl(row_id, table_num);
create index i_user_id on ca_acl(user_id);
create index i_group_id on ca_acl(group_id);
create index i_inherited_from_table_num ON ca_acl(inherited_from_table_num);
create index i_inherited_from_row_id ON ca_acl(inherited_from_row_id);


/*==========================================================================*/
create table ca_occurrences_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_occurrences_x_collections(occurrence_id);
create index i_collection_id on ca_occurrences_x_collections(collection_id);
create index i_type_id on ca_occurrences_x_collections(type_id);
create unique index u_all on ca_occurrences_x_collections
(
   occurrence_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_occurrences_x_collections(label_left_id);
create index i_label_right_id on ca_occurrences_x_collections(label_right_id);


/*==========================================================================*/
create table ca_occurrences_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_left_id             int unsigned                   not null,
   occurrence_right_id            int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_left_id on ca_occurrences_x_occurrences(occurrence_left_id);
create index i_occurrence_right_id on ca_occurrences_x_occurrences(occurrence_right_id);
create index i_type_id on ca_occurrences_x_occurrences(type_id);
create unique index u_all on ca_occurrences_x_occurrences
(
   occurrence_left_id,
   occurrence_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_occurrences_x_occurrences(label_left_id);
create index i_label_right_id on ca_occurrences_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_entity_labels
(
   label_id                       int unsigned               not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   displayname                    varchar(512)                   not null,
   forename                       varchar(100)                   not null,
   other_forenames                varchar(100)                   not null,
   middlename                     varchar(100)                   not null,
   surname                        varchar(512)                   not null,
   prefix                         varchar(100)                   not null,
   suffix                         varchar(100)                   not null,
   name_sort                      varchar(512)                   not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   checked                        tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_entity_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_entity_labels_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on ca_entity_labels(entity_id);
create index i_forename on ca_entity_labels(forename);
create index i_surname on ca_entity_labels(surname(128));
create unique index u_all on ca_entity_labels
(
   entity_id,
   forename(50),
   other_forenames(50),
   middlename(50),
   surname(50),
   type_id,
   locale_id
);
create index i_locale_id on ca_entity_labels(locale_id);
create index i_type_id on ca_entity_labels(type_id);
create index i_name_sort on ca_entity_labels(name_sort(128));
create index i_checked on ca_entity_labels(checked);
create index i_effective_date ON ca_entity_labels(sdatetime, edatetime);


/*==========================================================================*/
create table ca_entities_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on ca_entities_x_collections(entity_id);
create index i_collection_id on ca_entities_x_collections(collection_id);
create index i_type_id on ca_entities_x_collections(type_id);
create unique index u_all on ca_entities_x_collections
(
   entity_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_collections(label_left_id);
create index i_label_right_id on ca_entities_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_places_x_collections(place_id);
create index i_collection_id on ca_places_x_collections(collection_id);
create index i_type_id on ca_places_x_collections(type_id);
create unique index u_all on ca_places_x_collections
(
   place_id,
   collection_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_collections(label_left_id);
create index i_label_right_id on ca_places_x_collections(label_right_id);


/*==========================================================================*/
create table ca_places_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_places_x_occurrences(occurrence_id);
create index i_place_id on ca_places_x_occurrences(place_id);
create index i_type_id on ca_places_x_occurrences(type_id);
create unique index u_all on ca_places_x_occurrences
(
   place_id,
   occurrence_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_occurrences(label_left_id);
create index i_label_right_id on ca_places_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_places_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_left_id                  int unsigned               not null,
   place_right_id                 int unsigned               not null,
   type_id                        smallint unsigned              null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_left_id on ca_places_x_places(place_left_id);
create index i_place_right_id on ca_places_x_places(place_right_id);
create index i_type_id on ca_places_x_places(type_id);
create unique index u_all on ca_places_x_places
(
   place_left_id,
   place_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_places_x_places(label_left_id);
create index i_label_right_id on ca_places_x_places(label_right_id);


/*==========================================================================*/
create table ca_entities_x_occurrences
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   entity_id                      int unsigned               not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on ca_entities_x_occurrences(entity_id);
create index i_occurrence_id on ca_entities_x_occurrences(occurrence_id);
create index i_type_id on ca_entities_x_occurrences(type_id);
create unique index u_all on ca_entities_x_occurrences
(
   occurrence_id,
   type_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_occurrences(label_left_id);
create index i_label_right_id on ca_entities_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_relationship_relationships
(
   reification_id                 int unsigned                   not null AUTO_INCREMENT,
   type_id                        smallint unsigned              not null,
   relationship_table_num         tinyint unsigned               not null,
   relation_id                    int unsigned                   not null,
   table_num                      tinyint                        not null,
   row_id                         int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   primary key (reification_id),
   constraint ca_relationship_relationships_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_type_id on ca_relationship_relationships(type_id);
create index i_relation_row on ca_relationship_relationships
(
   relation_id,
   relationship_table_num
);
create index i_target_row on ca_relationship_relationships
(
   row_id,
   table_num
);


/*==========================================================================*/
create table ca_entities_x_places
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_entities_x_places(place_id);
create index i_entity_id on ca_entities_x_places(entity_id);
create index i_type_id on ca_entities_x_places(type_id);
create unique index u_all on ca_entities_x_places
(
   entity_id,
   place_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_places(label_left_id);
create index i_label_right_id on ca_entities_x_places(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_entities(representation_id);
create index i_entity_id on ca_object_representations_x_entities(entity_id);
create index i_type_id on ca_object_representations_x_entities(type_id);
create unique index u_all on ca_object_representations_x_entities
(
   type_id,
   representation_id,
   entity_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_entities(label_left_id);
create index i_label_right_id on ca_object_representations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_left_id                 int unsigned               not null,
   representation_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_left_id on ca_object_representations_x_object_representations(representation_left_id);
create index i_representation_right_id on ca_object_representations_x_object_representations(representation_right_id);
create index i_type_id on ca_object_representations_x_object_representations(type_id);
create unique index u_all on ca_object_representations_x_object_representations
(
   representation_left_id,
   representation_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_object_representations(label_left_id);
create index i_label_right_id on ca_object_representations_x_object_representations(label_right_id);


/*==========================================================================*/
create table ca_entities_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_left_id                 int unsigned               not null,
   entity_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_left_id on ca_entities_x_entities(entity_left_id);
create index i_entity_right_id on ca_entities_x_entities(entity_right_id);
create index i_type_id on ca_entities_x_entities(type_id);
create unique index u_all on ca_entities_x_entities
(
   entity_left_id,
   entity_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_entities_x_entities(label_left_id);
create index i_label_right_id on ca_entities_x_entities(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_entities
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on ca_representation_annotations_x_entities(entity_id);
create index i_annotation_id on ca_representation_annotations_x_entities(annotation_id);
create index i_type_id on ca_representation_annotations_x_entities(type_id);
create unique index u_all on ca_representation_annotations_x_entities
(
   entity_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_entities(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_entities(label_right_id);


/*==========================================================================*/
create table ca_groups_x_roles
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   group_id                       int unsigned                   not null,
   role_id                        smallint unsigned              not null,
   `rank`                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_groups_x_roles_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict,
   constraint fk_ca_groups_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_group_id on ca_groups_x_roles(group_id);
create index i_role_id on ca_groups_x_roles(role_id);
create index u_all on ca_groups_x_roles
(
   group_id,
   role_id
);


/*==========================================================================*/
create table ca_ips
(
   ip_id                          int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   ip1                            tinyint unsigned               not null,
   ip2                            tinyint unsigned,
   ip3                            tinyint unsigned,
   ip4s                           tinyint unsigned,
   ip4e                           tinyint unsigned,
   notes                          text                           not null,
   primary key (ip_id),
   constraint fk_ca_ips_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_ip on ca_ips
(
   ip1,
   ip2,
   ip3,
   ip4s,
   ip4e
);
create index i_user_id on ca_ips(user_id);


/*==========================================================================*/
create table ca_representation_annotations_x_objects
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_representation_annotations_x_objects(object_id);
create index i_annotation_id on ca_representation_annotations_x_objects(annotation_id);
create index i_type_id on ca_representation_annotations_x_objects(type_id);
create unique index u_all on ca_representation_annotations_x_objects
(
   object_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_objects(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_objects(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_occurrences
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_occurrence_id on ca_representation_annotations_x_occurrences(occurrence_id);
create index i_annotation_id on ca_representation_annotations_x_occurrences(annotation_id);
create index i_type_id on ca_representation_annotations_x_occurrences(type_id);
create unique index u_all on ca_representation_annotations_x_occurrences
(
   occurrence_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_occurrences(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_occurrences(label_right_id);


/*==========================================================================*/
create table ca_list_items_x_list_items
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   term_left_id                   int unsigned                   not null,
   term_right_id                  int unsigned                   not null,
   type_id                        smallint unsigned              null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_term_left_id on ca_list_items_x_list_items(term_left_id);
create index i_term_right_id on ca_list_items_x_list_items(term_right_id);
create index i_type_id on ca_list_items_x_list_items(type_id);
create unique index u_all on ca_list_items_x_list_items
(
   term_left_id,
   term_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_list_items_x_list_items(label_left_id);
create index i_label_right_id on ca_list_items_x_list_items(label_right_id);


/*==========================================================================*/
create table ca_objects_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_objects_x_storage_locations (object_id);
create index i_location_id on ca_objects_x_storage_locations (location_id);
create index i_type_id on ca_objects_x_storage_locations (type_id);
create unique index u_all on ca_objects_x_storage_locations (
   object_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id on ca_objects_x_storage_locations(label_left_id);
create index i_label_right_id on ca_objects_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_id on ca_object_lots_x_storage_locations (lot_id);
create index i_location_id on ca_object_lots_x_storage_locations (location_id);
create index i_type_id on ca_object_lots_x_storage_locations (type_id);
create unique index u_all on ca_object_lots_x_storage_locations (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);
create index i_label_left_id on ca_object_lots_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_lots_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_entities_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   location_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on  ca_entities_x_storage_locations(entity_id);
create index i_location_id on  ca_entities_x_storage_locations(location_id);
create index i_type_id on  ca_entities_x_storage_locations(type_id);
create unique index u_all on  ca_entities_x_storage_locations
(
   entity_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_entities_x_storage_locations(label_left_id);
create index i_label_right_id on  ca_entities_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_id on ca_object_lots_x_vocabulary_terms (lot_id);
create index i_item_id on ca_object_lots_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_lots_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_lots_x_vocabulary_terms (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_lots_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_lots_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_object_lots
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_left_id                    int unsigned               not null,
   lot_right_id                   int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_object_lots_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_lot_left_id foreign key (lot_left_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_lot_right_id foreign key (lot_right_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_left_id on ca_object_lots_x_object_lots(lot_left_id);
create index i_lot_right_id on ca_object_lots_x_object_lots(lot_right_id);
create index i_type_id on ca_object_lots_x_object_lots(type_id);
create unique index u_all on ca_object_lots_x_object_lots
(
   lot_left_id,
   lot_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_object_lots(label_left_id);
create index i_label_right_id on ca_object_lots_x_object_lots(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_representations_x_vocabulary_terms (representation_id);
create index i_item_id on ca_object_representations_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_representations_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_representations_x_vocabulary_terms (
   representation_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_object_representations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_object_representations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_users_x_groups
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   group_id                       int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_users_x_groups_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_groups_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_user_id on ca_users_x_groups(user_id);
create index i_group_id on ca_users_x_groups(group_id);
create unique index u_all on ca_users_x_groups
(
   user_id,
   group_id
);


/*==========================================================================*/
create table ca_users_x_roles
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   role_id                        smallint unsigned              not null,
   `rank`                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_users_x_roles_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
   constraint fk_ca_users_x_roles_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_user_id on ca_users_x_roles(user_id);
create index i_role_id on ca_users_x_roles(role_id);
create unique index u_all on ca_users_x_roles
(
   user_id,
   role_id
);


/*==========================================================================*/
create table ca_representation_annotations_x_places
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_representation_annotations_x_places(place_id);
create index i_annotation_id on ca_representation_annotations_x_places(annotation_id);
create index i_type_id on ca_representation_annotations_x_places(type_id);
create unique index u_all on ca_representation_annotations_x_places
(
   place_id,
   type_id,
   annotation_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_representation_annotations_x_places(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_places(label_right_id);


/*==========================================================================*/
create table ca_representation_annotations_x_vocabulary_terms
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_item_id on ca_representation_annotations_x_vocabulary_terms(item_id);
create index i_annotation_id on ca_representation_annotations_x_vocabulary_terms(annotation_id);
create index i_type_id on ca_representation_annotations_x_vocabulary_terms(type_id);
create unique index u_all on ca_representation_annotations_x_vocabulary_terms
(
   type_id,
   annotation_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_representation_annotations_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_representation_annotations_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_objects_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_objects_x_vocabulary_terms(object_id);
create index i_item_id on ca_objects_x_vocabulary_terms(item_id);
create index i_type_id on ca_objects_x_vocabulary_terms(type_id);
create unique index u_all on ca_objects_x_vocabulary_terms
(
   object_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_objects_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_objects_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_object_lots_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_lot_id on ca_object_lots_x_entities(lot_id);
create index i_entity_id on ca_object_lots_x_entities(entity_id);
create index i_type_id on ca_object_lots_x_entities(type_id);
create unique index u_all on ca_object_lots_x_entities
(
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_entities(label_left_id);
create index i_label_right_id on ca_object_lots_x_entities(label_right_id);


/*==========================================================================*/
create table ca_objects_x_entities
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_entity_id on ca_objects_x_entities(entity_id);
create index i_object_id on ca_objects_x_entities(object_id);
create index i_type_id on ca_objects_x_entities(type_id);
create unique index u_all on ca_objects_x_entities
(
   entity_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_objects_x_entities(label_left_id);
create index i_label_right_id on ca_objects_x_entities(label_right_id);


/*==========================================================================*/
create table ca_places_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   place_id                       int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_place_id on ca_places_x_vocabulary_terms(place_id);
create index i_item_id on ca_places_x_vocabulary_terms(item_id);
create index i_type_id on ca_places_x_vocabulary_terms(type_id);
create unique index u_all on ca_places_x_vocabulary_terms
(
   place_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_places_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_places_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_loan_id on ca_loans_x_objects (loan_id);
create index i_object_id on ca_loans_x_objects (object_id);
create index i_type_id on ca_loans_x_objects (type_id);
create unique index u_all on ca_loans_x_objects (
   loan_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_objects (label_left_id);
create index i_label_right_id on ca_loans_x_objects (label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_loan_id on ca_loans_x_entities (loan_id);
create index i_entity_id on ca_loans_x_entities (entity_id);
create index i_type_id on ca_loans_x_entities (type_id);
create unique index u_all on ca_loans_x_entities (
   loan_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_entities (label_left_id);
create index i_label_right_id on ca_loans_x_entities (label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_movement_id on ca_movements_x_objects (movement_id);
create index i_object_id on ca_movements_x_objects (object_id);
create index i_type_id on ca_movements_x_objects (type_id);
create unique index u_all on ca_movements_x_objects (
   movement_id,
   object_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_objects (label_left_id);
create index i_label_right_id on ca_movements_x_objects (label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_movement_id on ca_movements_x_object_lots (movement_id);
create index i_lot_id on ca_movements_x_object_lots (lot_id);
create index i_type_id on ca_movements_x_object_lots (type_id);
create unique index u_all on ca_movements_x_object_lots (
   movement_id,
   lot_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_object_lots (label_left_id);
create index i_label_right_id on ca_movements_x_object_lots (label_right_id);


/*==========================================================================*/
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
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_movement_id on ca_movements_x_entities (movement_id);
create index i_entity_id on ca_movements_x_entities (entity_id);
create index i_type_id on ca_movements_x_entities (type_id);
create unique index u_all on ca_movements_x_entities (
   movement_id,
   entity_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_entities (label_left_id);
create index i_label_right_id on ca_movements_x_entities (label_right_id);


/*==========================================================================*/
create table ca_loans_x_movements (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   movement_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_loan_id on ca_loans_x_movements (loan_id);
create index i_movement_id on ca_loans_x_movements (movement_id);
create index i_type_id on ca_loans_x_movements (type_id);
create unique index u_all on ca_loans_x_movements (
   loan_id,
   movement_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_movements (label_left_id);
create index i_label_right_id on ca_loans_x_movements (label_right_id);


/*==========================================================================*/
create table ca_attribute_values
(
   value_id                   	  int unsigned                   not null AUTO_INCREMENT,
   element_id                     smallint unsigned              not null,
   attribute_id                   int unsigned                   not null,
   item_id                        int unsigned                   null,
   value_longtext1                longtext                       null,
   value_longtext2                longtext                       null,
   value_blob                     longblob                       null,
   value_decimal1                 decimal(40,20)                 null,
   value_decimal2                 decimal(40,20)                 null,
   value_integer1                 int unsigned                   null,
   value_sortable                 varchar(100)                   null,
   source_info                    longtext                       not null,
   primary key (value_id),
   constraint fk_ca_attribute_values_attribute_id foreign key (attribute_id)
      references ca_attributes (attribute_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_element_id foreign key (element_id)
      references ca_metadata_elements (element_id) on delete restrict on update restrict,
   constraint fk_ca_attribute_values_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_element_id on ca_attribute_values(element_id);
create index i_attribute_id on ca_attribute_values(attribute_id);
create index i_value_integer1 on ca_attribute_values(value_integer1);
create index i_value_decimal1 on ca_attribute_values(value_decimal1);
create index i_value_decimal2 on ca_attribute_values(value_decimal2);
create index i_item_id on ca_attribute_values(item_id);
create index i_value_longtext1 on ca_attribute_values
(
   value_longtext1(128)
);
create index i_value_longtext2 on ca_attribute_values
(
   value_longtext2(128)
);
create index i_source_info on ca_attribute_values(source_info(255));
create index i_attr_element on ca_attribute_values(attribute_id, element_id);
create index i_value_sortable on ca_attribute_values(value_sortable);
create index i_sorting on ca_attribute_values(element_id, attribute_id, value_sortable);


/*==========================================================================*/
create table ca_attribute_value_multifiles (
	multifile_id		int unsigned not null auto_increment,
	value_id	        int unsigned not null,
	resource_path		text not null,
	media				longblob not null,
	media_metadata		longblob not null,
	media_content		longtext not null,
	`rank`				int unsigned not null default 0,	
	primary key (multifile_id),
	
   constraint fk_ca_attribute_value_multifiles_value_id foreign key (value_id)
      references ca_attribute_values (value_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_resource_path on ca_attribute_value_multifiles(resource_path(255));
create index i_value_id on ca_attribute_value_multifiles(value_id);


/*==========================================================================*/
create table ca_occurrences_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   occurrence_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_occurrences_x_vocabulary_terms(occurrence_id);
create index i_item_id on ca_occurrences_x_vocabulary_terms(item_id);
create index i_type_id on ca_occurrences_x_vocabulary_terms(type_id);
create unique index u_all on ca_occurrences_x_vocabulary_terms
(
   occurrence_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_occurrences_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_occurrences_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_collections_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_item_id on ca_collections_x_vocabulary_terms(item_id);
create index i_collection_id on ca_collections_x_vocabulary_terms(collection_id);
create index i_type_id on ca_collections_x_vocabulary_terms(type_id);
create unique index u_all on ca_collections_x_vocabulary_terms
(
   collection_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_collections_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_collections_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_entities_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_object_id on ca_entities_x_vocabulary_terms(entity_id);
create index i_item_id on ca_entities_x_vocabulary_terms(item_id);
create index i_type_id on ca_entities_x_vocabulary_terms(type_id);
create unique index u_all on ca_entities_x_vocabulary_terms
(
   entity_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);
create index i_label_left_id on ca_entities_x_vocabulary_terms(label_left_id);
create index i_label_right_id on ca_entities_x_vocabulary_terms(label_right_id);


/*==========================================================================*/
create table ca_editor_uis (
	ui_id int unsigned not null auto_increment,
	user_id int unsigned null,		/* owner of ui */
	is_system_ui tinyint unsigned not null,
	editor_type tinyint unsigned not null,							/* tablenum of editor */
	editor_code varchar(100) null,
	color char(6) null,
	icon longblob not null,
	
	primary key 				(ui_id),
	index i_user_id				(user_id),
	
   constraint fk_ca_editor_uis_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_code on ca_editor_uis(editor_code);

/*==========================================================================*/
create table ca_editor_ui_labels (
	label_id int unsigned not null auto_increment,
	ui_id int unsigned not null,
	name varchar(255) not null,
	description text not null,
	locale_id smallint unsigned not null,
	
	primary key 				(label_id),
	index i_ui_id				(ui_id),
	index i_locale_id			(locale_id),
	
   constraint fk_ca_editor_ui_labels_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_labels_ca_locales foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_uis_x_user_groups (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null,
	group_id int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_group_id			(group_id),
	
   constraint fk_ca_editor_uis_x_user_groups_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_uis_x_user_groups_ca_user_groups foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_uis_x_users (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null,
	user_id int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_user_id			(user_id),
	
   constraint fk_ca_editor_uis_x_users_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_uis_x_users_ca_users foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_uis_x_roles (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null,
	role_id smallint unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_role_id				(role_id),
	
   constraint fk_ca_editor_uis_x_roles_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_uis_x_roles_ca_user_roles foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens (
	screen_id int unsigned not null auto_increment,
	parent_id int unsigned null,
	ui_id int unsigned not null,
	idno varchar(255) not null,
	`rank` smallint unsigned not null default 0,
	is_default tinyint unsigned not null,
	color char(6) null,
	icon longblob not null,
	
	hier_left decimal(30,20) not null,
	hier_right decimal (30,20) not null,
	
	primary key 				(screen_id),
	index i_ui_id 				(ui_id),
	index i_parent_id			(parent_id),
	index i_hier_left			(hier_left),
	index i_hier_right			(hier_right),
      
   constraint fk_ca_editor_ui_screens_parent_id foreign key (parent_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_screens_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screen_labels (
	label_id int unsigned not null auto_increment,
	screen_id int unsigned not null,
	name varchar(255) not null,
	description text not null,
	locale_id smallint unsigned not null,
	
	primary key 				(label_id),
	index i_screen_id			(screen_id),
	index i_locale_id			(locale_id),
	
   constraint fk_ca_editor_ui_screen_labels_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_screen_labels_ca_locales foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens_x_user_groups (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null,
	group_id int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_group_id			(group_id),
	
   constraint fk_ca_editor_ui_screens_x_ug_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_screens_x_ug_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens_x_users (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null,
	user_id int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_user_id				(user_id),
	
   constraint fk_ca_editor_ui_screens_x_u_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_screens_x_u_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screens_x_roles (
	relation_id int unsigned not null auto_increment,
	screen_id int unsigned not null,
	role_id smallint unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_screen_id			(screen_id),
	index i_role_id				(role_id),
	
   constraint fk_ca_editor_ui_screens_x_r_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict,
      
   constraint fk_ca_editor_ui_screens_x_r_role_id foreign key (role_id)
      references ca_user_roles (role_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_bundle_placements (
	placement_id int unsigned not null auto_increment,
	screen_id int unsigned not null,
	placement_code varchar(255) not null,
	bundle_name varchar(255) not null,
	
	`rank` smallint unsigned not null default 0,
    settings longtext not null,
	
	primary key 				(placement_id),
	index i_screen_id			(screen_id),
	unique index u_bundle_name	(bundle_name, screen_id, placement_code),
	
   constraint fk_ca_editor_ui_bundle_placements_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_screen_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   screen_id                      int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_screen_id			(screen_id),
   index i_type_id				(type_id),
   constraint fk_ca_editor_ui_screen_type_restrictions_screen_id foreign key (screen_id)
      references ca_editor_ui_screens (screen_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_editor_ui_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   ui_id                          int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_ui_id				(ui_id),
   index i_type_id				(type_id),
   constraint fk_ca_editor_ui_type_restrictions_ui_id foreign key (ui_id)
      references ca_editor_uis (ui_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_sets (
	set_id		int unsigned not null auto_increment,
	parent_id	int unsigned,
	hier_set_id int unsigned not null,
	user_id		int unsigned null,
    type_id     int unsigned not null,
    commenting_status tinyint unsigned not null default 0,
    tagging_status tinyint unsigned not null default 0,
    rating_status tinyint unsigned not null default 0,
	set_code    varchar(100) null,
	set_code_sort varchar(100) null,
	table_num	tinyint unsigned not null,
	access		tinyint unsigned not null default 0,	
	status		tinyint unsigned not null default 0,
	hier_left	decimal(30,20) unsigned not null,
	hier_right	decimal(30,20) unsigned not null,
    deleted     tinyint unsigned not null default 0,
    `rank`      int unsigned not null default 0,
    source_id   int unsigned,
    
	primary key (set_id),
      
	key i_user_id (user_id),
	key i_type_id (type_id),
	key i_set_code (set_code),
	key i_hier_left (hier_left),
	key i_hier_right (hier_right),
	key i_parent_id (parent_id),
	key i_hier_set_id (hier_set_id),
	key i_table_num (table_num),
	key i_source_id (source_id),
	key i_set_code_sort (set_code_sort),
      
   constraint fk_ca_sets_parent_id foreign key (parent_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_sets_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
      
   constraint fk_ca_sets_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
create index i_set_filter on ca_sets(set_id, deleted, access); 


/*==========================================================================*/
create table ca_set_labels (
	label_id	int unsigned not null auto_increment,
	set_id		int unsigned not null,
	locale_id	smallint unsigned not null,
	
	name		varchar(255) not null,
	
	primary key (label_id),
	key i_set_id (set_id),
	key i_locale_id (locale_id),
	
   constraint fk_ca_set_labels_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_set_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_set_items (
	item_id		int unsigned not null auto_increment,
	set_id		int unsigned not null,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	representation_id int unsigned null,
	annotation_id int unsigned null,
    type_id     int unsigned not null,
	`rank`		int unsigned not null default 0,
	vars        longtext not null,
	deleted     tinyint unsigned not null default 0,
	
	primary key (item_id),
	key i_set_id (set_id, deleted),
	key i_type_id (type_id),
	key i_row_id (row_id),
	key i_row_key (row_id, representation_id, annotation_id),
	key i_table_num (table_num),
	
   constraint fk_ca_set_items_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
   constraint fk_ca_set_items_rep_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
   constraint fk_ca_set_items_anno_id foreign key (annotation_id)
      references ca_representation_annotations (annotation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_set_item_labels (
	label_id	int unsigned not null auto_increment,
	item_id		int unsigned not null,
	
	locale_id	smallint unsigned not null,
	
	caption		text not null,
	
	primary key (label_id),
	key i_set_id (item_id),
	key i_locale_id (locale_id),
	
   constraint fk_ca_set_item_labels_item_id foreign key (item_id)
      references ca_set_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_set_item_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_sets_x_user_groups (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null,
	group_id int unsigned not null,
	access tinyint unsigned not null default 0,
	sdatetime int unsigned null,
	edatetime int unsigned null,
	
	primary key 				(relation_id),
	index i_set_id				(set_id),
	index i_group_id			(group_id),
	
   constraint fk_ca_sets_x_ug_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_sets_x_ug_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_sets_x_users (
	relation_id int unsigned not null auto_increment,
	set_id int unsigned not null,
	user_id int unsigned null,
	access tinyint unsigned not null default 0,
	pending_access tinyint unsigned null,
	activation_key char(36) null,
	activation_email varchar(255) null,
	sdatetime int unsigned null,
	edatetime int unsigned null,
	
	primary key 				    (relation_id),
	index i_set_id				    (set_id),
	index i_user_id			        (user_id),
	unique index u_activation_key   (activation_key),
	index i_activation_email        (activation_email),
	
   constraint fk_ca_sets_x_users_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_sets_x_users_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_item_comments (
	comment_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	user_id		int unsigned null,
	locale_id	smallint unsigned not null,
	
	media1 longblob not null,
	media2 longblob not null,
	media3 longblob not null,
	media4 longblob not null,
	
	comment		text null,
	rating		tinyint null,
	email		varchar(255) null,
	name		varchar(255) null,
	location	varchar(255) null,
	created_on	int unsigned not null,
	access		tinyint unsigned not null default 0,
	ip_addr		varchar(39) null,
	moderated_on int unsigned null,
	moderated_by_user_id int unsigned null,
	
	primary key (comment_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_email (email),
	key i_user_id (user_id),
	key i_created_on (created_on),
	key i_access (access),
	key i_moderated_on (moderated_on),
	  
   constraint fk_ca_item_comments_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
      
   constraint fk_ca_item_comments_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
      
   constraint fk_ca_item_comments_moderated_by_user_id foreign key (moderated_by_user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_item_tags (
	tag_id		int unsigned not null auto_increment,

	locale_id	smallint unsigned not null,
	tag			varchar(255) not null,
	
	primary key (tag_id),
	key u_tag (tag, locale_id),
	
   constraint fk_ca_item_tags_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_items_x_tags (
	relation_id	int unsigned not null auto_increment,
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	
	tag_id		int unsigned not null,
	
	user_id		int unsigned,
	access		tinyint unsigned not null default 0,
	
	ip_addr		varchar(39) null,
	
	created_on	int unsigned not null,
	
	moderated_on int unsigned null,
	moderated_by_user_id int unsigned null,
    `rank` int unsigned not null default 0,
	
	primary key (relation_id),
	key i_row_id (row_id),
	key i_table_num (table_num),
	key i_tag_id (tag_id),
	key i_user_id (user_id),
	key i_access (access),
	key i_created_on (created_on),
	key i_moderated_on (moderated_on),
	key i_rank (`rank`),
	
   constraint fk_ca_items_x_tags_tag_id foreign key (tag_id)
      references ca_item_tags (tag_id) on delete restrict on update restrict,
      
   constraint fk_ca_items_x_tags_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict,
      
   constraint fk_ca_items_x_tags_moderated_by_user_id foreign key (moderated_by_user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_forms (
	form_id			int unsigned not null primary key auto_increment,
	user_id			int unsigned null,
	
	form_code		varchar(100) null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	
	settings		text not null,
	
	UNIQUE KEY u_form_code (form_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num),
	
   constraint fk_ca_search_forms_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_form_labels (
	label_id		int unsigned not null primary key auto_increment,
	form_id			int unsigned null,
	locale_id		smallint unsigned not null,
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_form_id (form_id),
	KEY i_locale_id (locale_id),
	
   constraint fk_ca_search_form_labels_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict,
      
   constraint fk_ca_search_form_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_form_placements (
	placement_id	int unsigned not null primary key auto_increment,
	form_id		int unsigned not null,
	
	bundle_name 	varchar(255) not null,
	`rank`			int unsigned not null default 0,
	settings		longtext not null,
	
	KEY i_bundle_name (bundle_name),
	KEY i_rank (`rank`),
	KEY i_form_id (form_id),
	
   constraint fk_ca_search_form_placements_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_form_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned,
   form_id                        int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_form_id				(form_id),
   index i_type_id				(type_id),
   constraint fk_ca_search_form_type_restrictions_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_forms_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null,
	group_id 		int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_form_id				(form_id),
	index i_group_id			(group_id),
	
   constraint fk_ca_search_forms_x_ug_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict,
      
   constraint fk_ca_search_forms_x_ug_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_forms_x_users (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null,
	user_id 		int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_form_id			(form_id),
	index i_user_id			(user_id),
	
   constraint fk_ca_search_forms_x_u_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict,
      
   constraint fk_ca_search_forms_x_u_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_search_log (
	search_id			int unsigned not null primary key auto_increment,
	log_datetime		int unsigned not null,
	user_id				int unsigned null,
	table_num			tinyint unsigned not null,
	search_expression	varchar(1024) not null,
	num_hits			int unsigned not null,
	form_id				int unsigned null,
	ip_addr				varchar(39) null,
	details				text not null,
	execution_time 		decimal(7,3) not null,
	search_source 		varchar(40) not null,
	
	KEY i_log_datetime (log_datetime),
	KEY i_user_id (user_id),
	KEY i_form_id (form_id),
	
   constraint fk_ca_search_log_form_id foreign key (form_id)
      references ca_search_forms (form_id) on delete restrict on update restrict,
      
   constraint fk_ca_search_log_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_batch_log
(
   batch_id                       int unsigned              not null AUTO_INCREMENT,
   user_id                        int unsigned              not null,
   log_datetime                   int unsigned              not null,
   notes                          text                      not null,
   batch_type                     char(2)                   not null,
   table_num                      tinyint unsigned          not null,
   elapsed_time                   int unsigned              not null default 0,
   
   primary key (batch_id), 
   KEY i_log_datetime (log_datetime),
   KEY i_user_id (user_id),
   constraint fk_ca_batch_log_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_batch_log_items 
(
  item_id                        int unsigned                   not null AUTO_INCREMENT,
	batch_id                       int unsigned                   not null,
	row_id                         int unsigned                   not null,
	errors                         longtext                       null,
	
	primary key (item_id),
  KEY i_row_id (row_id),
  INDEX i_batch_row_id (batch_id, row_id),
  constraint fk_ca_batch_log_items_batch_id foreign key (batch_id)
    references ca_batch_log (batch_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_displays (
	display_id		int unsigned not null primary key auto_increment,
	user_id			int unsigned null,
	
	display_code	varchar(100) null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	access          tinyint unsigned not null default 0,
	
	settings		text not null,
	
	UNIQUE KEY u_display_code (display_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num),
      
   constraint fk_ca_bundle_displays_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_display_labels (
	label_id		int unsigned not null primary key auto_increment,
	display_id		int unsigned null,
	locale_id		smallint unsigned not null,
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_display_id (display_id),
	KEY i_locale_id (locale_id),
	
   constraint fk_ca_bundle_display_labels_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict,
      
   constraint fk_ca_bundle_display_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_display_placements (
	placement_id	int unsigned not null primary key auto_increment,
	display_id		int unsigned not null,
	
	bundle_name 	varchar(255) not null,
	`rank`			int unsigned not null default 0,
	settings		longtext not null,
	
	KEY i_bundle_name (bundle_name),
	KEY i_rank (`rank`),
	KEY i_display_id (display_id),
	
   constraint fk_ca_bundle_display_placements_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_displays_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	display_id 		int unsigned not null,
	group_id 		int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_group_id			(group_id),
	
   constraint fk_ca_bundle_displays_x_ug_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict,
      
   constraint fk_ca_bundle_displays_x_ug_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_displays_x_users (
	relation_id 	int unsigned not null auto_increment,
	display_id 	int unsigned not null,
	user_id 		int unsigned not null,
	access 			tinyint unsigned not null default 0,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_user_id			(user_id),
	
   constraint fk_ca_bundle_displays_x_u_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict,
      
   constraint fk_ca_bundle_displays_x_u_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_bundle_display_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned,
   table_num                      tinyint unsigned               not null,
   display_id                     int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   `rank`                           smallint unsigned              not null default 0,
   primary key (restriction_id),
   
   index i_display_id			(display_id),
   index i_type_id				(type_id),
   constraint fk_ca_bundle_display_type_restrictions_display_id foreign key (display_id)
      references ca_bundle_displays (display_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
/* Support for tour content                                                 */
/*==========================================================================*/
create table ca_tours
(
   tour_id                        int unsigned                  not null AUTO_INCREMENT,
   tour_code                      varchar(100)                  not null,
   type_id                        int unsigned                  null,
   `rank`                           int unsigned                  not null default 0,
   color                          char(6)                       null,
   icon                           longblob                      not null,
   access                         tinyint unsigned              not null default 0,
   status                         tinyint unsigned              not null default 0,
   view_count                     int unsigned                  not null default 0,
   user_id                        int unsigned                  null,
   source_id                      int unsigned,
   source_info                    longtext                      not null,
   primary key (tour_id),
   
   constraint fk_ca_tours_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_tours_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_type_id on ca_tours(type_id);
create index i_user_id on ca_tours(user_id);
create index i_tour_code on ca_tours(tour_code);
create index i_source_id on ca_tours(source_id);
create index i_view_count on ca_tours(view_count);


/*==========================================================================*/
create table ca_tour_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   tour_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name_sort                      varchar(255)                   not null,
   name                           varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_labels_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_tour_id on ca_tour_labels(tour_id);
create index i_name on ca_tour_labels(name(128));
create index i_name_sort on ca_tour_labels(name_sort(128));
create unique index u_locale_id on ca_tour_labels(tour_id, locale_id);


/*==========================================================================*/
create table ca_tour_stops
(
   stop_id                        int unsigned              not null AUTO_INCREMENT,
   parent_id                      int unsigned,
   tour_id                        int unsigned              not null,
   type_id                        int unsigned              null,
   idno                           varchar(255)              not null,
   idno_sort                      varchar(255)              not null,
   idno_sort_num                  bigint                         not null default 0,
   `rank`                           int unsigned              not null default 0,
   view_count                     int unsigned              not null default 0,
   hier_left                      decimal(30,20)            not null,
   hier_right                     decimal(30,20)            not null,
   hier_stop_id				      int unsigned 				not null,
   color                          char(6)                   null,
   icon                           longblob                  not null,
   access                         tinyint unsigned          not null default 0,
   status                         tinyint unsigned          not null default 0,
   deleted                        tinyint unsigned          not null default 0,
   primary key (stop_id),
   
   constraint fk_ca_tour_stops_tour_id foreign key (tour_id)
      references ca_tours (tour_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_tour_id on ca_tour_stops(tour_id);
create index i_type_id on ca_tour_stops(type_id);
create index i_parent_id on ca_tour_stops(parent_id);
create index i_hier_stop_id on ca_tour_stops(hier_stop_id);
create index i_hier_left on ca_tour_stops(hier_left);
create index i_hier_right on ca_tour_stops(hier_right);
create index i_idno on ca_tour_stops(idno);
create index i_idno_sort on ca_tour_stops(idno_sort);
create index i_idno_sort_num on ca_tour_stops(idno_sort_num);
create index i_view_count on ca_tour_stops(view_count);


/*==========================================================================*/
create table ca_tour_stop_labels
(
   label_id                       int unsigned              not null AUTO_INCREMENT,
   stop_id                        int unsigned              not null,
   locale_id                      smallint unsigned              not null,
   name                           varchar(8192)                 not null,
   name_sort                      varchar(255)                   not null,
   primary key (label_id),
   
   constraint fk_ca_tour_stop_labels_stop_id foreign key (stop_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stop_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_stop_id on ca_tour_stop_labels(stop_id);
create index i_name on ca_tour_stop_labels(name(128));
create index i_name_sort on ca_tour_stop_labels(name_sort(128));
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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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


/*==========================================================================*/
create table ca_tour_stops_x_tour_stops
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   stop_left_id                 int unsigned               not null,
   stop_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_stop_left_id on ca_tour_stops_x_tour_stops(stop_left_id);
create index i_stop_right_id on ca_tour_stops_x_tour_stops(stop_right_id);
create index i_type_id on ca_tour_stops_x_tour_stops(type_id);
create unique index u_all on ca_tour_stops_x_tour_stops
(
   stop_left_id,
   stop_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_tour_stops_x_tour_stops(label_left_id);
create index i_label_right_id on ca_tour_stops_x_tour_stops(label_right_id);


/*==========================================================================*/
create table ca_storage_locations_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   location_left_id                 int unsigned               not null,
   location_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   source_info                    text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
   source_info                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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


/*==========================================================================*/
create table ca_object_lots_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null default 0,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_object_lots_x_object_representations(representation_id);
create index i_lot_id on ca_object_lots_x_object_representations(lot_id);
create index i_type_id on ca_object_lots_x_object_representations(type_id);
create unique index u_all on ca_object_lots_x_object_representations
(
   type_id,
   representation_id,
   lot_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_object_representations(label_left_id);
create index i_label_right_id on ca_object_lots_x_object_representations(label_right_id);


/*==========================================================================*/
create table ca_loans_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   loan_id                        int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
   primary key (relation_id),
   constraint fk_ca_loans_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_loans_x_object_representations(representation_id);
create index i_loan_id on ca_loans_x_object_representations(loan_id);
create index i_type_id on ca_loans_x_object_representations(type_id);
create unique index u_all on ca_loans_x_object_representations
(
   type_id,
   representation_id,
   loan_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_object_representations(label_left_id);
create index i_label_right_id on ca_loans_x_object_representations(label_right_id);


/*==========================================================================*/
create table ca_movements_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   movement_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   `rank`                           int unsigned                   not null default 0,
   is_primary                     tinyint                        not null,
   primary key (relation_id),
   constraint fk_ca_movements_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_movements_x_object_representations(representation_id);
create index i_movement_id on ca_movements_x_object_representations(movement_id);
create index i_type_id on ca_movements_x_object_representations(type_id);
create unique index u_all on ca_movements_x_object_representations
(
   type_id,
   representation_id,
   movement_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_object_representations(label_left_id);
create index i_label_right_id on ca_movements_x_object_representations(label_right_id);


/*==========================================================================*/
create table ca_watch_list
(
   watch_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                      tinyint unsigned               not null,
   row_id                         int unsigned                   not null,
   user_id                        int unsigned                   not null,
   primary key (watch_id),
   
   constraint fk_ca_watch_list_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_watch_list(row_id, table_num);
create index i_user_id on ca_watch_list(user_id);
create unique index u_all on ca_watch_list(row_id, table_num, user_id);


/*==========================================================================*/
create table ca_user_notes
(
   note_id                       int unsigned                   not null AUTO_INCREMENT,
   table_num                     tinyint unsigned               not null,
   row_id                        int unsigned                   not null,
   user_id                       int unsigned                   not null,
   bundle_name                   varchar(255)                   not null,
   note                          longtext                       not null,
   created_on                    int unsigned                   not null,
   primary key (note_id),
   
   constraint fk_ca_user_notes_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
      
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_user_notes(row_id, table_num);
create index i_user_id on ca_user_notes(user_id);
create index i_bundle_name on ca_user_notes(bundle_name);


/*==========================================================================*/
create table ca_bookmark_folders 
(
  folder_id int(10) unsigned not null auto_increment,
  name varchar(255) not null,
  user_id int unsigned not null,
  `rank` smallint unsigned not null default 0,
  
  primary key (folder_id),
  
   constraint fk_ca_bookmark_folders_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_user_id on ca_bookmark_folders(user_id);


/*==========================================================================*/
create table ca_bookmarks 
(
  bookmark_id int(10) unsigned not null auto_increment,
  folder_id int unsigned not null,
  table_num tinyint unsigned not null,
  row_id int unsigned not null,
  notes text not null,
  `rank` smallint unsigned not null default 0,
  created_on int unsigned not null,
  
  primary key (bookmark_id),
  
   constraint fk_ca_bookmarks_folder_id foreign key (folder_id)
      references ca_bookmark_folders (folder_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_bookmarks(row_id);
create index i_folder_id on ca_bookmarks(folder_id);


/*==========================================================================*/
create table ca_object_checkouts (
   checkout_id	            int unsigned					not null AUTO_INCREMENT,
   group_uuid               char(36) not null,
   object_id                int unsigned not null,
   user_id                	int unsigned not null,
   created_on				int unsigned not null,
   checkout_date			int unsigned null,
   due_date					int unsigned null,
   return_date				int unsigned null,
   return_confirmation_date	int unsigned null,
   checkout_notes			text not null,
   return_notes				text not null,
   last_sent_coming_due_email int unsigned null,
   last_sent_overdue_email int unsigned null,
   last_reservation_available_email int unsigned null,
   deleted					tinyint unsigned not null,
   
   primary key (checkout_id),
   index i_group_uuid (group_uuid),
   index i_object_id (object_id),
   index i_user_id (user_id),
   index i_created_on (created_on),
   index i_checkout_date (checkout_date),
   index i_due_date (due_date),
   index i_return_date (return_date),
   index i_return_confirmation_date (return_confirmation_date),
   index i_last_sent_coming_due_email (last_sent_coming_due_email),
   index i_last_reservation_available_email (last_reservation_available_email),
   
   constraint fk_ca_object_checkouts_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_checkouts_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_sql_search_words 
(
  word_id int(10) unsigned not null auto_increment,
  word varchar(255) not null,
  stem varchar(255) not null,
  locale_id smallint(5) unsigned default null,
  
  primary key (word_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create unique index u_word on ca_sql_search_words(word);
create index i_stem on ca_sql_search_words(stem);
create index i_locale_id on ca_sql_search_words(locale_id);


/*==========================================================================*/
create table ca_sql_search_word_index (
  index_id bigint(20) unsigned not null auto_increment,
  table_num tinyint(3) unsigned not null,
  row_id int(10) unsigned not null,
  field_table_num tinyint(3) unsigned not null,
  field_num varchar(100) not null default '',
  field_container_id int unsigned null,  
  field_row_id int(10) unsigned not null,
  rel_type_id smallint unsigned not null default 0,
  word_id int(10) unsigned not null,
  boost tinyint unsigned not null default 1,
  access tinyint unsigned not null default 1,
  primary key (index_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_row_id on ca_sql_search_word_index(row_id, table_num);
create index i_word_id on ca_sql_search_word_index(word_id, access);
create index i_field_row_id on ca_sql_search_word_index(field_row_id, field_table_num);
create index i_rel_type_id on ca_sql_search_word_index(rel_type_id);
create index i_field_table_num on ca_sql_search_word_index(field_table_num);
create index i_field_num on ca_sql_search_word_index(field_num);
CREATE index i_index_table_num on ca_sql_search_word_index(word_id, table_num, row_id);
CREATE index i_index_field_table_num on ca_sql_search_word_index(word_id, table_num, field_table_num, row_id);
CREATE index i_index_field_num on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, row_id, access, boost);
CREATE index i_index_delete ON ca_sql_search_word_index(table_num, row_id, field_table_num, field_num);
CREATE INDEX i_index_field_num_container on ca_sql_search_word_index(word_id, table_num, field_table_num, field_num, field_container_id, rel_type_id, row_id, access, boost);
CREATE INDEX i_field_word on ca_sql_search_word_index(field_num, field_table_num, table_num, word_id, row_id);

/*==========================================================================*/
create table ca_sql_search_ngrams (
  word_id int(10) unsigned not null,
  ngram char(4) not null,
  seq tinyint(3) unsigned not null,
  
  index i_ngram (ngram),
  index i_word_id (word_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_media_replication_status_check (
   check_id                 int unsigned					not null AUTO_INCREMENT,
   table_num                tinyint unsigned				not null,
   row_id                   int unsigned					not null,
   target                   varchar(255)					not null,
   created_on               int unsigned                    not null,
   last_check               int unsigned                    not null,
   primary key (check_id),
   
   index i_row_id			(row_id, table_num)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_metadata_dictionary_entries (
   entry_id                 int unsigned					not null AUTO_INCREMENT,
   table_num                tinyint unsigned not null default 0,
   bundle_name              varchar(255) not null,
   settings                 longtext not null,
   primary key (entry_id),
   key i_table_num (table_num),
   key i_bundle_name (bundle_name)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_metadata_dictionary_entry_labels (
	label_id		  int unsigned not null primary key auto_increment,
	entry_id			  int unsigned null,
	locale_id		  smallint unsigned not null,
	name			    varchar(255) not null,
	name_sort		  varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,

	KEY i_entry_id (entry_id),
	KEY i_locale_id (locale_id),
   
   constraint fk_ca_md_entry_labels_entry_id foreign key (entry_id)
      references ca_metadata_dictionary_entries (entry_id) on delete restrict on update restrict,
      
   constraint fk_ca_md_entry_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_metadata_dictionary_rules (
   rule_id                  int unsigned					not null AUTO_INCREMENT,
   entry_id                 int unsigned not null,
   rule_code                varchar(100) not null,
   expression               text not null,
   rule_level               char(4) not null,
   settings                 longtext not null,
   primary key (rule_id),
   index i_entry_id (entry_id),
   unique index u_rule_code (entry_id, rule_code),
   index i_rule_code (rule_level),
   
   constraint fk_ca_metadata_dictionary_rules_entry_id foreign key (entry_id)
      references ca_metadata_dictionary_entries (entry_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table ca_metadata_dictionary_rule_violations (
   violation_id             int unsigned					not null AUTO_INCREMENT,
   rule_id                  int unsigned not null,
   table_num                tinyint unsigned not null,
   row_id               	int unsigned not null,
   created_on				int unsigned not null,
   last_checked_on			int unsigned not null,
   primary key (violation_id),
   index i_rule_id (rule_id),
   index i_row_id (row_id, table_num),
   index i_created_on (created_on),
   index i_last_checked_on (last_checked_on),
   
   constraint fk_ca_metadata_dictionary_rule_vio_rule_id foreign key (rule_id)
      references ca_metadata_dictionary_rules (rule_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_user_representation_annotations
(
  annotation_id                  int unsigned                   not null AUTO_INCREMENT,
  representation_id              int unsigned                   not null,
  idno                           varchar(255)                   null,
  locale_id                      smallint unsigned,
  user_id                        int unsigned                   null,
  session_id                     varchar(255)                   null,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_representation_id on ca_user_representation_annotations(representation_id);
create index i_locale_id on ca_user_representation_annotations(locale_id);
create index i_user_id on ca_user_representation_annotations(user_id);
create index i_session_id on ca_user_representation_annotations(session_id);
create index u_idno on ca_user_representation_annotations(idno);


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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
  `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
  `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
  `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
  `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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
  `rank`                           int unsigned                   not null default 0,
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
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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

/*==========================================================================*/

create table ca_search_indexing_queue
(
  entry_id        int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  row_id          int unsigned      not null,
  field_data      LONGTEXT          null,
  reindex         tinyint unsigned  not null default 0,
  changed_fields  LONGTEXT          null,
  options         LONGTEXT          null,
  is_unindex      tinyint unsigned  not null default 0,
  dependencies    LONGTEXT          null,
  started_on      int unsigned      null,

  primary key (entry_id),
  index i_table_num_row_id (table_num, row_id),
  index i_started_on (started_on)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_guids
(
  guid_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  row_id          int unsigned      not null,
  guid            VARCHAR(36)       not null,

  primary key (guid_id),
  index i_table_num_row_id (table_num, row_id),
  unique index u_guid (guid)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_replication_log
(
  entry_id        int unsigned      not null AUTO_INCREMENT,
  source_system_guid     VARCHAR(36)       not null,
  log_id          int unsigned      not null,
  status          char(1)           not null,
  vars            longtext          null,

  primary key (entry_id),
  index i_source_log (source_system_guid, log_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_user_sorts
(
  sort_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  user_id         int unsigned      not null,
  name            varchar(255)      not null,
  settings        longtext          not null,
  sort_type       char(1)           null,
  `rank`            smallint unsigned not null default 0,
  deleted         tinyint unsigned  not null default 0,

  primary key (sort_id),
  index i_table_num (table_num),
  index i_user_id (user_id),
  unique index u_guid (table_num, name),

  constraint fk_ca_user_sorts_user_id foreign key (user_id)
  references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_user_sort_items
(
  item_id         int unsigned      not null AUTO_INCREMENT,
  sort_id         int unsigned      not null,
  bundle_name     varchar(255)      not null,
  `rank`            smallint unsigned not null default 0,

  primary key (item_id),

  constraint fk_ca_user_sort_items_sort_id foreign key (sort_id)
  references ca_user_sorts (sort_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_rules (
  rule_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  code            varchar(20)       not null,
  settings        longtext          not null,
  user_id			    int unsigned      null,

  primary key (rule_id),
  index i_table_num (table_num),
   
   constraint fk_ca_metadata_alert_rules_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_rule_labels (
  label_id		  int unsigned not null primary key auto_increment,
  rule_id			  int unsigned null,
  locale_id		  smallint unsigned not null,
  name			    varchar(255) not null,
  name_sort		  varchar(255) not null,
  description		text not null,
  source_info		longtext not null,
  is_preferred	tinyint unsigned not null,

  KEY i_rule_id (rule_id),
  KEY i_locale_id (locale_id),
  
   constraint fk_ca_metadata_alert_rule_labels_rule_id foreign key (rule_id)
      references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_alert_rule_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_triggers (
  trigger_id      int unsigned      not null AUTO_INCREMENT,
  rule_id         int unsigned      not null,
  element_id      smallint unsigned,
  element_filters text          	not null,
  settings        longtext          not null,
  trigger_type    varchar(30)       not null,

  primary key (trigger_id),
  constraint fk_alert_rules_rule_id foreign key (rule_id)
    references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict,

  constraint fk_ca_metadata_alert_triggers_element_id foreign key (element_id)
    references ca_metadata_elements (element_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_rules_x_user_groups (
  relation_id   int unsigned not null auto_increment,
  rule_id 		  int unsigned not null,
  group_id 		  int unsigned not null,
  access 			  tinyint unsigned not null default 0,

  primary key 				(relation_id),
  index i_rule_id			(rule_id),
  index i_group_id		(group_id),
  
   constraint fk_ca_metadata_alert_rules_x_ug_rule_id foreign key (rule_id)
      references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_alert_rules_x_ug_group_id foreign key (group_id)
      references ca_user_groups (group_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_rules_x_users (
  relation_id 	int unsigned not null auto_increment,
  rule_id 	int unsigned not null,
  user_id 		int unsigned not null,
  access 			tinyint unsigned not null default 0,

  primary key 				(relation_id),
  index i_rule_id			(rule_id),
  index i_user_id			(user_id),
  
   constraint fk_ca_metadata_alert_rules_x_u_rule_id foreign key (rule_id)
      references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict,
      
   constraint fk_ca_metadata_alert_rules_x_u_user_id_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_metadata_alert_rule_type_restrictions (
  restriction_id                 int unsigned                   not null AUTO_INCREMENT,
  type_id                        int unsigned,
  table_num                      tinyint unsigned               not null,
  rule_id                        int unsigned                   not null,
  include_subtypes               tinyint unsigned               not null default 0,
  settings                       longtext                       not null,
  `rank`                           smallint unsigned              not null default 0,
  primary key (restriction_id),

  index i_rule_id			(rule_id),
  index i_type_id				(type_id),
  constraint fk_ca_metadata_alert_rule_type_restrictions_rule_id foreign key (rule_id)
    references ca_metadata_alert_rules(rule_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_notifications (
  notification_id     int unsigned        not null AUTO_INCREMENT,
  notification_type   tinyint unsigned    not null default 0,
  datetime            int unsigned        not null,
  message             longtext,
  is_system		      tinyint unsigned    not null default 0,
  notification_key    char(32)            not null default '',
  extra_data          longtext            not null,

  primary key (notification_id),

  index i_datetime (datetime),
  index i_notification_type (notification_type),
  index i_notification_key (notification_key)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_notification_subjects (
  subject_id      int unsigned        not null auto_increment,
  notification_id int unsigned        not null,
  was_read        tinyint unsigned    not null default 0,
  read_on         int unsigned        null,
  table_num       tinyint unsigned    not null,
  row_id          int unsigned        not null,
  delivery_email  tinyint unsigned    not null default 0,
  delivery_email_sent_on int unsigned null,
  delivery_inbox  tinyint unsigned    not null default 1,
  
  primary key (subject_id),
  index i_notification_id (notification_id),
  index i_table_num_row_id (table_num, row_id, read_on),
  index i_delivery_email (delivery_email, delivery_email_sent_on),
  index i_delivery_inbox (delivery_inbox),
  
   constraint fk_ca_notification_subjects_notification_id foreign key (notification_id)
      references ca_notifications (notification_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_download_log (
  log_id		      	int unsigned        not null AUTO_INCREMENT,
  log_datetime        	int unsigned        not null,
  user_id             	int unsigned        null,
  ip_addr			  	varchar(39)			null,
  table_num    			tinyint unsigned    not null,
  row_id       			int unsigned        not null,
  representation_id     int unsigned      	null,
  download_source		varchar(40)			null,

  primary key (log_id),

  constraint fk_ca_download_log_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict,

  constraint fk_ca_download_log_representation_id foreign key (representation_id)
    references ca_object_representations (representation_id) on delete restrict on update restrict,

  index i_table_num_row_id (table_num, row_id),
  index i_log_datetime (log_datetime)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_site_templates (
  template_id		    int unsigned        not null AUTO_INCREMENT,
  title					varchar(255)		not null,
  description			text				not null,
  template				longtext			not null, 
  template_code 		varchar(100)		not null,
  tags                  longtext            not null,
  deleted               tinyint unsigned    not null default 0,

  primary key (template_id),
  unique index u_title (title)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_site_pages (
  page_id		      	int unsigned        not null  AUTO_INCREMENT,
  template_id           int unsigned        not null,
  title					varchar(255)		not null,
  description			text				not null,
  path        			varchar(255)        not null,
  content				longtext			not null,
  keywords				text				not null,
  access                tinyint unsigned    not null default 0,
  deleted               tinyint unsigned    not null default 0,
  view_count            int unsigned        not null default 0,
  locale_id             smallint unsigned   null, 
  `rank`                int unsigned        not null default 0,

  primary key (page_id),
  key (template_id),
  key (path),
  key (locale_id),
  
   constraint fk_ca_site_pages_template_id foreign key (template_id)
      references ca_site_templates (template_id) on delete restrict on update restrict,
   constraint fk_ca_site_pages_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

create table ca_site_page_media (
  media_id		      	int unsigned        not null  AUTO_INCREMENT,
  page_id               int unsigned        not null,
  title					varchar(255)		not null,
  caption			    text				not null,
  idno                  varchar(255)        not null,
  idno_sort             varchar(255)        not null,
  idno_sort_num                  bigint                         not null default 0,
  media        			longblob            not null,
  media_metadata        longblob            not null,
  media_content			longtext			not null,
  md5                   varchar(32)         not null,
  mimetype              varchar(255)        null,
  original_filename     varchar(1024)       not null, 
  `rank`					int unsigned		not null default 0,
  access                tinyint unsigned    not null default 0,
  deleted               tinyint unsigned    not null default 0,

  primary key (media_id),
  key (page_id),
  key (`rank`),
  key (md5),
  key (idno),
  key (idno_sort),
  key (idno_sort_num),
  unique index u_idno (page_id, idno),
  
   constraint fk_ca_site_page_media_page_id foreign key (page_id)
      references ca_site_pages (page_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/

create table ca_history_tracking_current_values (
   tracking_id                    int unsigned                   not null AUTO_INCREMENT,
   policy                         varchar(50)                    not null,
   
   /* Row this history tracking policy current value is bound to (Aka. the "subject") */
   table_num                      tinyint unsigned               not null,
   type_id                        int unsigned                   null,
   row_id                         int unsigned                   not null,
   
   /* Row that is current value for this history tracking policy */
   current_table_num              tinyint unsigned               null,
   current_type_id                int unsigned                   null,
   current_row_id                 int unsigned                   null,
   
   /* Row that establishes current value. Eg. the relationship that links location (current value) to object (subject) */
   /* This may be the same as the target. The current value can always be derived from this tracked row. */
   tracked_table_num              tinyint unsigned               null,
   tracked_type_id                int unsigned                   null,
   tracked_row_id                 int unsigned                   null,
   
   is_future                      int unsigned                   null,
   
   value_sdatetime                decimal(40,20)                 null,
   value_edatetime                decimal(40,20)                 null,
   
   primary key (tracking_id),

   index i_policy			    (policy),
   index i_row_id				(row_id),
   
   /* Only one current value per subject per policy */
   unique index u_all           (row_id, table_num, policy, type_id, is_future), 
   
   index i_current              (current_row_id, current_table_num, current_type_id), 
   index i_tracked              (tracked_row_id, tracked_table_num, tracked_type_id),
   index i_datetime             (value_sdatetime, value_edatetime, table_num, row_id),
   index i_is_future            (is_future)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/

create table ca_history_tracking_current_value_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   tracking_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   value                          varchar(8192)                  not null,
   value_sort                     varchar(1024)                  not null,
   source_info                    longtext                       not null,
   is_preferred                   tinyint unsigned               not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   access                         tinyint unsigned               not null default 0,
   
   primary key (label_id),
   constraint fk_ca_history_tracking_current_value_labels_type_id foreign key (type_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_history_tracking_current_value_labels_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict,
   constraint fk_ca_history_tracking_current_value_labels_tracking_id foreign key (tracking_id)
      references ca_history_tracking_current_values (tracking_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create index i_name on ca_history_tracking_current_value_labels(value(128));
create index i_object_id on ca_history_tracking_current_value_labels(tracking_id);
create unique index u_all on ca_history_tracking_current_value_labels
(
   tracking_id,
   value(255),
   type_id,
   locale_id
);
create index i_name_sort on ca_history_tracking_current_value_labels(value_sort(128));
create index i_type_id on ca_history_tracking_current_value_labels(type_id);
create index i_locale_id on ca_history_tracking_current_value_labels(locale_id);
create index i_effective_date ON ca_history_tracking_current_value_labels(sdatetime, edatetime);

/*==========================================================================*/
create table ca_persistent_cache (
    cache_key         char(32) not null primary key,
    cache_value       longblob not null,
    created_on        int unsigned not null,
    updated_on        int unsigned not null,
    namespace         varchar(100) not null default '',

	KEY i_namespace (namespace),
	KEY i_updated_on (updated_on)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table if not exists ca_ip_bans (
   ban_id                    int unsigned                   not null AUTO_INCREMENT,
   reason                    varchar(255)                   not null,
   created_on                int unsigned                   not null,
   expires_on                int unsigned                   null,
   
   ip_addr		             varchar(39)                    not null,
   
   primary key (ban_id),

   index i_created_on			    (created_on),
   index i_expires_on			    (expires_on),
   index i_ip_addr				    (ip_addr)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/
create table if not exists ca_ip_whitelist (
   whitelist_id              int unsigned                   not null AUTO_INCREMENT,
   reason                    varchar(255)                   not null,
   created_on                int unsigned                   not null,
   expires_on                int unsigned                   null,
   
   ip_addr		             varchar(39)                    not null,
   
   primary key (whitelist_id),

   index i_created_on			    (created_on),
   index i_expires_on			    (expires_on),
   index i_ip_addr				    (ip_addr)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_representation_transcriptions (
   transcription_id          int unsigned                   not null AUTO_INCREMENT,
   representation_id         int unsigned                   not null,
   transcription             longtext                       not null,
   created_on                int unsigned                   not null,
   completed_on              int unsigned                   null,
   validated_on              int unsigned                   null,
   is_primary                tinyint unsigned               not null default 0,
   
   ip_addr		             varchar(39)                    not null,
   user_id                   int unsigned                   null,
   
   primary key (transcription_id),

   index i_created_on			    (created_on),
   index i_completed_on      	    (completed_on, is_primary),
   index i_validated_on      	    (validated_on),
   index i_ip_addr				    (ip_addr),
   unique index i_user_id           (user_id, representation_id),
   index i_representation_id        (representation_id),
   
   constraint fk_ca_representation_transcriptions_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
  
   constraint fk_ca_representation_transcriptions_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


/*==========================================================================*/

create table ca_user_export_downloads (
  download_id		    int unsigned        not null AUTO_INCREMENT,
  created_on        	int unsigned        not null,
  generated_on        	int unsigned        null,
  user_id             	int unsigned        null,
  download_type    		varchar(30)	   		not null,
  metadata				longtext			not null,
  status		 		varchar(30)    		not null default 'QUEUED',
  downloaded_on			int unsigned		null,
  error_code            smallint unsigned   not null default 0,
  export_file           blob            not null,

  primary key (download_id),

  constraint fk_ca_export_download_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict,

  index i_user_id (user_id)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/
/* Schema update tracking                                                   */
/*==========================================================================*/
create table ca_schema_updates (
	version_num		int unsigned not null,
	datetime		int unsigned not null,
	
	UNIQUE KEY u_version_num (version_num)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/* Indicate up to what migration this schema definition covers */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (197, unix_timestamp());
