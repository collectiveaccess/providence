/*
	Date: 4 July 2019
	Migration: 162
	Description:    Transcription UI support
*/

/*==========================================================================*/

create table if not exists ca_representation_transcriptions (
   transcription_id          int unsigned                   not null AUTO_INCREMENT,
   representation_id         int unsigned                   not null references ca_object_representations(representation_id),
   transcription             longtext                       not null,
   created_on                int unsigned                   not null,
   completed_on              int unsigned                   null,
   validated_on              int unsigned                   null,
   is_primary                tinyint unsigned               not null default 0,
   
   ip_addr		             varchar(39)                    not null,
   user_id                   int unsigned                   null references ca_users(user_id),
   
   primary key (transcription_id),

   index i_created_on			    (created_on),
   index i_completed_on      	    (completed_on, is_primary),
   index i_validated_on      	    (validated_on),
   index i_ip_addr				    (ip_addr),
   unique index i_user_id           (user_id, representation_id),
   index i_representation_id        (representation_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;


ALTER TABLE ca_object_representations ADD COLUMN is_transcribable tinyint unsigned not null default 0;
CREATE INDEX i_is_transcribable on ca_object_representations(is_transcribable);


/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (162, unix_timestamp());
