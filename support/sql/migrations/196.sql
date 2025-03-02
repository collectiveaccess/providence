/*
	Date: 25 May 2024
	Migration: 196
	Description: Add identifier fields to representation annotation tables
*/

/*==========================================================================*/

ALTER TABLE ca_user_representation_annotations ADD COLUMN idno varchar(255) null;
ALTER TABLE ca_user_representation_annotations ADD COLUMN session_id varchar(255) null;

create index i_session_id on ca_user_representation_annotations(session_id);
create index u_idno on ca_user_representation_annotations(idno);

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (196, unix_timestamp());
