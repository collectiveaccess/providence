/*
	Date: 27 October 2014
	Migration: 112
	Description: add empty set item labels for all items
*/

INSERT INTO ca_set_item_labels (item_id, locale_id, caption)
  SELECT item_id, (SELECT locale_id FROM ca_locales WHERE dont_use_for_cataloguing = 0 ORDER BY locale_id LIMIT 1), '[BLANK]'
  FROM ca_set_items WHERE item_id NOT IN (SELECT item_id FROM ca_set_item_labels);

/* Always add the update to ca_schema_updates at the end of the file */
INSERT IGNORE INTO ca_schema_updates (version_num, datetime) VALUES (112, unix_timestamp());