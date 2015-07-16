/*
	Date: 23 September 2014
	Migration: 110
	Description: pseudo-migration to notify users of authentication backend rewrite
*/

alter table ca_representation_annotation_labels modify column name text not null;
alter table ca_representation_annotation_labels modify column name_sort text not null;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (110, unix_timestamp());