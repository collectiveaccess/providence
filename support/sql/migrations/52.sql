/* 
	Date: 11 January 2011
	Migration: 52
	Description:
*/


ALTER TABLE ca_commerce_orders MODIFY COLUMN order_status varchar(40) not null;
ALTER TABLE ca_commerce_orders MODIFY COLUMN shipping_method varchar(40) not null;
ALTER TABLE ca_commerce_orders MODIFY COLUMN payment_method varchar(40) not null;
ALTER TABLE ca_commerce_orders MODIFY COLUMN payment_status varchar(40) not null;

ALTER TABLE ca_commerce_order_items MODIFY COLUMN service varchar(40) null;

ALTER TABLE ca_commerce_order_items ADD COLUMN shipping_cost decimal(8,2) null;
ALTER TABLE ca_commerce_order_items ADD COLUMN handling_cost decimal(8,2) null;
ALTER TABLE ca_commerce_order_items ADD COLUMN shipping_notes text not null;

ALTER TABLE ca_commerce_orders ADD COLUMN refund_date int unsigned null;
ALTER TABLE ca_commerce_orders ADD COLUMN refund_notes text not null;
ALTER TABLE ca_commerce_orders ADD COLUMN refund_amount decimal(8,2) null;

ALTER TABLE ca_commerce_order_items ADD COLUMN refund_date int unsigned null;
ALTER TABLE ca_commerce_order_items ADD COLUMN refund_notes text not null;
ALTER TABLE ca_commerce_order_items ADD COLUMN refund_amount decimal(8,2) null;

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (52, unix_timestamp());