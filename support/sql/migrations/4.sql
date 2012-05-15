/* 
	Date: 24 September 2009
	Migration: 4
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Support for direct relationships between storage locations and objects or object lots
*/

create table ca_objects_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   object_id                      int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_2044f foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
   constraint fk_reference_454f foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_104g foreign key (object_id)
      references ca_objects (object_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_object_id on ca_objects_x_storage_locations (object_id);
create index i_location_id on ca_objects_x_storage_locations (location_id);
create index i_type_id on ca_objects_x_storage_locations (type_id);
create unique index u_all on ca_objects_x_storage_locations (
   object_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);


create table ca_object_lots_x_storage_locations (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   location_id                    int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_2044e foreign key (location_id)
      references ca_storage_locations (location_id) on delete restrict on update restrict,
   constraint fk_reference_454e foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_104f foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_storage_locations (lot_id);
create index i_location_id on ca_object_lots_x_storage_locations (location_id);
create index i_type_id on ca_object_lots_x_storage_locations (type_id);
create unique index u_all on ca_object_lots_x_storage_locations (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   location_id
);

/* -------------------------------------------------------------------------------- */
/*
	BrowseEngine support table fixes
*/
ALTER TABLE ca_browses  MODIFY params longtext not null;
ALTER TABLE ca_browse_results ADD COLUMN rank int unsigned not null;
CREATE INDEX i_rank on ca_browse_results(rank);

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (4, unix_timestamp());