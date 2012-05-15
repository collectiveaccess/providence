/* 
	Date: 6 October 2011
	Migration: 47
	Description:
*/

/*==========================================================================*/
create table ca_change_log_snapshots (
	log_id                         bigint                         not null,
    snapshot                       longblob                       not null,
    
   constraint fk_ca_change_log_snaphots_log_id foreign key (log_id)
      references ca_change_log (log_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;
create index i_log_id on ca_change_log_snapshots (log_id);

INSERT INTO ca_change_log_snapshots SELECT log_id, snapshot FROM ca_change_log;
ALTER TABLE ca_change_log DROP COLUMN snapshot;

create index i_table_num on ca_change_log (logged_table_num);


/*==========================================================================*/
create table ca_entities_x_storage_locations
(
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   entity_id                      int unsigned               not null,
   location_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   label_left_id                  int unsigned                   null,
   label_right_id                 int unsigned                   null,
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_ca_entities_x_storage_locations_type_id foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_entity_id foreign key (entity_id)
      references ca_entities (entity_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_location_id foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_label_left_id foreign key (label_left_id)
      references ca_entity_labels (label_id) on delete restrict on update restrict,
      
   constraint fk_ca_entities_x_storage_locations_label_right_id foreign key (label_right_id)
      references ca_storage_location_labels (label_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_entity_id on  ca_entities_x_storage_locations(entity_id);
create index i_location_id on  ca_entities_x_storage_locations(location_id);
create index i_type_id on  ca_entities_x_storage_locations(type_id);
create unique index u_all on  ca_entities_x_storage_locations
(
   entity_id,
   location_id,
   type_id,
   sdatetime,
   edatetime
);
create index i_label_left_id on  ca_entities_x_storage_locations(label_left_id);
create index i_label_right_id on  ca_entities_x_storage_locations(label_right_id);



/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (47, unix_timestamp());