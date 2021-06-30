#!/bin/bash
cd /app/apache2/htdocs 
cp docker_templates/setup.php.docker /app/apache2/htdocs/setup.php
composer install 
sudo setup_ca_permissions.sh
sudo apachectl -D FOREGROUND
