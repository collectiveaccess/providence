/*
	Date: 25 May 2016
	Migration: 135
	Description: Add tables for metadata alerts
*/

/*==========================================================================*/
create table ca_metadata_alert_rules (
  rule_id         int unsigned      not null AUTO_INCREMENT,
  table_num       tinyint unsigned  not null,
  name            varchar(255)      not null,
  code            varchar(20)       not null,
  settings        longtext          not null,
  user_id			    int unsigned      null references ca_users(user_id),

  primary key (rule_id),
  index i_table_num (table_num)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
/*==========================================================================*/
create table ca_metadata_alert_triggers (
  trigger_id      int unsigned      not null AUTO_INCREMENT,
  rule_id         int unsigned      not null,
  settings        longtext          not null,
  trigger_type    varchar(30)       not null,


  primary key (trigger_id),
  constraint fk_alert_rules_rule_id foreign key (rule_id)
    references ca_metadata_alert_rules (rule_id) on delete restrict on update restrict
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
create table ca_metadata_alert_notifications (
  notification_id    int UNSIGNED      not null AUTO_INCREMENT,
  user_id     int UNSIGNED      not null references ca_users(user_id),
  rule_id     int UNSIGNED      not null references ca_metadata_alert_rules(rule_id),
  datetime    int unsigned      not null,
  table_num   tinyint UNSIGNED  not null,
  row_id      int unsigned      not null,

  primary key (notification_id),

  index i_table_num_row_id (table_num, row_id),
  index i_datetime (datetime)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (135, unix_timestamp());
