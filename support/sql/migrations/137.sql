/*
	Date: 5 Aug 2016
	Migration: 137
	Description: Add logging of downloads
*/

/*==========================================================================*/
create table ca_download_log (
  log_id		      	int unsigned        not null AUTO_INCREMENT,
  log_datetime        	int unsigned        not null,
  user_id             	int unsigned        null,
  ip_addr			  	char(15)			null,
  table_num    			tinyint unsigned    not null,
  row_id       			int unsigned        not null,
  representation_id     int unsigned      	null,
  download_source		varchar(40)			null,

  primary key (log_id),

  constraint fk_ca_download_log_user_id foreign key (user_id)
    references ca_users (user_id) on delete restrict on update restrict,

  constraint fk_ca_download_log_representation_id foreign key (representation_id)
    references ca_object_representations (representation_id) on delete restrict on update restrict,

  index i_table_num_row_id (table_num, row_id),
  index i_log_datetime (log_datetime)

) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (137, unix_timestamp());