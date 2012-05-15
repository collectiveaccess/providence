/* 
	Date: 9 September 2009
	Migration: 1
	Description:
*/

/* -------------------------------------------------------------------------------- */
/* The new ca_schema_updates table records updates to schema:
		- migration_num is the # of the update applied, as defined in support/sql/migrations
		- datetime is a Unix timestamp recording when the update was applied
*/
CREATE TABLE IF NOT EXISTS ca_schema_updates (
	version_num		int unsigned not null,
	datetime		int unsigned not null,
	
	UNIQUE KEY u_version_num (version_num)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Make type_id NULLable to support polyhierarchies for places and list items
	All relations in these tables with type_id = NULL are considered to be non-preferred parent-child
	relationships where the left_id is the parent and right_id is the child
*/
ALTER TABLE ca_places_x_places MODIFY COLUMN type_id smallint unsigned null;
ALTER TABLE ca_list_items_x_list_items MODIFY COLUMN type_id smallint unsigned null;

/* -------------------------------------------------------------------------------- */
/*
	BrowseEngine support tables
*/
DROP TABLE IF EXISTS ca_browses;
CREATE TABLE ca_browses (
	browse_id	int unsigned not null primary key auto_increment,
	cache_key	char(32) not null,
	params		longtext not null,
	table_num	tinyint unsigned not null,
	created_on	int unsigned not null,
	last_used	int unsigned not null,
	
	UNIQUE KEY u_cache_key (cache_key, table_num),
	KEY i_created_on (created_on),
	KEY i_last_used (last_used),
	KEY i_table_num (table_num)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_browse_results;
CREATE TABLE ca_browse_results (
	browse_id	int unsigned not null references ca_browses(browse_id),
	row_id		int unsigned not null,
	
	UNIQUE KEY u_row (browse_id, row_id),
	KEY i_row_id (row_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Advanced search form tables
*/
DROP TABLE IF EXISTS ca_search_forms;
CREATE TABLE ca_search_forms (
	form_id			int unsigned not null primary key auto_increment,
	user_id			int unsigned null references ca_users(user_id),
	
	form_code		varchar(100) not null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	
	settings		text not null,
	
	UNIQUE KEY u_form_code (form_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_search_form_labels;
CREATE TABLE ca_search_form_labels (
	label_id		int unsigned not null primary key auto_increment,
	form_id			int unsigned null references ca_search_forms(form_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_form_id (form_id),
	KEY i_locale_id (locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_search_form_bundles;
CREATE TABLE ca_search_form_bundles (
	form_bundle_id	int unsigned not null primary key auto_increment,
	form_id			int unsigned not null references ca_search_forms(form_id),
	
	bundle_name 	varchar(255) not null,
	rank			int unsigned not null,
	settings		longtext not null,
	
	UNIQUE KEY u_placement (form_id, bundle_name),
	KEY i_bundle_name (bundle_name),
	KEY i_rank (rank),
	KEY i_form_id (form_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_search_forms_x_user_groups;
CREATE TABLE ca_search_forms_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	form_id 		int unsigned not null references ca_search_forms(form_id),
	group_id 		int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_form_id				(form_id),
	index i_group_id			(group_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Bundle "display" tables
*/
DROP TABLE IF EXISTS ca_bundle_displays;
CREATE TABLE ca_bundle_displays (
	display_id		int unsigned not null primary key auto_increment,
	user_id			int unsigned null references ca_users(user_id),
	
	display_code	varchar(100) not null,
	table_num		tinyint unsigned not null,
	
	is_system		tinyint unsigned not null,
	
	settings		text not null,
	
	UNIQUE KEY u_display_code (display_code),
	KEY i_user_id (user_id),
	KEY i_table_num (table_num)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_bundle_display_labels;
CREATE TABLE ca_bundle_display_labels (
	label_id		int unsigned not null primary key auto_increment,
	display_id		int unsigned null references ca_bundle_displays(display_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_display_id (display_id),
	KEY i_locale_id (locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_bundle_display_placements;
CREATE TABLE ca_bundle_display_placements (
	placement_id	int unsigned not null primary key auto_increment,
	display_id		int unsigned not null references ca_bundle_displays(display_id),
	
	bundle_name 	varchar(255) not null,
	rank			int unsigned not null,
	settings		longtext not null,
	
	UNIQUE KEY u_placement (display_id, bundle_name),
	KEY i_bundle_name (bundle_name),
	KEY i_rank (rank),
	KEY i_display_id (display_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_bundle_displays_x_user_groups;
CREATE TABLE ca_bundle_displays_x_user_groups (
	relation_id 	int unsigned not null auto_increment,
	display_id 		int unsigned not null references ca_bundle_displays(display_id),
	group_id 		int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_display_id			(display_id),
	index i_group_id			(group_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Data mapping tables
*/
DROP TABLE IF EXISTS ca_data_export_mappings;		/* get rid of outdated table */
DROP TABLE IF EXISTS ca_bundle_mappings;
CREATE TABLE ca_bundle_mappings (
	mapping_id		int unsigned not null primary key auto_increment,
	
	direction		char(1) not null,				/* I=import, E=export */
	mapping_code	varchar(100) not null,
	target			varchar(100) not null,			/* indicates what format this mapping maps to/from; eg. target=EAD */
	settings		text not null,
	
	UNIQUE KEY u_mapping_code (mapping_code),
	KEY i_target (target)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_bundle_mapping_labels;
CREATE TABLE ca_bundle_mapping_labels (
	label_id		int unsigned not null primary key auto_increment,
	mapping_id		int unsigned null references ca_bundle_mappings(mapping_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_mapping_id (mapping_id),
	KEY i_locale_id (locale_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS ca_bundle_mapping_relationships;
CREATE TABLE ca_bundle_mapping_relationships (
	relation_id			int unsigned not null primary key auto_increment,
	mapping_id			int unsigned not null references ca_bundle_mappings(mapping_id),
	
	bundle_name 		varchar(255) not null,		/* Bundle name of CA end of relation */
	destination			varchar(1024) not null,		/* Specification for source-end of relation; can be XPath for XML targets, integer column numbers for tab-delimited targets, etc. */
	
	settings			text not null,
	
	UNIQUE KEY u_all(mapping_id, bundle_name, destination(255)),
	KEY i_mapping_id (mapping_id),
	KEY i_bundle_name (bundle_name),
	KEY i_destination (destination(255))
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Multi-file representation tables
*/
DROP TABLE IF EXISTS ca_object_representation_multifiles;
CREATE TABLE ca_object_representation_multifiles (
	multifile_id		int unsigned not null primary key auto_increment,
	representation_id	int unsigned not null references ca_object_representations(representation_id),
	resource_path		text not null,
	media				longtext not null,
	media_metadata		longtext not null,
	media_content		longtext not null,
	rank				int unsigned not null,	
	
	KEY i_resource_path (resource_path(255)),
	KEY i_representation_id (representation_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Companion to ca_item_views for performance
*/
DROP TABLE IF EXISTS ca_item_view_counts;
CREATE TABLE ca_item_view_counts (
	table_num	tinyint unsigned not null,
	row_id		int unsigned not null,
	view_count	int unsigned not null,
	
	KEY u_row (row_id, table_num),
	KEY i_row_id (row_id),
	KEY i_table_num (table_num),
	KEY i_view_count (view_count)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */
/*
	Logging tables
*/
DROP TABLE IF EXISTS ca_search_log;
CREATE TABLE ca_search_log (
	search_id			int unsigned not null primary key auto_increment,
	log_datetime		int unsigned not null,
	user_id				int unsigned null references ca_users(user_id),
	table_num			tinyint unsigned not null,
	search_expression	varchar(1024) not null,
	num_hits			int unsigned not null,
	form_id				int unsigned null references ca_search_forms(form_id),
	ip_addr				char(15) null,
	details				text not null,
	
	KEY i_log_datetime (log_datetime),
	KEY i_user_id (user_id),
	KEY i_form_id (form_id)
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */
/* Template support in primary editable items */
/* Content of rows marked as is_template can be used to prefill newly created items of the same type */
ALTER TABLE ca_objects ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_object_lots ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_entities ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_places ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_occurrences ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_collections ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_storage_locations ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_object_events ADD COLUMN is_template tinyint unsigned not null;
ALTER TABLE ca_object_lot_events ADD COLUMN is_template tinyint unsigned not null;


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (1, unix_timestamp());