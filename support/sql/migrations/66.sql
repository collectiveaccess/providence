/* 
	Date: 28 June 2012
	Migration: 66
	Description:
*/

ALTER TABLE ca_commerce_order_items ADD COLUMN loan_checkout_date int unsigned null DEFAULT '0';
ALTER TABLE ca_commerce_order_items ADD COLUMN loan_due_date int unsigned null DEFAULT '0';
ALTER TABLE ca_commerce_order_items ADD COLUMN loan_return_date int unsigned null DEFAULT '0';

create index i_loan_checkout_date on ca_commerce_order_items(loan_checkout_date);
create index i_loan_due_date on ca_commerce_order_items(loan_due_date);
create index i_loan_return_date on ca_commerce_order_items(loan_return_date);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (66, unix_timestamp());