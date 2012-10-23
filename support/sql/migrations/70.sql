/*
	Date: 22 October 2012
	Migration: 70
	Description: Data structures for simplified import
*/  

create table ca_data_importers (
	importer_id				int unsigned			not null AUTO_INCREMENT,
	importer_code			varchar(100)			not null,
	table_num				tinyint unsigned		not null,
	settings				longtext					not null,
	primary key (importer_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_importer_code on ca_data_importers(importer_code);
create index i_table_num on ca_data_importers(table_num);

create table ca_data_importer_labels (
	label_id				int unsigned			not null AUTO_INCREMENT,
	importer_id				int unsigned			not null,
	locale_id				smallint unsigned		not null,
	name					varchar(255) 			not null,
	name_sort				varchar(255)			not null,
	description				text 					not null,
	source_info				longtext 				not null,
	is_preferred			tinyint unsigned 		not null,

	primary key (label_id),

	constraint fk_ca_data_importer_labels_importer_id foreign key (importer_id)
		references ca_data_importers (importer_id) on delete restrict on update restrict,

	constraint fk_ca_data_importer_labels_locale_id foreign key (locale_id)
		references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_importer_id on ca_data_importer_labels(importer_id);
create index i_locale_id on ca_data_importer_labels(locale_id);
create index i_name_sort on ca_data_importer_labels(name_sort);
create unique index u_all on ca_data_importer_labels
(
	importer_id,
	locale_id,
	name,
	is_preferred
);

create table ca_data_importer_groups (
	group_id				int unsigned			not null AUTO_INCREMENT,
	importer_id 			int unsigned			not null,
	group_code				varchar(100)			not null,
	table_num 				tinyint unsigned		not null,
	settings				longtext					not null,

	primary key (group_id),

	constraint fk_ca_data_importer_groups_importer_id foreign key (importer_id)
		references ca_data_importers (importer_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_importer_id on ca_data_importer_groups(importer_id);
create unique index u_group_code on ca_data_importer_groups(importer_id, group_code);
create index i_table_num on ca_data_importer_groups(table_num);

create table ca_data_importer_items (
	item_id 				int unsigned			not null AUTO_INCREMENT,
	importer_id 			int unsigned			not null,
	group_id 				int unsigned			null,
	source 					varchar(1024)			not null,
	destination				varchar(1024)			not null,
	settings				longtext				not null,

	primary key (item_id),

	constraint fk_ca_data_importer_items_importer_id foreign key (importer_id)
		references ca_data_importers (importer_id) on delete restrict on update restrict,
	constraint fk_ca_data_importer_items_group_id foreign key (group_id)
		references ca_data_importer_groups (group_id) on delete restrict on update restrict

)  engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_importer_id on ca_data_importer_items(importer_id);
create index i_group_id on ca_data_importer_items(group_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (70, unix_timestamp());
