/* 
	Date: 13 January 2013
	Migration: 78
	Description:
*/


/* -------------------------------------------------------------------------------- */

create index i_hier_left on ca_places(hier_left);
create index i_hier_right on ca_places(hier_right);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (78, unix_timestamp());