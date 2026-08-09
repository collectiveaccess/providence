/*
	Date: 22 December 2025
	Migration: 208
	Description: Add item_id field to support list-based tags
*/

/*==========================================================================*/

ALTER TABLE ca_items_x_tags MODIFY COLUMN tag_id int unsigned null;
ALTER TABLE ca_items_x_tags ADD COLUMN item_id int unsigned null;
CREATE INDEX i_item_id ON ca_items_x_tags(item_id);
ALTER TABLE ca_items_x_tags add constraint fk_ca_items_x_tags_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (208, unix_timestamp());
