/* 
	Date: 9 March 2012
	Migration: 56
	Description:
*/

/* Allow for icons associated with storage locations */
ALTER TABLE ca_storage_locations ADD COLUMN icon longblob not null;
ALTER TABLE ca_storage_locations ADD COLUMN color char(6) null;
ALTER TABLE ca_storage_locations ADD COLUMN idno varchar(255) not null;
ALTER TABLE ca_storage_locations ADD COLUMN idno_sort varchar(255) not null;

create index idno on ca_storage_locations(idno);
create index idno_sort on ca_storage_locations(idno_sort);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (56, unix_timestamp());