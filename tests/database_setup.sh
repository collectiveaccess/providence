#!/usr/bin/env bash

export BASE_DIR=$(dirname $0)

# Set environment variables.
export CACHE_DIR=${CACHE_DIR:-mysql_profile}
export DB_NAME=${DB_NAME:-ca_test}
export DB_USER=${DB_USER:-ca_test}
export DB_PASSWORD=${DB_PASSWORD:-password}

# Create cache dir
echo "Creating cache dir at ${CACHE_DIR}"
mkdir -p "${CACHE_DIR}"

# Initialise the database instance for the test.
echo "Drop existing database"
sudo mysql -uroot -e "DROP DATABASE ${DB_NAME};"
echo "Create database"
sudo mysql -uroot -e "create database ${DB_NAME};"
echo "Grant permissions to database"
sudo mysql -uroot -e "grant all on ${DB_NAME}.* to '${DB_USER}'@'localhost' identified by '${DB_PASSWORD}';"

# Add custom configuration from file.
echo "Configuring MySQL server"
MYCNF_CONFIG="${MYCNF_CONFIG:-../$BASE_DIR/tests/my.cnf}"
if test -e "$MYCNF_CONFIG"; then
  cat "$MYCNF_CONFIG" | sudo tee -a /etc/mysql/my.cnf
fi

# Restart service.
echo "Restarting MySQL server"
sudo service mysql restart

# Show variables.
echo "Show updated MySQL server variables"
sudo mysql -uroot -e 'show variables;'
