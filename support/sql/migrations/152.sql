/*
	Date: 31 January 2018
	Migration: 152
	Description: Extend SQL search index all for container-aware searching
*/

ALTER TABLE ca_search_log MODIFY ip_addr VARCHAR(39);
ALTER TABLE ca_download_log MODIFY ip_addr VARCHAR(39);
ALTER TABLE ca_items_x_tags MODIFY ip_addr VARCHAR(39);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (152, unix_timestamp());
