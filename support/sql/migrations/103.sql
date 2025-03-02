/*
	Date: 23 May 2014
	Migration: 103
	Description: did you mean revision
*/

/* Add deaccession fields for objects */
DROP TABLE ca_did_you_mean_phrases;
DROP TABLE ca_did_you_mean_ngrams;

TRUNCATE TABLE ca_sql_search_ngrams;
ALTER TABLE ca_sql_search_ngrams DROP PRIMARY KEY;
CREATE INDEX i_word_id ON ca_sql_search_ngrams(word_id);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (103, unix_timestamp());