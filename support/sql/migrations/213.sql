/*
	Date: 25 August 2026
	Migration: 213
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_list_items MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_entities MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_storage_locations MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_object_representations MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_occurrences MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_collections MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_places MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_loans MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_movements MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_objects MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_tour_stops MODIFY COLUMN idno_sort varchar(1024) not null default '';
ALTER TABLE ca_object_lots MODIFY COLUMN idno_stub_sort varchar(1024) not null default '';
ALTER TABLE ca_sets MODIFY COLUMN set_code_sort varchar(1024) null;
ALTER TABLE ca_site_page_media MODIFY COLUMN idno_sort varchar(1024) not null default '';

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (213, unix_timestamp());
