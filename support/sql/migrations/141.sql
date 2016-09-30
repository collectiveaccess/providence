/*
	Date: 22 September 2016
	Migration: 141
	Description: Add Pawtucket content management tables
*/

/*==========================================================================*/

create table ca_site_templates (
  template_id		    int unsigned        not null AUTO_INCREMENT,
  title					varchar(255)		not null,
  description			text				not null,
  template				longtext			not null,
  tags                  longtext            not null,
  deleted               tinyint unsigned    not null default 0,

  primary key (template_id),
  unique index u_title (title)

) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/

create table ca_site_pages (
  page_id		      	int unsigned        not null  AUTO_INCREMENT,
  template_id           int unsigned        not null references ca_site_templates(template_id),
  title					varchar(255)		not null,
  description			text				not null,
  path        			varchar(255)        not null,
  content				longtext			not null,
  keywords				text				not null,
  access                tinyint unsigned    not null default 0,
  deleted               tinyint unsigned    not null default 0,
  view_count            int unsigned        not null default 0,

  primary key (page_id),
  key (template_id),
  unique index u_path (path)

) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (141, unix_timestamp());