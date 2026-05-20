/*
	Date: 11 May 2026
	Migration: 212
	Description: 
*/

/*==========================================================================*/

ALTER TABLE ca_sql_search_word_index ADD COLUMN timecode_start decimal(10,3) not null default 0;
ALTER TABLE ca_sql_search_word_index ADD COLUMN timecode_end decimal(10,3) not null default 0;
CREATE INDEX i_timecode_start on ca_sql_search_word_index(timecode_start, timecode_end);
CREATE INDEX i_timecode_end on ca_sql_search_word_index(timecode_end);

ALTER TABLE ca_sql_search_word_index MODIFY COLUMN word_index mediumintint unsigned not null default 0;
ALTER TABLE ca_sql_search_word_index MODIFY COLUMN word_count mediumint unsigned not null default 0;
ALTER TABLE ca_sql_search_word_index MODIFY COLUMN field_index mediumintint unsigned not null default 0;
      
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (212, unix_timestamp());
