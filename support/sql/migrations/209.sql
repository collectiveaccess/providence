/*
	Date: 17 January 2026
	Migration: 209
	Description: Support for hierarchical lots (ca_object_lots)
*/

/*==========================================================================*/

ALTER TABLE ca_object_lots ADD COLUMN parent_id int unsigned null;
ALTER TABLE ca_object_lots ADD COLUMN hier_lot_id int unsigned not null;
ALTER TABLE ca_object_lots ADD COLUMN hier_left decimal(30,20) unsigned not null;
ALTER TABLE ca_object_lots ADD COLUMN hier_right decimal(30,20) unsigned not null;
CREATE index i_parent_id ON ca_object_lots(parent_id);
CREATE index i_hier_left ON ca_object_lots(hier_left);
CREATE index i_hier_right ON ca_object_lots(hier_right);

ALTER TABLE ca_object_lots add constraint fk_ca_object_lots_parent_id foreign key (parent_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict;
      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (209, unix_timestamp());
