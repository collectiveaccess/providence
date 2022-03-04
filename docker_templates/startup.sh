#!/bin/bash
cd /app/apache2/htdocs 
composer install 
sudo apachectl -D FOREGROUND
