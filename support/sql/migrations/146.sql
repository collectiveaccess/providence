/*
	Date: 2 April 2017
	Migration: 146
	Description: Increase maximum length for entity surnames
*/

DROP INDEX u_all on ca_entity_labels;
DROP INDEX i_surname on ca_entity_labels;
ALTER TABLE ca_entity_labels MODIFY COLUMN surname varchar(512) not null default '';

CREATE INDEX i_surname on ca_entity_labels(surname(100));
create unique index u_all on ca_entity_labels
(
   entity_id,
   forename(50),
   other_forenames(50),
   middlename(50),
   surname(100),
   type_id,
   locale_id
);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (146, unix_timestamp());
