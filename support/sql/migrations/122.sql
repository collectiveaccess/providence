/*
	Description: Longer rule_code field for ca_metadata_dictionary_rules, and also
	  don't enforce uniqueness of rule codes globally. Just inside a single entry.
*/

/*==========================================================================*/

ALTER TABLE ca_metadata_dictionary_rules MODIFY rule_code varchar(100) null;

#DROP INDEX u_rule_code ON ca_metadata_dictionary_rules;
CREATE INDEX u_rule_code ON ca_metadata_dictionary_rules(entry_id, rule_code);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (122, unix_timestamp());
