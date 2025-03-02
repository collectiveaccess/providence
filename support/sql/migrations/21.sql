/* 
	Date: 30 August 2010
	Migration: 21
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	
*/
RENAME TABLE ca_representations_x_entities TO ca_object_representations_x_entities;
RENAME TABLE ca_representations_x_occurrences TO ca_object_representations_x_occurrences;
RENAME TABLE ca_representations_x_places TO ca_object_representations_x_places;
RENAME TABLE ca_representations_x_vocabulary_terms TO ca_object_representations_x_vocabulary_terms;

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (21, unix_timestamp());
