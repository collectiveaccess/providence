/* 
	Date: 18 December 2011
	Migration: 50
	Description:
*/


/*==========================================================================*/
create table ca_commerce_communications_read_log
(
  log_id int(10) unsigned not null auto_increment,
  communication_id int unsigned not null,
  read_on int unsigned null,
  read_by_user_id int unsigned null,
  
  primary key (log_id),
   constraint fk_ca_commerce_communications_read_log_communication_id foreign key (communication_id)
      references ca_commerce_communications (communication_id) on delete restrict on update restrict,
      
   constraint ca_commerce_communications_read_log_read_by_user_id foreign key (read_by_user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_communication_id on ca_commerce_communications_read_log(communication_id);
create index i_read_on on ca_commerce_communications_read_log(read_on);
create index i_read_by_user_id on ca_commerce_communications_read_log(read_by_user_id);


/*==========================================================================*/
ALTER TABLE ca_commerce_communications ADD COLUMN read_on int null;
create index i_read_on on ca_commerce_communications(read_on);


ALTER TABLE ca_commerce_communications ADD COLUMN from_user_id int null references ca_users(user_id);
create index i_from_user_id on ca_commerce_communications(from_user_id);

ALTER TABLE ca_commerce_communications ADD COLUMN set_snapshot longtext not null;

ALTER TABLE ca_commerce_transactions ADD COLUMN set_id int unsigned null references ca_sets(set_id);
create index i_set_id on ca_commerce_transactions(set_id);

ALTER TABLE ca_commerce_communications DROP FOREIGN KEY fk_ca_commerce_communications_set_id;
ALTER TABLE ca_commerce_communications DROP COLUMN set_id;


/*==========================================================================*/
create index i_rank on ca_object_representations(rank);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (50, unix_timestamp());