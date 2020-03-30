#!/usr/bin/env bash

# Set environment variables.
export DB_NAME=${DB_NAME:-ca_test}
export DB_USER=${DB_USER:-ca_test}
export DB_PASSWORD=${DB_PASSWORD:-password}

# Initialise the database instance for the test.
sudo mysql -uroot -e "DROP DATABASE ${DB_NAME};"
sudo mysql -uroot -e "create database ${DB_NAME};"
sudo mysql -uroot -e "grant all on ${DB_NAME}.* to '${DB_USER}'@'localhost' identified by '${DB_PASSWORD}';"

# Add custom configuration from file.
MYCNF_CONFIG="${MYCNF_CONFIG:-$TRAVIS_BUILD_DIR/tests/my.cnf}"
if test -e "$MYCNF_CONFIG";
then
  cat "$MYCNF_CONFIG" | sudo tee -a /etc/mysql/my.cnf;
fi

# Restart service.
sudo service mysql restart

# Show variables.
sudo mysql -uroot -e 'show variables;'
