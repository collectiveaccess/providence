/* 
	Date: 24 August 2011
	Migration: 45
	Description:
*/

/*==========================================================================*/
/* Add field to support search on relationship types */

alter table ca_sql_search_word_index add column rel_type_id smallint unsigned not null default '0';
create index i_rel_type_id on ca_sql_search_word_index(rel_type_id);

alter table ca_mysql_fulltext_search add column rel_type_id smallint unsigned not null default '0';
create index i_rel_type_id on ca_mysql_fulltext_search(rel_type_id);

/*==========================================================================*/
/* Restructuring of bundle mapping tables */

drop table ca_bundle_mappings;
drop table ca_bundle_mapping_labels;
drop table ca_bundle_mapping_relationships;

create table ca_bundle_mappings (
	mapping_id		int unsigned not null primary key auto_increment,
	
	direction		char(1) not null,
	table_num		tinyint unsigned not null,
	mapping_code	varchar(100) null,
	target			varchar(100) not null,
    access          tinyint unsigned not null,
	settings		text not null,
	
	UNIQUE KEY u_mapping_code (mapping_code),
	KEY i_target (target)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


create table ca_bundle_mapping_labels (
	label_id		int unsigned not null primary key auto_increment,
	mapping_id		int unsigned null references ca_bundle_mappings(mapping_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_mapping_id (mapping_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


create table ca_bundle_mapping_groups (
	group_id			int unsigned not null primary key auto_increment,
	mapping_id			int unsigned not null references ca_bundle_mappings(mapping_id),
	
	group_code			varchar(100) not null,
	
	settings			text not null,
	notes				text not null,
	
	KEY i_mapping_id (mapping_id),
	UNIQUE KEY i_group_code (group_code, mapping_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


create table ca_bundle_mapping_group_labels (
	label_id		int unsigned not null primary key auto_increment,
	group_id		int unsigned null references ca_bundle_mapping_groups(group_id),
	locale_id		smallint unsigned not null references ca_locales(locale_id),
	name			varchar(255) not null,
	name_sort		varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,
	
	KEY i_group_id (group_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


create table ca_bundle_mapping_rules (
	rule_id				int unsigned not null primary key auto_increment,
	group_id			int unsigned not null references ca_bundle_mapping_groups(group_id),
	
	ca_path 				varchar(1024) not null,
	external_path		varchar(1024) not null,	
	
	settings			text not null,
	notes				text not null,
	
	KEY i_group_id (group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (45, unix_timestamp());