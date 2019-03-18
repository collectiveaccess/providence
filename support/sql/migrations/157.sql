/*
	Date: 14 January 2019
	Migration: 157
	Description:    Set null change log units
	                Fix schema issues in older systems
	                Add labels for ca_metadata_dictionary_entries
	                Extend SQL Search field_num
*/

/*==========================================================================*/

UPDATE ca_change_log SET unit_id = MD5(log_id) WHERE unit_id IS NULL;

CREATE INDEX i_log_plus on ca_change_log_subjects (log_id, subject_table_num, subject_row_id);
CREATE INDEX i_date_unit on ca_change_log(log_datetime, unit_id); 


/* Reset field settings that may be incorrect in older installations */
ALTER TABLE ca_search_indexing_queue MODIFY COLUMN changed_fields longtext null;
ALTER TABLE ca_set_items MODIFY COLUMN rank int unsigned not null default 0;

/*==========================================================================*/
create table ca_metadata_dictionary_entry_labels (
	label_id		  int unsigned not null primary key auto_increment,
	entry_id			  int unsigned null references ca_metadata_dictionary_entries(entry_id),
	locale_id		  smallint unsigned not null references ca_locales(locale_id),
	name			    varchar(255) not null,
	name_sort		  varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,

	KEY i_entry_id (entry_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

ALTER TABLE ca_metadata_dictionary_entries ADD COLUMN table_num tinyint unsigned not null default 0;
UPDATE ca_metadata_dictionary_entries SET table_num = 57;
/* all existing entries should be bound to ca_objects */
CREATE INDEX i_table_num ON ca_metadata_dictionary_entries(table_num);
CREATE INDEX i_name ON ca_metadata_dictionary_entries(bundle_name);
CREATE INDEX i_prefetch ON ca_attributes(row_id, element_id, table_num);

/*==========================================================================*/

ALTER TABLE ca_sql_search_word_index MODIFY COLUMN field_num varchar(100) not null;

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (157, unix_timestamp());
