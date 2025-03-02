/* 
	Date: 30 August 2012
	Migration: 68
	Description:
*/

alter table ca_commerce_orders add column order_number varchar(255) not null;

update ca_commerce_orders set order_number = concat(date_format(from_unixtime(created_on), '%m%d%Y'), '-', order_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (68, unix_timestamp());