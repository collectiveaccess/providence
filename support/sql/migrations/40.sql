/* 
	Date: 31 May 2011
	Migration: 40
	Description:
*/


DROP TABLE IF EXISTS ca_sql_search_words;
DROP TABLE IF EXISTS ca_sql_search_word_index;
DROP TABLE IF EXISTS ca_sql_search_text;
DROP TABLE IF EXISTS ca_sql_search_date_index;
DROP TABLE IF EXISTS ca_sql_search_ngrams;

/*==========================================================================*/
create table ca_sql_search_words 
(
  word_id int(10) unsigned not null auto_increment,
  word varchar(255) not null,
  stem varchar(255) not null,
  locale_id smallint(5) unsigned default null,
  
  primary key (word_id)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create unique index u_word on ca_sql_search_words(word);
create index i_stem on ca_sql_search_words(stem);
create index i_locale_id on ca_sql_search_words(locale_id);


/*==========================================================================*/
create table ca_sql_search_word_index (
  table_num tinyint(3) unsigned not null,
  row_id int(10) unsigned not null,
  field_table_num tinyint(3) unsigned not null,
  field_num tinyint(3) unsigned not null,
  field_row_id int(10) unsigned not null,
  word_id int(10) unsigned not null,
  boost tinyint unsigned not null default '1'
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_row_id on ca_sql_search_word_index(row_id, table_num);
create index i_word_id on ca_sql_search_word_index(word_id);


/*==========================================================================*/
create table ca_sql_search_ngrams (
  word_id int(10) unsigned not null,
  ngram char(4) not null,
  seq tinyint(3) unsigned not null,
  
  primary key (word_id,seq)
) engine=innodb CHARACTER SET utf8 COLLATE utf8_general_ci;

create index i_ngram on ca_sql_search_ngrams(ngram);


/* -------------------------------------------------------------------------------- */

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (40, unix_timestamp());