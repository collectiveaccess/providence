#!/bin/bash

#tmp
chgrp -R daemon /app/apache2/htdocs/app/tmp
chmod 775 $(find /app/apache2/htdocs/app/tmp -type d)
chmod 664 $(find /app/apache2/htdocs/app/tmp -type f)
#logs
chgrp -R daemon /app/apache2/htdocs/app/log
chmod 775 $(find /app/apache2/htdocs/app/log -type d)
chmod 664 $(find /app/apache2/htdocs/app/log -type f)

#media
chgrp -R daemon /app/apache2/htdocs/media
chmod 775 $(find /app/apache2/htdocs/media -type d)
chmod 664 $(find /app/apache2/htdocs/media -type f)

#htmlpurifier plugin
chgrp -R daemon /app/apache2/htdocs/vendor/
chmod 775 $(find /app/apache2/htdocs/vendor -type d)
chmod 664 $(find /app/apache2/htdocs/vendor -type f)
