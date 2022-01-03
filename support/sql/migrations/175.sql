/*
	Date: 3 January 2022
	Migration: 175
	Description:    Add fields linking imported items to front-end submissions interface
*/

/*==========================================================================*/


ALTER TABLE ca_objects ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_objects(submission_session_id);

ALTER TABLE ca_entities ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_entities(submission_session_id);

ALTER TABLE ca_places ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_places(submission_session_id);

ALTER TABLE ca_occurrences ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_occurrences(submission_session_id);

ALTER TABLE ca_collections ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_collections(submission_session_id);

ALTER TABLE ca_loans ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_loans(submission_session_id);

ALTER TABLE ca_movements ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_movements(submission_session_id);

ALTER TABLE ca_object_lots ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_object_lots(submission_session_id);

ALTER TABLE ca_object_representations ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_object_representations(submission_session_id);

ALTER TABLE ca_storage_locations ADD COLUMN submission_session_id int unsigned null references ca_media_upload_sessions(session_id);
create index i_submission_session_id on ca_storage_locations(submission_session_id);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (175, unix_timestamp());
