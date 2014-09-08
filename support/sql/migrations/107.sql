/*
	Date: 14 August 2014
	Migration: 107
	Description: add current location fields to ca_objects
*/

drop index i_name on ca_representation_annotation_labels;
drop index i_name_sort on ca_representation_annotation_labels;
drop index u_all on ca_representation_annotation_labels;
alter table ca_representation_annotation_labels modify column name varchar(2048) not null default '';
alter table ca_representation_annotation_labels modify column name_sort varchar(2048) not null default '';
create index i_name on ca_representation_annotation_labels(name(128));
create index i_name_sort on ca_representation_annotation_labels(name_sort(128));
create unique index u_all on ca_representation_annotation_labels
(
   name(128),
   locale_id,
   type_id,
   annotation_id
);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (107, unix_timestamp());
