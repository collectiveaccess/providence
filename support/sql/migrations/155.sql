/*
	Date: 6 September 2018
	Migration: 155
	Description: Allow tags to have arbitrary sort order
*/



ALTER TABLE ca_items_x_tags ADD COLUMN rank int unsigned not null default 0;
CREATE INDEX i_rank ON ca_items_x_tags(rank);
UPDATE ca_items_x_tags SET rank = relation_id;

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (155, unix_timestamp());
