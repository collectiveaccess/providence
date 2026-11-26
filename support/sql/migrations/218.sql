/*
	Date: 19 November 2026
	Migration: 218
	Description: Add tables for remembered devices, passkeys and per-user API keys
*/
/*==========================================================================*/
/* Browsers remembered after two-factor authentication. Only a hash of each token is stored. */
create table ca_user_mfa_devices (
   device_id                      int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   selector                       char(24)                       not null,
   validator_hash                 char(64)                       not null,
   user_agent                     varchar(255)                   not null default '',
   ip_addr                        varchar(39)                    not null default '',
   created_on                     int unsigned                   not null,
   expires_on                     int unsigned                   not null,
   last_used_on                   int unsigned                   null,
   primary key (device_id),
   
   unique index u_selector (selector),
   index i_user_id (user_id),
   constraint fk_ca_user_mfa_devices_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/* Passkeys (WebAuthn credentials) usable as a second factor */
create table ca_user_mfa_passkeys (
   passkey_id                     int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   name                           varchar(255)                   not null,
   credential_id                  varchar(512)                   not null,
   public_key                     text                           not null,
   sign_count                     int unsigned                   not null default 0,
   created_on                     int unsigned                   not null,
   last_used_on                   int unsigned                   null,
   primary key (passkey_id),
   
   unique index u_credential_id (credential_id(255)),
   index i_user_id (user_id),
   constraint fk_ca_user_mfa_passkeys_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/* Per-user API keys for scripts and integrations. Only a hash of each key is stored. */
create table ca_user_api_keys (
   key_id                         int unsigned                   not null AUTO_INCREMENT,
   user_id                        int unsigned                   not null,
   name                           varchar(255)                   not null,
   key_prefix                     char(12)                       not null,
   key_hash                       char(64)                       not null,
   created_on                     int unsigned                   not null,
   expires_on                     int unsigned                   null,
   last_used_on                   int unsigned                   null,
   primary key (key_id),
   
   unique index u_key_prefix (key_prefix),
   index i_user_id (user_id),
   constraint fk_ca_user_api_keys_user_id foreign key (user_id)
      references ca_users (user_id) on delete restrict on update restrict
) engine=innodb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

/*==========================================================================*/

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, modifier, datetime, schema_name) VALUES (218, 0, unix_timestamp(), 'CORE');
