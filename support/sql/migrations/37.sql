/* 
	Date: 22 March 2011
	Migration: 37
	Description:
*/

ALTER TABLE ca_tour_labels ADD COLUMN  name_sort  varchar(255) not null;
create index i_name_sort on ca_tour_labels(name_sort);

ALTER TABLE ca_tour_stop_labels ADD COLUMN  name_sort  varchar(255) not null;
create index i_name_sort on ca_tour_stop_labels(name_sort);

/*==========================================================================*/
create table ca_tour_stops_x_tour_stops
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   stop_left_id                 int unsigned               not null,
   stop_right_id                int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_notes                   text                           not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_tour_stops_x_tour_stops_stop_left_id foreign key (stop_left_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_stop_right_id foreign key (stop_right_id)
      references ca_tour_stops (stop_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_left_id foreign key (label_left_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_tour_stops_x_tour_stops_label_right_id foreign key (label_right_id)
      references ca_tour_stop_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_stop_left_id on ca_tour_stops_x_tour_stops(stop_left_id);
create index i_stop_right_id on ca_tour_stops_x_tour_stops(stop_right_id);
create index i_type_id on ca_tour_stops_x_tour_stops(type_id);
create unique index u_all on ca_tour_stops_x_tour_stops
(
   stop_left_id,
   stop_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_tour_stops_x_tour_stops(label_left_id);
create index i_label_right_id on ca_tour_stops_x_tour_stops(label_right_id);

/*==========================================================================*/
drop table if exists ca_editor_uis_x_user_groups;
create table ca_editor_uis_x_user_groups (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	group_id int unsigned not null references ca_user_groups(group_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_group_id			(group_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

drop table if exists ca_editor_uis_x_users;
create table ca_editor_uis_x_users (
	relation_id int unsigned not null auto_increment,
	ui_id int unsigned not null references ca_editor_uis(ui_id),
	user_id int unsigned not null references ca_users(user_id),
	access 			tinyint unsigned not null,
	
	primary key 				(relation_id),
	index i_ui_id				(ui_id),
	index i_user_id			(user_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;



/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (37, unix_timestamp());