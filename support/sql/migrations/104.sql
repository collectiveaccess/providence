/*
	Date: 27 June 2014
	Migration: 104
	Description: add multifiles support for attribute values
*/

create index i_source_info on ca_attribute_values(source_info(255));

create table ca_attribute_value_multifiles (
	multifile_id		int unsigned not null auto_increment,
	value_id	        int unsigned not null references ca_attribute_values(value_id),
	resource_path		text not null,
	media				longblob not null,
	media_metadata		longblob not null,
	media_content		longtext not null,
	rank				int unsigned not null default 0,	
	primary key (multifile_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_resource_path on ca_attribute_value_multifiles(resource_path(255));
create index i_value_id on ca_attribute_value_multifiles(value_id);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (104, unix_timestamp());