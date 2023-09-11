/*
	Date: 11 June 2023
	Migration: 185
	Description:  Add source_id for ca_sets (required for consortium sync)
*/
/*==========================================================================*/

ALTER TABLE ca_sets ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_sets ADD CONSTRAINT fk_ca_sets_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
CREATE INDEX i_source_id ON ca_sets(source_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (185, unix_timestamp());
