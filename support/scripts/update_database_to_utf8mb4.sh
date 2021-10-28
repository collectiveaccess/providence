#!/bin/bash

# update_database_to_utf8mb4.sh <user> <database> [<charset> <collation>]
# changes MySQL/MariaDB charset and collation for one database - all tables and
# all columns in all tables

USER="$1"
DB="$2"
CHARSET="$3"
COLL="$4"

[ -n "$DB" ] || exit 1
[ -n "$CHARSET" ] || CHARSET="utf8mb4"
[ -n "$COLL" ] || COLL="utf8mb4_general_ci"

echo "ALTER DATABASE \`$DB\` CHARACTER SET $CHARSET COLLATE $COLL;" | mysql -u$1 -p

echo "USE \`$DB\`; SHOW TABLES;" | mysql -s | (
    while read TABLE; do
        echo $DB.$TABLE
        echo "ALTER TABLE \`$TABLE\` CONVERT TO CHARACTER SET $CHARSET COLLATE $COLL;" | mysql $DB
    done
)
