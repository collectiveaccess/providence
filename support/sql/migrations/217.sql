/*
	Date: 19 November 2026
	Migration: 217
	Description: Add tables for two-factor authentication
*/
/*==========================================================================*/
/* Two-factor authentication. Secrets are encrypted at rest; recovery codes are hashed. */
/* No BaseModel classes on purpose, so this data never ends up in the change log, search or exports */
create table ca_user_mfa (
   mfa_id                         int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   mfa_type                       varchar(20)                    not null,
   secret                         varchar(255)                   not null,
   created_on                     int unsigned                   not null,
   enabled_on                     int unsigned                   null,
   last_used_step                 bigint unsigned                not null default 0,
   primary key (mfa_id),
   
   unique index u_user_type (user_id, mfa_type),
   constraint fk_ca_user_mfa_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create table ca_user_mfa_recovery_codes (
   code_id                        int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   code_hash                      varchar(255)                   not null,
   created_on                     int unsigned                   not null,
   primary key (code_id),
   
   index i_user_id (user_id),
   constraint fk_ca_user_mfa_recovery_codes_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, modifier, datetime, schema_name) VALUES (217, 0, unix_timestamp(), 'CORE');
