/* 
	Date: 20 May 2012
	Migration: 63
	Description:
*/

/*==========================================================================*/
create table ca_object_lots_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   lot_id                         int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_object_lots_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_lot_id foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_object_lots_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_object_lot_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_object_lots_x_object_representations(representation_id);
create index i_lot_id on ca_object_lots_x_object_representations(lot_id);
create index i_type_id on ca_object_lots_x_object_representations(type_id);
create unique index u_all on ca_object_lots_x_object_representations
(
   type_id,
   representation_id,
   lot_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_object_lots_x_object_representations(label_left_id);
create index i_label_right_id on ca_object_lots_x_object_representations(label_right_id);

/*==========================================================================*/
create table ca_loans_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   loan_id                        int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_loans_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_loans_x_object_representations(representation_id);
create index i_loan_id on ca_loans_x_object_representations(loan_id);
create index i_type_id on ca_loans_x_object_representations(type_id);
create unique index u_all on ca_loans_x_object_representations
(
   type_id,
   representation_id,
   loan_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_object_representations(label_left_id);
create index i_label_right_id on ca_loans_x_object_representations(label_right_id);

/*==========================================================================*/
create table ca_movements_x_object_representations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned                   not null,
   movement_id                  int unsigned                   not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null default 0,
   primary key (relation_id),
   constraint fk_ca_movements_x_object_representations_representation_id foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_label_left_id foreign key (label_left_id)
      references ca_object_representation_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_movements_x_object_representations_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_movements_x_object_representations(representation_id);
create index i_movement_id on ca_movements_x_object_representations(movement_id);
create index i_type_id on ca_movements_x_object_representations(type_id);
create unique index u_all on ca_movements_x_object_representations
(
   type_id,
   representation_id,
   movement_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_movements_x_object_representations(label_left_id);
create index i_label_right_id on ca_movements_x_object_representations(label_right_id);

/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (63, unix_timestamp());