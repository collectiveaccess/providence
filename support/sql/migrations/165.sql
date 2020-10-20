/*
	Date: 19 October 2020
	Migration: 166
	Description:    Add deaccession "authorized by" field
*/

/*==========================================================================*/

ALTER TABLE ca_objects ADD COLUMN deaccession_authorized_by varchar(255) not null default '';

create index i_deaccession_auth_by on ca_objects(deaccession_authorized_by);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (165, unix_timestamp());
