/* 
	Date: 12 September 2010
	Migration: 23
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
ALTER TABLE ca_data_import_events ADD COLUMN source text not null;
ALTER TABLE ca_data_import_events ADD COLUMN type_code varchar(10) not null;

ALTER TABLE ca_data_import_items CHANGE COLUMN typecode type_code char(1) not null;
ALTER TABLE ca_data_import_items ADD COLUMN occurred_on int unsigned not null;


CREATE INDEX i_value_longtext1 ON ca_attribute_values(value_longtext1(1024));
CREATE INDEX i_value_longtext2 ON ca_attribute_values(value_longtext2(1024));

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (23, unix_timestamp());
