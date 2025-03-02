/*
	Date: 8 February 2021
	Migration: 168
	Description:    Add is_preferred field for metadata element labels
*/

/*==========================================================================*/

create table ca_object_representation_sidecars (
	sidecar_id			int unsigned not null auto_increment,
	representation_id	int unsigned not null references ca_object_representations(representation_id),
	sidecar_file		longblob not null,
	sidecar_content		longtext not null,
	notes               text not null,
    mimetype            varchar(255) null,
	primary key (sidecar_id),
      
    index i_representation_id	(representation_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (168, unix_timestamp());
