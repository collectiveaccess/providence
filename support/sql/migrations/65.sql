/* 
	Date: 26 June 2012
	Migration: 65
	Description:
*/

ALTER TABLE ca_commerce_communications ADD COLUMN communication_type char(1) not null DEFAULT 'O';
create index i_communication_type on ca_commerce_communications(communication_type);

ALTER TABLE ca_commerce_orders ADD COLUMN order_type char(1) not null DEFAULT 'O';
create index i_order_type on ca_commerce_orders(order_type);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (65, unix_timestamp());