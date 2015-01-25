/*
	Date: 25 January 2015
	Migration: 116
	Description: Add last sent fields to checkouts
*/

ALTER TABLE ca_object_checkouts ADD COLUMN last_sent_coming_due_email int unsigned null;
ALTER TABLE ca_object_checkouts ADD COLUMN last_sent_overdue_email int unsigned null;
ALTER TABLE ca_object_checkouts ADD COLUMN last_reservation_available_email int unsigned null;

CREATE INDEX i_last_sent_coming_due_email ON ca_object_checkouts (last_sent_coming_due_email);
CREATE INDEX i_last_sent_overdue_email ON ca_object_checkouts (last_sent_overdue_email);
CREATE INDEX i_last_reservation_available_email ON ca_object_checkouts (last_reservation_available_email);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (116, unix_timestamp());
