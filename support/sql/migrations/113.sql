/*
	Date: 16 October 2014
	Migration: 113
	Description: add metadata dictionary rule checking tables
*/

ALTER TABLE ca_metadata_dictionary_rules DROP COLUMN rule_name;
ALTER TABLE ca_metadata_dictionary_rules ADD COLUMN rule_code varchar(30) not null;
ALTER TABLE ca_metadata_dictionary_rules ADD COLUMN expression text not null;
ALTER TABLE ca_metadata_dictionary_rules ADD COLUMN rule_level char(4) not null;

create table ca_metadata_dictionary_rule_violations (
   violation_id             int unsigned					not null AUTO_INCREMENT,
   rule_id                  int unsigned not null,
   table_num                tinyint unsigned not null,
   row_id               	int unsigned not null,
   created_on				int unsigned not null,
   last_checked_on			int unsigned not null,
   primary key (violation_id),
   index i_rule_id (rule_id),
   index i_row_id (row_id, table_num),
   index i_created_on (created_on),
   index i_last_checked_on (last_checked_on),
   
   constraint fk_ca_metadata_dictionary_rule_vio_rule_id foreign key (rule_id)
      references ca_metadata_dictionary_rules (rule_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (113, unix_timestamp());