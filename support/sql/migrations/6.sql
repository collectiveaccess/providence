/* 
	Date: 8 Octover 2009
	Migration: 6
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	support for boosting in MySQL FULLTEXT search engine
*/
ALTER TABLE ca_mysql_fulltext_search ADD COLUMN boost int not null default 1;
CREATE INDEX i_boost on ca_mysql_fulltext_search(boost);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (6, unix_timestamp());
