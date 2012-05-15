/* 
	Date: 20 January 2012
	Migration: 54
	Description:
*/


ALTER TABLE ca_set_items ADD COLUMN vars longtext not null;

/* -------------------------------------------------------------------------------- */
create table ca_commerce_order_items_x_object_representations 
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   item_id                        int unsigned                   not null,
   representation_id              int unsigned                   not null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_commerce_order_items_x_object_reps_item_id foreign key (item_id)
      references ca_commerce_order_items (item_id) on delete restrict on update restrict,
   constraint fk_ca_commerce_order_items_x_object_reps_rep_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_item_id on ca_commerce_order_items_x_object_representations(item_id);
create index i_representation_id on ca_commerce_order_items_x_object_representations(representation_id);
create unique index u_all on ca_commerce_order_items_x_object_representations
(
   item_id,
   representation_id
);

/* -------------------------------------------------------------------------------- */

ALTER TABLE ca_commerce_orders ADD COLUMN additional_fees longtext not null;
ALTER TABLE ca_commerce_order_items ADD COLUMN additional_fees longtext not null;


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (54, unix_timestamp());