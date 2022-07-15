/*
	Date: 15 July 2022
	Migration: 178
	Description:  Add browse index tables
*/

/*==========================================================================*/

create table ca_browse_facets (
  facet_id		        int unsigned        not null AUTO_INCREMENT,
  name					varchar(255)		not null,
  code			        varchar(100)		not null,
  table_num				tinyint unsigned	not null,

  primary key (facet_id),
  unique index u_code (code, table_num)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create table ca_browse_values (
  value_id		        int unsigned        not null AUTO_INCREMENT,
  facet_id              int unsigned        not null references ca_browse_facets(facet_id),
  value					varchar(255)		not null,
  value_sort			varchar(255)		not null,
  item_id				int unsigned	    null,
  parent_id				int unsigned	    null,
  aggregations          longtext            not null,

  primary key (value_id),
  index i_facet_id (facet_id),
  index i_value (value),
  index i_value_sort (value_sort),
  index i_item_id (item_id),
  index i_parent_id (parent_id),
  index i_all (facet_id, value, item_id, parent_id)

) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create table ca_browse_references (
  row_id		        int unsigned        not null,
  value_id				int unsigned		not null references ca_browse_values(value_id),
  access				tinyint unsigned	not null default 0,

  primary key (row_id, value_id)
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (178, unix_timestamp());
