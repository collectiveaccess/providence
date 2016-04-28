/*
	Date: 13 April 2016
	Migration: 130
	Description: Add primary key to ca_batch_log_items
*/

/*==========================================================================*/

ALTER TABLE ca_batch_log_items DROP FOREIGN KEY fk_ca_batch_log_items_batch_id;
ALTER TABLE ca_batch_log_items DROP PRIMARY KEY;
ALTER TABLE ca_batch_log_items ADD  `item_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE ca_batch_log_items ADD FOREIGN KEY fk_ca_batch_log_items_batch_id (batch_id) REFERENCES ca_batch_log (batch_id) on delete restrict on update restrict;
ALTER TABLE ca_batch_log_items ADD INDEX i_batch_row_id (batch_id, row_id);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (130, unix_timestamp());
