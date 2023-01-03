/*
	Date: 13 February 2022
	Migration: 176
	Description:  Add numeric idno sort value - used for idno range searches
*/

/*==========================================================================*/

ALTER TABLE ca_objects ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_objects(idno_sort_num);

ALTER TABLE ca_list_items ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_list_items(idno_sort_num);

ALTER TABLE ca_storage_locations ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_storage_locations(idno_sort_num);

ALTER TABLE ca_entities ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_entities(idno_sort_num);

ALTER TABLE ca_object_representations ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_object_representations(idno_sort_num);

ALTER TABLE ca_occurrences ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_occurrences(idno_sort_num);

ALTER TABLE ca_collections ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_collections(idno_sort_num);

ALTER TABLE ca_places ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_places(idno_sort_num);

ALTER TABLE ca_loans ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_loans(idno_sort_num);

ALTER TABLE ca_movements ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_movements(idno_sort_num);

ALTER TABLE ca_tour_stops ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_tour_stops(idno_sort_num);

ALTER TABLE ca_site_page_media ADD COLUMN idno_sort_num bigint unsigned not null default 0;
create index i_idno_sort_num on ca_site_page_media(idno_sort_num);

ALTER TABLE ca_object_lots ADD COLUMN idno_stub_sort_num bigint unsigned not null default 0;
create index i_idno_stub_sort_num on ca_object_lots(idno_stub_sort_num);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (176, unix_timestamp());
