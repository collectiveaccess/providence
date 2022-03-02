#!/bin/bash
cd /app/apache2/htdocs 
composer install 
sudo setup_ca_permissions.sh
sudo apachectl -D FOREGROUND
