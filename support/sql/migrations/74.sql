/* 
	Date: 28 December 2012
	Migration: 74
	Description:
*/

/* --------------------------- Batch edit log --------------------------- */

alter table ca_batch_log_items add column errors longtext null;
alter table ca_batch_log_items drop foreign key fk_ca_change_log_log_id;
alter table ca_batch_log_items drop column log_id;
alter table ca_change_log add column batch_id int unsigned null references ca_batch_log(batch_id);
create index i_batch_id on ca_change_log (batch_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (74, unix_timestamp());