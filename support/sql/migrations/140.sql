/*
	Date: 16 September 2016
	Migration: 140
	Description: Add indexing to improve browse performance
*/

/*==========================================================================*/

create index i_row_table_num on ca_attributes(row_id, table_num);
create index i_attr_element on ca_attribute_values(attribute_id, element_id);
create index i_obj_filter on ca_objects(object_id, deleted, access); 
create index i_entity_filter on ca_entities(entity_id, deleted, access); 
create index i_place_filter on ca_places(place_id, deleted, access); 
create index i_occ_filter on ca_occurrences(occurrence_id, deleted, access); 
create index i_collection_filter on ca_collections(collection_id, deleted, access); 
create index i_loan_filter on ca_loans(loan_id, deleted, access); 
create index i_movement_filter on ca_movements(movement_id, deleted, access); 
create index i_item_filter on ca_list_items(item_id, deleted, access); 
create index i_loc_filter on ca_storage_locations(location_id, deleted, access); 
create index i_lot_filter on ca_object_lots(lot_id, deleted, access); 
create index i_rep_filter on ca_object_representations(representation_id, deleted, access); 
create index i_set_filter on ca_sets(set_id, deleted, access); 

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (140, unix_timestamp());
