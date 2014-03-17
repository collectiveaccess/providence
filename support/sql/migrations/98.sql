/*
	Date: 28 February 2014
	Migration: 98
	Description: Add source_id fields
*/

ALTER TABLE ca_loans ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_movements ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_object_lots ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_storage_locations ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_tours ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_object_representations ADD COLUMN source_id int unsigned null;
ALTER TABLE ca_list_items ADD COLUMN source_id int unsigned null;

ALTER TABLE ca_tours ADD COLUMN source_info longtext not null;
ALTER TABLE ca_object_representations ADD COLUMN source_info longtext not null;
ALTER TABLE ca_list_items ADD COLUMN source_info longtext not null;

ALTER TABLE ca_loans add constraint fk_ca_loans_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_movements add constraint fk_ca_movements_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_object_lots add constraint fk_ca_object_lots_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_storage_locations add constraint fk_ca_storage_locations_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_tours add constraint fk_ca_tours_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_object_representations add constraint fk_ca_object_representations_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;
ALTER TABLE ca_list_items add constraint fk_ca_list_items_source_id foreign key (source_id)
      references ca_list_items (item_id) on delete restrict on update restrict;

create index i_source_id on ca_loans(source_id);
create index i_source_id on ca_movements(source_id);
create index i_source_id on ca_object_lots(source_id);
create index i_source_id on ca_storage_locations(source_id);
create index i_source_id on ca_tours(source_id);
create index i_source_id on ca_object_representations(source_id);
create index i_source_id on ca_list_items(source_id);


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (98, unix_timestamp());