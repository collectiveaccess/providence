#!/usr/bin/env bash

export BASE_DIR=$(dirname $0)

# Set environment variables
export CACHE_DIR=${CACHE_DIR:-$BASE_DIR/mysql_profile}
export DB_NAME=${DB_NAME:-ca_test}
export PROFILE=${1:-testing}
export COLLECTIVEACCESS_HOME="$(dirname $(dirname "$0"))"
export PATH="$PATH:$COLLECTIVEACCESS_HOME/support/bin"
export PHP_BIN=${PHP_BIN:-php}

# Install the testing profile
if test ! -e "$CACHE_DIR/$PROFILE.sql" -o -n "$SKIP_CACHED_PROFILE"; then
  echo "Installing profile $PROFILE..."
  "$PHP_BIN" "$COLLECTIVEACCESS_HOME"/support/bin/caUtils install --hostname=localhost --setup="tests/setup-tests.php" \
    --skip-roles --profile-name="$PROFILE" --admin-email=support@collectiveaccess.org && (
        # Export database for later faster import
        echo "Exporting database to cache file: $CACHE_DIR/$PROFILE.sql"
        sudo mysqldump -uroot --hex-blob --complete-insert --extended-insert $DB_NAME >"$CACHE_DIR/$PROFILE.sql"
  )
else
  echo "Skipping profile install"
  if test -e "$CACHE_DIR/$PROFILE.sql" -a -z "$SKIP_CACHED_PROFILE"; then
    echo "Found cached database file $CACHE_DIR/$PROFILE.sql. Importing..."
    sudo mysql -uroot "$DB_NAME" <"$CACHE_DIR/$PROFILE.sql"
  fi
fi
