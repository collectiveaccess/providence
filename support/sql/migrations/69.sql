/* 
	Date: 18 October 2012
	Migration: 69
	Description:
*/

ALTER TABLE ca_collections ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;
ALTER TABLE ca_objects ADD COLUMN acl_inherit_from_ca_collections tinyint unsigned not null default 0;
ALTER TABLE ca_objects ADD COLUMN acl_inherit_from_parent tinyint unsigned not null default 0;

ALTER TABLE ca_acl ADD COLUMN inherited_from_table_num tinyint unsigned null;
ALTER TABLE ca_acl ADD COLUMN inherited_from_row_id int unsigned null;


CREATE INDEX i_acl_inherit_from_parent on ca_objects(acl_inherit_from_parent);
CREATE INDEX i_acl_inherit_from_ca_collections on ca_objects(acl_inherit_from_ca_collections);
CREATE INDEX i_acl_inherit_from_parent on ca_collections(acl_inherit_from_parent);

CREATE INDEX i_inherited_from_table_num ON ca_acl(inherited_from_table_num);
CREATE INDEX i_inherited_from_row_id ON ca_acl(inherited_from_row_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (69, unix_timestamp());