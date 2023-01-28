/*
	Date: 29 December 2022
	Migration: 181
	Description:  Add sortable labels to history current value tracking
*/

/*==========================================================================*/


create table ca_history_tracking_current_value_labels
(
   label_id                       int unsigned                   not null AUTO_INCREMENT,
   tracking_id                    int unsigned                   not null,
   locale_id                      smallint unsigned              not null,
   type_id                        int unsigned                   null,
   value                          varchar(8192)                 not null,
   value_sort                     varchar(255)                   not null,
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

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (181, unix_timestamp());
