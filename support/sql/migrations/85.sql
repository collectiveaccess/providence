/*
	Date: 13 June 2013
	Migration: 85
	Description: 
*/

create table ca_object_lots_x_object_lots
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_left_id                    int unsigned               not null,
   lot_right_id                   int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_object_lots_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_lot_left_id foreign key (lot_left_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_lot_right_id foreign key (lot_right_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_label_left_id foreign key (label_left_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_lots_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_left_id on ca_object_lots_x_object_lots(lot_left_id);
create index i_lot_right_id on ca_object_lots_x_object_lots(lot_right_id);
create index i_type_id on ca_object_lots_x_object_lots(type_id);
create unique index u_all on ca_object_lots_x_object_lots
(
   lot_left_id,
   lot_right_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_object_lots(label_left_id);
create index i_label_right_id on ca_object_lots_x_object_lots(label_right_id);



/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (85, unix_timestamp());