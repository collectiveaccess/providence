<<<<<<< HEAD
/*
	Date: 19 February 2013
	Migration: 80
	Description: Data structures for export
*/

create table ca_data_exporters (
	exporter_id				int unsigned			not null AUTO_INCREMENT,
	exporter_code			varchar(100)			not null,
	table_num				tinyint unsigned		not null,
	settings				longtext				not null,
	primary key (exporter_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_exporter_code on ca_data_exporters(exporter_code);
create index i_table_num on ca_data_exporters(table_num);

create table ca_data_exporter_items (
	item_id 				int unsigned			not null AUTO_INCREMENT,
	parent_id 				int unsigned			null,
	exporter_id 			int unsigned			not null,
	element					varchar(1024)			not null,
	context 				varchar(1024)			null,
	source 					varchar(1024)			null,
	settings				longtext				not null,
	hier_item_id			int unsigned			not null,
	hier_left				decimal(30,20) unsigned	not null,
	hier_right				decimal(30,20) unsigned	not null,
	rank					int unsigned			not null default 0,

	primary key (item_id),

	constraint fk_ca_data_exporter_items_exporter_id foreign key (exporter_id)
		references ca_data_exporters (exporter_id) on delete restrict on update restrict,
	constraint fk_ca_data_exporter_items_parent_id foreign key (parent_id)
		references ca_data_exporter_items (item_id) on delete restrict on update restrict

)  engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_parent_id on ca_data_exporter_items(parent_id);
create index i_exporter_id on ca_data_exporter_items(exporter_id);
create index i_hier_left on ca_data_exporter_items(hier_left);
create index i_hier_right on ca_data_exporter_items(hier_right);
create index i_hier_item_id on ca_data_exporter_items(hier_item_id);

create table ca_data_exporter_labels (
	label_id				int unsigned			not null AUTO_INCREMENT,
	exporter_id				int unsigned			not null,
	locale_id				smallint unsigned		not null,
	name					varchar(255) 			not null,
	name_sort				varchar(255)			not null,
	description				text 					not null,
	source_info				longtext 				not null,
	is_preferred			tinyint unsigned 		not null,

	primary key (label_id),

	constraint fk_ca_data_exporter_labels_exporter_id foreign key (exporter_id)
		references ca_data_exporters (exporter_id) on delete restrict on update restrict,

	constraint fk_ca_data_exporter_labels_locale_id foreign key (locale_id)
		references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_exporter_id on ca_data_exporter_labels(exporter_id);
create index i_locale_id on ca_data_exporter_labels(locale_id);
create index i_name_sort on ca_data_exporter_labels(name_sort);
create unique index u_all on ca_data_exporter_labels
(
	exporter_id,
	locale_id,
	name,
	is_preferred
);
=======
/* 
	Date: 3 March 2013
	Migration: 80
	Description:
*/


/* -------------------------------------------------------------------------------- */

/*==========================================================================*/
create table if not exists ca_data_import_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   occurred_on                    int unsigned                   not null,
   user_id                        int unsigned,
   description                    text                           not null,
   type_code                      char(10)                       not null,
   source                         text                           not null,
   primary key (event_id),
   
   index i_user_id(user_id),
   
   constraint fk_ca_data_import_events_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_data_import_items
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
   
   index i_event_id (event_id),
   index i_row_id (table_num, row_id),
   
   constraint fk_ca_data_import_items_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table if not exists ca_data_import_event_log
(
   log_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                    int unsigned                   not null,
   item_id                      int unsigned                   null,
   type_code                  char(10)                       not null,
   date_time                  int unsigned                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   
   index i_event_id (event_id),
   index i_item_id (item_id),
   
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

>>>>>>> origin/master

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (80, unix_timestamp());
