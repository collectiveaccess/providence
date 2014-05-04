/*
	Date: 27 April 2014
	Migration: 101
	Description: add metadata dictionary tables
*/

/*==========================================================================*/
create table ca_metadata_dictionary_entries (
   entry_id                 int unsigned					not null AUTO_INCREMENT,
   bundle_name              varchar(255) not null,
   settings                 longtext not null,
   primary key (entry_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


/*==========================================================================*/
create table ca_metadata_dictionary_rules (
   rule_id                  int unsigned					not null AUTO_INCREMENT,
   entry_id                 int unsigned not null,
   rule_name                varchar(255) not null,
   settings                 longtext not null,
   primary key (rule_id),
   index i_entry_id (entry_id),
   index i_rule_name (rule_name),
   
   constraint fk_ca_metadata_dictionary_rules_entry_id foreign key (entry_id)
      references ca_metadata_dictionary_entries (entry_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/
/* Add missing sql search indices */
create index i_field_table_num on ca_sql_search_word_index(field_table_num);
create index i_field_num on ca_sql_search_word_index(field_num);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (101, unix_timestamp());