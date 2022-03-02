/*
	Date: 3 August 2020
	Migration: 170
	Description:    Add sortable field for attributes
*/
/*==========================================================================*/

alter table ca_attribute_values ADD COLUMN value_sortable varchar(100) null;
CREATE INDEX i_value_sortable ON ca_attribute_values(value_sortable);
CREATE INDEX i_sorting ON ca_attribute_values(element_id, attribute_id, value_sortable);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (170, unix_timestamp());
