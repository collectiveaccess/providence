/* 
	Date: 14 January 2011
	Migration: 53
	Description:
*/

ALTER TABLE ca_commerce_fulfillment_events CHANGE COLUMN fullfillment_method fulfillment_method varchar(40) not null; 
ALTER TABLE ca_commerce_fulfillment_events CHANGE COLUMN fullfillment_details fulfillment_details blob not null; 

/*	If you updated from SVN in December 2011 you may have run a migration (since removed) on your database that */
/*	incorrectly renames the fulfillment_method and fulfillment_details fields in the ca_commerce_fulfillment_events table. */
/*	If the two lines above throw an error replace them with the two lines below, which will resolve the issue. */

/* ALTER TABLE ca_commerce_fulfillment_events MODIFY COLUMN fulfillment_method varchar(40) not null; */
/* ALTER TABLE ca_commerce_fulfillment_events MODIFY COLUMN fulfillment_details blob not null; */


/*==========================================================================*/
create table if not exists ca_storage_locations_x_vocabulary_terms
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   location_id                        int unsigned               not null,
   item_id                       int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_storage_locations_x_vocabulary_terms_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_item_id foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_label_left_id foreign key (label_left_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_storage_locations_x_vocabulary_terms_label_right_id foreign key (label_right_id)
      references ca_list_item_labels (label_id) on delete restrict on update restrict,
      
      key i_location_id(location_id),
      key i_item_id (item_id),
      key i_type_id (type_id),
      unique key u_all (location_id, item_id, type_id, sdatetime, edatetime),
      key i_label_left_id (label_left_id),
      key i_label_right_id (label_right_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (53, unix_timestamp());