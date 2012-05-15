/* 
	Date: 28 September 2009
	Migration: 5
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Extend maximum length of labels for occurrences
*/

drop index u_all on ca_occurrence_labels;
drop index i_name_sort on ca_occurrence_labels;
ALTER TABLE ca_occurrence_labels MODIFY COLUMN name varchar(1024) not null;
ALTER TABLE ca_occurrence_labels MODIFY COLUMN name_sort varchar(1024) not null;
create unique index u_all on ca_occurrence_labels
(
   occurrence_id,
   name(255),
   type_id,
   locale_id
);

create index i_name_sort on ca_occurrence_labels
(
   name_sort(255)
);

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (5, unix_timestamp());