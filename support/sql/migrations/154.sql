/*
	Date: 6 March 2018; 4 April 2018
	Migration: 154
	Description: Add notification fields to track reading and uniqueness
*/

/*==========================================================================*/
create table ca_metadata_alert_rules (
  rule_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  code            varchar(20)       not null,
  settings        longtext          not null,
  user_id			    int unsigned      null references ca_users(user_id),

  primary key (rule_id),
  index i_table_num (table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_rule_labels (
	label_id		  int unsigned not null primary key auto_increment,
	rule_id			  int unsigned null references ca_metadata_alert_rules(rule_id),
	locale_id		  smallint unsigned not null references ca_locales(locale_id),
	name			    varchar(255) not null,
	name_sort		  varchar(255) not null,
	description		text not null,
	source_info		longtext not null,
	is_preferred	tinyint unsigned not null,

	KEY i_rule_id (rule_id),
	KEY i_locale_id (locale_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_triggers (
  trigger_id      int unsigned      not null AUTO_INCREMENT,
  rule_id         int unsigned      not null,
  element_id      smallint unsigned,
  settings        longtext          not null,
  trigger_type    varchar(30)       not null,
  element_filters text              not null,

  primary key (trigger_id),
  constraint fk_alert_rules_rule_id foreign key (rule_id)
    references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict,

  constraint fk_ca_metadata_alert_triggers_element_id foreign key (element_id)
    references ca_metadata_elements (element_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_rules_x_user_groups (
	relation_id   int unsigned not null auto_increment,
	rule_id 		  int unsigned not null references ca_metadata_alert_rules(rule_id),
	group_id 		  int unsigned not null references ca_user_groups(group_id),
	access 			  tinyint unsigned not null default 0,

	primary key 				(relation_id),
	index i_rule_id			(rule_id),
	index i_group_id		(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_rules_x_users (
	relation_id 	int unsigned not null auto_increment,
	rule_id 	int unsigned not null references ca_metadata_alert_rules(rule_id),
	user_id 		int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null default 0,

	primary key 				(relation_id),
	index i_rule_id			(rule_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_rule_type_restrictions (
   restriction_id                 int unsigned                   not null AUTO_INCREMENT,
   type_id                        int unsigned,
   table_num                      tinyint unsigned               not null,
   rule_id                        int unsigned                   not null,
   include_subtypes               tinyint unsigned               not null default 0,
   settings                       longtext                       not null,
   rank                           smallint unsigned              not null default 0,
   primary key (restriction_id),

   index i_rule_id			(rule_id),
   index i_type_id				(type_id),
   constraint fk_ca_metadata_alert_rule_type_restrictions_rule_id foreign key (rule_id)
      references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/*==========================================================================*/


ALTER TABLE ca_notifications ADD COLUMN notification_key char(32) not null default '';
CREATE INDEX i_notification_key ON ca_notifications(notification_key);

ALTER TABLE ca_notifications ADD COLUMN extra_data longtext not null;

ALTER TABLE ca_notification_subjects ADD COLUMN read_on int unsigned null;
DROP INDEX i_table_num_row_id ON ca_notification_subjects;
CREATE INDEX i_table_num_row_id ON ca_notification_subjects(table_num, row_id, read_on);

ALTER TABLE ca_notification_subjects ADD COLUMN delivery_email tinyint unsigned not null default 0;
ALTER TABLE ca_notification_subjects ADD COLUMN delivery_email_sent_on int unsigned null;
ALTER TABLE ca_notification_subjects ADD COLUMN delivery_inbox tinyint unsigned not null default 1;

CREATE INDEX i_delivery_email ON ca_notification_subjects(delivery_email, delivery_email_sent_on);
CREATE INDEX i_delivery_inbox ON ca_notification_subjects(delivery_inbox);

/*==========================================================================*/


/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (154, unix_timestamp());
