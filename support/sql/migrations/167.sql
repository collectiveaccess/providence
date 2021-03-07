/*
	Date: 4 February 2021
	Migration: 167
	Description:    Add is_preferred field for metadata element labels; add metadata slot to ca_media_upload_sessions
*/

/*==========================================================================*/

/* Allow non-preferred labels for metadata elements - used to disambiguate fields */
ALTER TABLE ca_metadata_element_labels ADD COLUMN is_preferred tinyint unsigned not null default 0;
UPDATE ca_metadata_element_labels SET is_preferred = 1;

/* Add fields for adding of metadata to insert for uploaded files; used by front-end media importer */
ALTER TABLE ca_media_upload_sessions ADD COLUMN `metadata` longtext null;
ALTER TABLE ca_media_upload_sessions ADD COLUMN `source` varchar(30) not null default 'UPLOADER';
ALTER TABLE ca_media_upload_sessions ADD COLUMN `status` varchar(30) not null default 'IN_PROGRESS';
ALTER TABLE ca_media_upload_sessions ADD COLUMN `submitted_on` int unsigned null;
ALTER TABLE ca_media_upload_sessions DROP COLUMN `cancelled`;

CREATE INDEX i_status ON ca_media_upload_sessions(status);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (167, unix_timestamp());
