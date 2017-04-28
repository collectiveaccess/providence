/*
	Date: 18 November 2016
	Migration: 142
	Description: Add Pawtucket content management tables
*/

/*==========================================================================*/

create table ca_site_page_media (
  media_id		      	int unsigned        not null  AUTO_INCREMENT,
  page_id               int unsigned        not null references ca_site_pages(page_id),
  title					varchar(255)		not null,
  idno                  varchar(255)        not null,
  idno_sort             varchar(255)        not null,
  caption			    text				not null,
  media        			longblob            not null,
  media_metadata        longblob            not null,
  media_content			longtext			not null,
  md5                   varchar(32)         not null,
  mimetype              varchar(255)        null,
  original_filename     varchar(1024)       not null, 
  rank					int unsigned		not null default 0,
  access                tinyint unsigned    not null default 0,
  deleted               tinyint unsigned    not null default 0,

  primary key (media_id),
  key (page_id),
  key (rank),
  key (md5),
  key (idno),
  key (idno_sort),
  unique index u_idno (page_id, idno)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (142, unix_timestamp());