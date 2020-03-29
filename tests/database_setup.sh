#!/usr/bin/env bash

# Set environment variables
export PROFILE=${1:-testing}
export DB_NAME=ca_test
export DB_USER=ca_test
export DB_PASSWORD=password
export COLLECTIVEACCESS_HOME="$(dirname $(dirname "$0"))"
export PATH="$PATH:$COLLECTIVEACCESS_HOME/support/bin"

# Initialise the database instance for the test
sudo mysql -uroot -e "DROP DATABASE ${DB_NAME};"
sudo mysql -uroot -e "create database ${DB_NAME};"
sudo mysql -uroot -e "grant all on ${DB_NAME}.* to '${DB_USER}'@'localhost' identified by '${DB_PASSWORD}';"

# Install the testing profile
"$COLLECTIVEACCESS_HOME"/support/bin/caUtils install --hostname=localhost --setup="tests/setup-tests.php" \
  --skip-roles --profile-name="$PROFILE" --admin-email=support@collectiveaccess.org

