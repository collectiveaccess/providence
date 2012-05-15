/* 
	Date: 12 January 2011
	Migration: 32
	Description:
*/

/*==============================================================*/
/* Tables for MYSQL inverted index in a table search backend    */
/*==============================================================*/
DROP TABLE IF EXISTS ca_sql_search_word_index;
CREATE TABLE ca_sql_search_word_index (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	word_id				int unsigned		not null references ca_sql_search_words(word_id),
	seq					int unsigned		not null,
	boost				int 				not null default 1,
	
	PRIMARY KEY								(index_id),
	
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id),
	INDEX				i_word_id			(word_id)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------
DROP TABLE IF EXISTS ca_sql_search_text;
CREATE TABLE ca_sql_search_text (
	index_id			int unsigned		not null auto_increment,
	
	table_num			tinyint unsigned 	not null,
	row_id				int unsigned 		not null,
	
	field_table_num		tinyint unsigned	not null,
	field_num			tinyint unsigned	not null,
	field_row_id		int unsigned		not null,
	
	fieldtext			longtext 			not null,
	
	PRIMARY KEY								(index_id),
	FULLTEXT INDEX		f_fulltext			(fieldtext),
	INDEX				i_table_num			(table_num),
	INDEX				i_row_id			(row_id),
	INDEX				i_field_table_num	(field_table_num),
	INDEX				i_field_num			(field_num),
	INDEX				i_field_row_id		(field_row_id)
	
) TYPE=myisam character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------
DROP TABLE IF EXISTS ca_sql_search_words;
CREATE TABLE ca_sql_search_words (
	word_id				int unsigned 		not null auto_increment,
	word				varchar(255)		not null,
	soundex				varchar(4)			not null,
	metaphone			varchar(255)		not null,
	stem				varchar(255)		not null,
	locale_id	smallint unsigned null,
	
	PRIMARY KEY								(word_id),
	UNIQUE INDEX		u_word				(word),
	INDEX				i_soundex			(soundex),
	INDEX				i_metaphone			(metaphone),
	INDEX				i_stem				(stem),
	INDEX				i_locale_id			(locale_id)
) TYPE=innodb character set utf8 collate utf8_general_ci;

# -----------------------------------------------------------------
DROP TABLE IF EXISTS ca_sql_search_ngrams;
CREATE TABLE ca_sql_search_ngrams (
	word_id				int unsigned		not null references ca_sql_search_words(word_id),
	ngram				char(4)				not null,
	seq					tinyint unsigned	not null,
	
	PRIMARY KEY								(word_id, seq),
	INDEX				i_ngram				(ngram)
) TYPE=innodb character set utf8 collate utf8_general_ci;

/* -------------------------------------------------------------------------------- */

DROP TABLE IF EXISTS ca_data_import_event_log;
create table ca_data_import_event_log
(
   log_id                       int unsigned                   not null AUTO_INCREMENT,
   event_id                    int unsigned                   not null,
   item_id                      int unsigned                   null,
   type_code                  char(10)                       not null,
   date_time                  int unsigned                   not null,
   message                    text                           not null,
   source                       varchar(255)                   not null,
   primary key (log_id),
   constraint fk_ca_data_import_events_event_id foreign key (event_id)
      references ca_data_import_events (event_id) on delete restrict on update restrict,
    constraint fk_ca_data_import_events_item_id foreign key (item_id)
      references ca_data_import_items (item_id) on delete restrict on update restrict
) type=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_event_id on ca_data_import_event_log(event_id);
create index i_item_id on ca_data_import_event_log(item_id);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (32, unix_timestamp());
