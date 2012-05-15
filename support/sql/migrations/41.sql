/* 
	Date: 24 June 2011
	Migration: 41
	Description:
*/

/*==========================================================================*/
/* Bookmarks (for Pawtucket) */

create table ca_bookmark_folders 
(
  folder_id int(10) unsigned not null auto_increment,
  name varchar(255) not null,
  user_id int unsigned not null references ca_users(user_id),
  rank smallint unsigned not null,
  
  primary key (folder_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_bookmark_folders(user_id);

create table ca_bookmarks 
(
  bookmark_id int(10) unsigned not null auto_increment,
  folder_id int unsigned not null references ca_bookmark_folders(folder_id),
  table_num tinyint unsigned not null,
  row_id int unsigned not null,
  notes text not null,
  rank smallint unsigned not null,
  created_on int unsigned not null,
  
  primary key (bookmark_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_bookmarks(row_id);
create index i_folder_id on ca_bookmarks(folder_id);

/*==========================================================================*/
/* E-commerce (for Pawtucket) */

create table ca_commerce_transactions 
(
  transaction_id int(10) unsigned not null auto_increment,
  user_id int unsigned not null,
  short_description text not null,
  notes text not null,
  created_on int unsigned not null,
  
  primary key (transaction_id),
  
   constraint fk_ca_commerce_transactions_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_user_id on ca_commerce_transactions(user_id);


create table ca_commerce_communications
(
  communication_id int(10) unsigned not null auto_increment,
  transaction_id int unsigned not null,
  source char(1) not null, 					/* U=from user; I=from institution */
  created_on int unsigned not null,
  subject varchar(255) not null,
  message text not null,
  set_id int unsigned null,
  
  primary key (communication_id),
   constraint fk_ca_commerce_communications_set_id foreign key (set_id)
      references ca_sets (set_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_communications_transaction_id foreign key (transaction_id)
      references ca_commerce_transactions (transaction_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_transaction_id on ca_commerce_communications(transaction_id);
create index i_set_id on ca_commerce_communications(set_id);


create table ca_commerce_orders
(
  order_id int(10) unsigned not null auto_increment,
  transaction_id int unsigned not null,
  created_on int unsigned not null,
  
  order_status int unsigned not null,		/* fixed list coded in model */
   
  shipping_fname varchar(255) not null,
  shipping_lname varchar(255) not null,
  shipping_organization varchar(255) not null,
  shipping_address1 varchar(255) not null,
  shipping_address2 varchar(255) not null,
  shipping_city varchar(255) not null,
  shipping_zone varchar(255) not null,
  shipping_postal_code varchar(255) not null,
  shipping_country varchar(255) not null,
  shipping_phone varchar(255) not null,
  shipping_fax varchar(255) not null,
  shipping_email varchar(255) not null,
  
  billing_fname varchar(255) not null,
  billing_lname varchar(255) not null,
  billing_organization varchar(255) not null,
  billing_address1 varchar(255) not null,
  billing_address2 varchar(255) not null,
  billing_city varchar(255) not null,
  billing_zone varchar(255) not null,
  billing_postal_code varchar(255) not null,
  billing_country varchar(255) not null,
  billing_phone varchar(255) not null,
  billing_fax varchar(255) not null,
  billing_email varchar(255) not null,
  
  payment_method varchar(100) null,
  payment_status tinyint unsigned not null,
  payment_details longblob not null,
  payment_response longblob not null,
  payment_received_on int unsigned null,
  
  shipping_method tinyint unsigned null,		/* fixed list from config file */
  shipping_cost decimal(8,2) null,
  handling_cost decimal(8,2) null,
  shipping_notes text not null,
  shipping_date int unsigned null,
  shipped_on_date int unsigned null,
  
  primary key (order_id),
   constraint fk_ca_commerce_orders_transaction_id foreign key (transaction_id)
      references ca_commerce_transactions (transaction_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_transaction_id on ca_commerce_orders(transaction_id);


create table ca_commerce_order_items
(
   item_id                        int unsigned                   not null AUTO_INCREMENT,
   order_id                       int unsigned                   not null,
   object_id                      int unsigned                   null,
   service                    	  tinyint unsigned                   null,				/* fixed list from config file */
   fulfillment_method      tinyint unsigned                   null,			/* fixed list from config file */
   fee                               decimal(8,2) null,
   tax                               decimal(8,2) null,
   notes                            text                                 not null,
   restrictions                   text                                 not null,
   rank                           int unsigned                   not null,
   primary key (item_id),
   constraint fk_ca_commerce_order_items_object_id foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_order_items_order_id foreign key (order_id)
      references ca_commerce_orders (order_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_commerce_order_items(object_id);
create index i_order_id on ca_commerce_order_items(order_id);


create table ca_commerce_fulfillment_events
(
   event_id                       int unsigned                   not null AUTO_INCREMENT,
   order_id                       int unsigned                   not null,
   item_id                        int unsigned                   null,
   fulfillment_method     tinyint unsigned              null,				/* fixed list from config file */
   fulfillment_details       blob not null,										/* serialized array; format depends upon fulfillment method */
   occurred_on                int unsigned not null,
   notes                          text not null,
   
   primary key (event_id),
   constraint fk_ca_commerce_fulfillment_events_order_id foreign key (order_id)
      references ca_commerce_orders (order_id) on delete restrict on update restrict,
      
   constraint fk_ca_commerce_fulfillment_events_item_id foreign key (item_id)
      references ca_commerce_order_items (item_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_order_id on ca_commerce_fulfillment_events(order_id);
create index i_item_id on ca_commerce_fulfillment_events(item_id);


/*==========================================================================*/
/* User contributed content enhancements (for Pawtucket) */

/* Support for up to four images associated with a comment */
ALTER TABLE ca_item_comments ADD COLUMN media1 longblob not null;
ALTER TABLE ca_item_comments ADD COLUMN media2 longblob not null;
ALTER TABLE ca_item_comments ADD COLUMN media3 longblob not null;
ALTER TABLE ca_item_comments ADD COLUMN media4 longblob not null;


/*==========================================================================*/
/* Missing ca_object_representations relationships */
/*==========================================================================*/
create table ca_object_representations_x_collections
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   collection_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_collections_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_collection_id foreign key (collection_id)
      references ca_collections (collection_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_collections_label_right_id foreign key (label_right_id)
      references ca_collection_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_collections(representation_id);
create index i_collection_id on ca_object_representations_x_collections(collection_id);
create index i_type_id on ca_object_representations_x_collections(type_id);
create unique index u_all on ca_object_representations_x_collections
(
   type_id,
   representation_id,
   collection_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_collections(label_left_id);
create index i_label_right_id on ca_object_representations_x_collections(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   location_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_storage_loc_rep_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_loc_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_storage_loc_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_representations_x_storage_locations(representation_id);
create index i_location_id on ca_object_representations_x_storage_locations(location_id);
create index i_type_id on ca_object_representations_x_storage_locations(type_id);
create unique index u_all on ca_object_representations_x_storage_locations
(
   type_id,
   representation_id,
   location_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_storage_locations(label_left_id);
create index i_label_right_id on ca_object_representations_x_storage_locations(label_right_id);


/*==========================================================================*/
create table ca_object_representations_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_left_id                 int unsigned               not null,
   representation_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_object_representations_x_object_reps_rep_left_id foreign key (representation_left_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_rep_right_id foreign key (representation_right_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_label_left_id foreign key (label_left_id)
      references ca_object_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_representations_x_object_reps_label_right_id foreign key (label_right_id)
      references ca_object_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_left_id on ca_object_representations_x_object_representations(representation_left_id);
create index i_representation_right_id on ca_object_representations_x_object_representations(representation_right_id);
create index i_type_id on ca_object_representations_x_object_representations(type_id);
create unique index u_all on ca_object_representations_x_object_representations
(
   representation_left_id,
   representation_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_representations_x_object_representations(label_left_id);
create index i_label_right_id on ca_object_representations_x_object_representations(label_right_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (41, unix_timestamp());