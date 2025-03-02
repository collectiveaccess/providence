/*
	Date: 28 November 2020
	Migration: 166
	Description:    Add deaccession "authorized by" field
*/

/*==========================================================================*/

ALTER TABLE ca_collections ADD COLUMN accession_sdatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN accession_edatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN deaccession_sdatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN deaccession_edatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN deaccession_disposal_sdatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN deaccession_disposal_edatetime decimal(30,20) null;
ALTER TABLE ca_collections ADD COLUMN is_deaccessioned tinyint not null default 0;
ALTER TABLE ca_collections ADD COLUMN deaccession_notes text not null;
ALTER TABLE ca_collections ADD COLUMN deaccession_authorized_by varchar(255) not null default '';
ALTER TABLE ca_collections ADD COLUMN deaccession_type_id int unsigned null;

ALTER TABLE ca_collections ADD CONSTRAINT fk_ca_collections_deaccession_type_id foreign key (deaccession_type_id)
      references ca_list_items (item_id) ON delete restrict ON update restrict;

ALTER TABLE ca_collections ADD COLUMN home_location_id int unsigned null;
      
ALTER TABLE ca_collections ADD CONSTRAINT fk_ca_collections_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) ON delete restrict ON update restrict;
      
CREATE INDEX i_home_location_id ON ca_collections(home_location_id);
CREATE INDEX i_accession_sdatetime ON ca_collections(accession_sdatetime);
CREATE INDEX i_accession_edatetime ON ca_collections(accession_edatetime);
CREATE INDEX i_deaccession_sdatetime ON ca_collections(deaccession_sdatetime);
CREATE INDEX i_deaccession_edatetime ON ca_collections(deaccession_edatetime);
CREATE INDEX i_deaccession_disposal_sdatetime ON ca_collections(deaccession_disposal_sdatetime);
CREATE INDEX i_deaccession_disposal_edatetime ON ca_collections(deaccession_disposal_edatetime);
CREATE INDEX i_deaccession_auth_by ON ca_collections(deaccession_authorized_by);
CREATE INDEX i_deaccession_type_id ON ca_collections(deaccession_type_id);
CREATE INDEX i_is_deaccessioned ON ca_collections(is_deaccessioned);

ALTER TABLE ca_object_lots ADD COLUMN accession_sdatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN accession_edatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_sdatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_edatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_disposal_sdatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_disposal_edatetime decimal(30,20) null;
ALTER TABLE ca_object_lots ADD COLUMN is_deaccessioned tinyint not null default 0;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_notes text not null;
ALTER TABLE ca_object_lots ADD COLUMN deaccession_authorized_by varchar(255) not null default '';
ALTER TABLE ca_object_lots ADD COLUMN deaccession_type_id int unsigned null;

ALTER TABLE ca_object_lots ADD CONSTRAINT fk_ca_object_lots_deaccession_type_id foreign key (deaccession_type_id)
      references ca_list_items (item_id) ON delete restrict ON update restrict;

ALTER TABLE ca_object_lots ADD COLUMN home_location_id int unsigned null;
      
ALTER TABLE ca_object_lots ADD CONSTRAINT fk_ca_object_lots_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) ON delete restrict ON update restrict;
      
CREATE INDEX i_home_location_id ON ca_object_lots(home_location_id);
CREATE INDEX i_accession_sdatetime ON ca_object_lots(accession_sdatetime);
CREATE INDEX i_accession_edatetime ON ca_object_lots(accession_edatetime);
CREATE INDEX i_deaccession_sdatetime ON ca_object_lots(deaccession_sdatetime);
CREATE INDEX i_deaccession_edatetime ON ca_object_lots(deaccession_edatetime);
CREATE INDEX i_deaccession_disposal_sdatetime ON ca_object_lots(deaccession_disposal_sdatetime);
CREATE INDEX i_deaccession_disposal_edatetime ON ca_object_lots(deaccession_disposal_edatetime);
CREATE INDEX i_deaccession_auth_by ON ca_object_lots(deaccession_authorized_by);
CREATE INDEX i_deaccession_type_id ON ca_object_lots(deaccession_type_id);
CREATE INDEX i_is_deaccessioned ON ca_object_lots(is_deaccessioned);
      
ALTER TABLE ca_object_representations ADD COLUMN home_location_id int unsigned null;
      
ALTER TABLE ca_object_representations ADD CONSTRAINT fk_ca_object_representations_home_location_id foreign key (home_location_id)
      references ca_storage_locations (location_id) ON delete restrict ON update restrict;
      
CREATE INDEX i_home_location_id ON ca_object_representations(home_location_id);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (166, unix_timestamp());
