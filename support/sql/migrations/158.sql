/*
	Date: 9 March 2019
	Migration: 158
	Description:    Set null change log units
	                Fix schema issues in older systems
	                Add labels for ca_metadata_dictionary_entries
	                Extend SQL Search field_num
*/

/*==========================================================================*/
create table ca_persistent_cache (
    cache_key         char(32) not null primary key,
    cache_value       longblob not null,
    created_on        int unsigned not null,
    updated_on        int unsigned not null,
    namespace         varchar(100) not null default '',

	KEY i_namespace (namespace),
	KEY i_updated_on (updated_on)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (158, unix_timestamp());
