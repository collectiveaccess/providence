/* 
	Date: 17 November 2010
	Migration: 31
	Description:
*/

/* -------------------------------------------------------------------------------- */
/*
	Fix for http://bugs.collectiveaccess.org:9000/browse/PROV-239
*/

DELIMITER $$
DROP PROCEDURE IF EXISTS `drop_index_if_not_exists`$$

CREATE DEFINER=`user`@`%` PROCEDURE `drop_index_if_not_exists`(table_name_vc varchar(50), index_name_vc varchar(50))
SQL SECURITY INVOKER
BEGIN

set @Index_cnt = (
select	count(1) cnt
FROM	INFORMATION_SCHEMA.STATISTICS
WHERE	table_name = table_name_vc
and	index_name = index_name_vc and index_schema = database()
);

IF ifnull(@Index_cnt,0) > 0 THEN set @index_sql = concat('DROP INDEX ',index_name_vc,' ON ',table_name_vc,';');

PREPARE stmt FROM @index_sql;
EXECUTE stmt;

DEALLOCATE PREPARE stmt;

END IF;

END$$
DELIMITER ;

alter ignore table ca_list_item_labels drop foreign key fk_ca_list_item_labels_locale_id;
call drop_index_if_not_exists('ca_list_item_labels', 'u_all');
create unique index u_all on ca_list_item_labels
(
   item_id,
   name_singular,
   name_plural,
   type_id,
   locale_id
);


/* -------------------------------------------------------------------------------- */
/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (31, unix_timestamp());
