/* 
	Date: 20 October 2010
	Migration: 28
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Add missing ca_loans_x_movements table
*/

create table ca_loans_x_movements (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   loan_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   movement_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   
   constraint fk_ca_loans_x_movements_loan_id foreign key (loan_id)
      references ca_loans (loan_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_movement_id foreign key (movement_id)
      references ca_movements (movement_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movements_label_left_id foreign key (label_left_id)
      references ca_loan_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_loans_x_movement_label_right_id foreign key (label_right_id)
      references ca_movement_labels (label_id) on delete restrict on update restrict
      
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_loan_id on ca_loans_x_movements (loan_id);
create index i_movement_id on ca_loans_x_movements (movement_id);
create index i_type_id on ca_loans_x_movements (type_id);
create unique index u_all on ca_loans_x_movements (
   loan_id,
   movement_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on ca_loans_x_movements (label_left_id);
create index i_label_right_id on ca_loans_x_movements (label_right_id);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (28, unix_timestamp());
