/*
	Date: 25 April 2016
	Migration: 132
	Description: Adds tables to stash user sorts
*/

/*==========================================================================*/

create table ca_user_sorts
(
  sort_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  user_id         int unsigned      not null,
  name            varchar(255)      not null,
  settings        longtext          not null,
  sort_type       char(1)           null,
  rank            smallint unsigned not null default 0,
  deleted         tinyint unsigned  not null default 0,

  primary key (sort_id),
  index i_table_num (table_num),
  index i_user_id (user_id),
  unique index u_guid (table_num, name),

  constraint fk_ca_user_sorts_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create table ca_user_sort_items
(
  item_id         int unsigned      not null AUTO_INCREMENT,
  sort_id         int unsigned      not null,
  bundle_name     varchar(255)      not null,
  rank            smallint unsigned not null default 0,

  primary key (item_id),

  constraint fk_ca_user_sort_items_sort_id foreign key (sort_id)
    references ca_user_sorts (sort_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (132, unix_timestamp());
