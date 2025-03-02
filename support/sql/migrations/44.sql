/* 
	Date: 31 July 2011
	Migration: 44
	Description:
*/

/*==========================================================================*/
/* Add identifier field to ca_object_representations */

ALTER TABLE ca_object_representations ADD COLUMN idno varchar(255) not null;
ALTER TABLE ca_object_representations ADD COLUMN idno_sort varchar(255) not null;

create index i_idno on ca_object_representations(idno);
create index i_idno_sort on ca_object_representations(idno_sort);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (44, unix_timestamp());
