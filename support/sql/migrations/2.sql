/* 
	Date: 14 September 2009
	Migration: 2
	Description:
*/

/* Tagging/commenting/rating control fields */
/* Provide per-item control when needed */
ALTER TABLE ca_objects ADD COLUMN commenting_status tinyint unsigned not null;	/* 0=moderated commenting; 1=unmoderated commenting; 2=no commenting */
ALTER TABLE ca_objects ADD COLUMN tagging_status tinyint unsigned not null;	/* 0=moderated tagging; 1=unmoderated tagging; 2=no tagging */
ALTER TABLE ca_objects ADD COLUMN rating_status tinyint unsigned not null;	/* 0=rating allowed; 1=rating not allowed */

ALTER TABLE ca_object_lots ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_object_lots ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_object_lots ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_entities ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_entities ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_entities ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_places ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_places ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_places ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_occurrences ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_occurrences ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_occurrences ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_collections ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_collections ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_collections ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_object_representations ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_object_representations ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_object_representations ADD COLUMN rating_status tinyint unsigned not null;

ALTER TABLE ca_sets ADD COLUMN commenting_status tinyint unsigned not null;	
ALTER TABLE ca_sets ADD COLUMN tagging_status tinyint unsigned not null;
ALTER TABLE ca_sets ADD COLUMN rating_status tinyint unsigned not null;

/* Support for vocabulary term keywords on object_representations, object_lots, object_events and object_lot_events */

create table ca_object_lots_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   lot_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_2044d foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_reference_454d foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_104e foreign key (lot_id)
      references ca_object_lots (lot_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_lot_id on ca_object_lots_x_vocabulary_terms (lot_id);
create index i_item_id on ca_object_lots_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_lots_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_lots_x_vocabulary_terms (
   lot_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);

create table ca_representations_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   representation_id              int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_204e foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_reference_45e foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_10f foreign key (representation_id)
      references ca_object_representations (representation_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_representation_id on ca_representations_x_vocabulary_terms (representation_id);
create index i_item_id on ca_representations_x_vocabulary_terms (item_id);
create index i_type_id on ca_representations_x_vocabulary_terms (type_id);
create unique index u_all on ca_representations_x_vocabulary_terms (
   representation_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);

create table ca_object_events_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_204f foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_reference_45f foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_10g foreign key (event_id)
      references ca_object_events (event_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_events_x_vocabulary_terms (event_id);
create index i_item_id on ca_object_events_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_events_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);

create table ca_object_lot_events_x_vocabulary_terms (
   relation_id                    int unsigned                   not null AUTO_INCREMENT,
   event_id                         int unsigned               not null,
   type_id                        smallint unsigned              not null,
   item_id                        int unsigned                   not null,
   source_info                    longtext                       not null,
   sdatetime                      decimal(30,20),
   edatetime                      decimal(30,20),
   rank                           int unsigned                   not null,
   primary key (relation_id),
   constraint fk_reference_204g foreign key (item_id)
      references ca_list_items (item_id) on delete restrict on update restrict,
   constraint fk_reference_45g foreign key (type_id)
      references ca_relationship_types (type_id) on delete restrict on update restrict,
   constraint fk_reference_10h foreign key (event_id)
      references ca_object_lot_events (event_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_object_lot_events_x_vocabulary_terms (event_id);
create index i_item_id on ca_object_lot_events_x_vocabulary_terms (item_id);
create index i_type_id on ca_object_lot_events_x_vocabulary_terms (type_id);
create unique index u_all on ca_object_lot_events_x_vocabulary_terms (
   event_id,
   type_id,
   sdatetime,
   edatetime,
   item_id
);

/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (2, unix_timestamp());