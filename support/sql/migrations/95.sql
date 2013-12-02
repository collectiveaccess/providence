/*
	Date: 1 December 2013
	Migration: 95
	Description: Add media replication and subtitle/caption tables
*/

create table ca_media_replication_status_check (
   check_id                 int unsigned					not null AUTO_INCREMENT,
   table_num                tinyint unsigned				not null,
   row_id                   int unsigned					not null,
   target                   varchar(255)					not null,
   created_on               int unsigned                    not null,
   last_check               int unsigned                    not null,
   primary key (check_id),
   
   index i_row_id			(row_id, table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


create table ca_object_representation_captions (
	caption_id			int unsigned not null auto_increment,
	representation_id	int unsigned not null references ca_object_representations(representation_id),
	locale_id			smallint unsigned not null,
	caption_file		longblob not null,
	caption_content		longtext not null,
	primary key (caption_id),
      
    index i_representation_id	(representation_id),
    index i_locale_id			(locale_id),
   constraint fk_ca_object_rep_captiopns_locale_id foreign key (locale_id)
      references ca_locales (locale_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (95, unix_timestamp());