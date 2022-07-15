FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

#Apache Requirements
RUN apt -qq update && apt-get -qq install curl wget gnupg && apt-get -qq install wget vim libreadline-dev libssl-dev libpcre3-dev libexpat1-dev build-essential bison zlib1g-dev libxss1 libappindicator1 libindicator7 sudo tzdata unzip less

#Misc plugin requirements
#GraphicsMagick/Mediainfo/Plugins
RUN apt-get -qq install dcraw exiftool git graphicsmagick imagemagick mediainfo libmagickwand-dev libmagickcore-dev libgraphicsmagick1-dev poppler-utils

#wkhtmltopdf
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_amd64.deb && apt-get install -qq ./wkhtmltox_0.12.6-1.focal_amd64.deb && rm wkhtmltox_0.12.6-1.focal_amd64.deb

#pdfminer - if needed
#RUN apt-get -qq update && apt-get -qq install python3-pip && pip install pdfminer.six

#libreoffice
RUN apt-get -qq install libreoffice

# apache
ARG APACHE_VERSION=2.4.53
RUN wget https://ai.galib.uga.edu/files/httpd-$APACHE_VERSION-w-apr.tar.gz && tar xzf httpd-$APACHE_VERSION-w-apr.tar.gz && cd httpd-$APACHE_VERSION && ./configure  '--prefix=/app/apache2' '--with-apxs2=/app/apache2/bin/apxs' '--with-mysqli' '--with-pear' '--with-xsl' '--with-pspell' '--enable-ssl' '--with-gettext' '--enable-gd' '--enable-mbstring' '--with-mcrypt' '--enable-soap' '--enable-sockets' '--with-libdir=/lib/i386-linux-gnu' '--with-jpeg-dir=/usr' '--with-png-dir=/usr' '--with-curl' '--with-pdo-mysql' '--enable-so' '--with-included-apr' && make -j$(nproc) && make install && cd ..&& rm -rf httpd-$APACHE_VERSION*

ENV PATH /app/apache2/bin:$PATH

RUN sed -i "s/\/snap\/bin/\/snap\/bin:\/app\/apache2\/bin/" /etc/sudoers

#PHP
ARG PHP_VERSION=7.4.30
RUN apt-get -qq install libcurl4-gnutls-dev pkg-config libpng-dev libonig-dev libsqlite3-dev libxml2-dev libzip-dev libmemcached-dev memcached && wget https://www.php.net/distributions/php-$PHP_VERSION.tar.gz && tar xzf php-$PHP_VERSION.tar.gz && cd php-$PHP_VERSION && './configure'  '--prefix=/usr/local' '--with-apxs2=/app/apache2/bin/apxs' '--with-mysqli' '--enable-mbstring' '--with-pdo-mysql' '--with-openssl' '--with-zlib' '--enable-gd' '--enable-opcache' '--with-curl' '--enable-exif' '--with-zip' '--with-readline' '--enable-intl' && make -j$(nproc) && make install && cp php.ini-production /usr/local/lib/php.ini && cd .. && rm -rf php-$PHP_VERSION*

RUN apt-get -qq install autoconf && wget https://pecl.php.net/get/memcached-3.1.5.tgz && tar xzf memcached-3.1.5.tgz && cd memcached-3.1.5 && phpize && ./configure && make && make install && echo "extension=memcached.so" >> /usr/local/lib/php.ini && cd .. && rm -rf memcached-3.1.5*

#Composer
RUN curl --output /usr/local/bin/composer https://getcomposer.org/composer.phar     && chmod +x /usr/local/bin/composer

#gmagick extension
RUN git clone https://github.com/vitoc/gmagick.git && cd gmagick && phpize && ./configure && make -j$(nproc) && make install && cd .. && rm -rf gmagick && echo "extension=gmagick.so" >> /usr/local/lib/php.ini

RUN echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf

#FFMPEG
ARG FFMPEG_VERSION=4.3.3
RUN apt-get install -qq autoconf automake build-essential cmake git-core libass-dev libfreetype6-dev libgnutls28-dev libsdl2-dev libtool libva-dev libvdpau-dev libvorbis-dev libxcb1-dev libxcb-shm0-dev libxcb-xfixes0-dev meson ninja-build pkg-config texinfo wget yasm zlib1g-dev nasm libx264-dev libx265-dev libnuma-dev libvpx-dev libfdk-aac-dev libmp3lame-dev libopus-dev libaom-dev && wget https://ffmpeg.org/releases/ffmpeg-$FFMPEG_VERSION.tar.gz && tar xzf ffmpeg-$FFMPEG_VERSION.tar.gz && cd ffmpeg-$FFMPEG_VERSION && ./configure --enable-gpl --enable-gnutls --enable-libaom --enable-libass --enable-libfdk-aac --enable-libfreetype --enable-nonfree --enable-libmp3lame --enable-libopus --enable-libvorbis --enable-libvpx --enable-libx264 --enable-libx265 && make -j$(nproc) && make install && cd .. && rm -rf ffmpeg-$FFMPEG_VERSION*

#Config changes for apache/php
RUN sed -i "s/memory_limit = 128M/memory_limit = 4G/" /usr/local/lib/php.ini && sed -i "s/post_max_size = 8M/post_max_size = 2048M/" /usr/local/lib/php.ini && sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 2048M/" /usr/local/lib/php.ini && sed -i "s/display_errors = Off/display_errors = On/" /usr/local/lib/php.ini && sed -i "s/max_execution_time = 30/max_execution_time = 300/g" /usr/local/lib/php.ini

#PHPRedis
RUN apt update && apt-get install -qq -y libzstd-dev && wget https://github.com/phpredis/phpredis/archive/refs/tags/5.3.4.tar.gz && tar xzf 5.3.4.tar.gz && cd phpredis-5.3.4 && phpize && ./configure --enable-redis-zstd && make -j$(nproc) && make install && echo "extension=redis.so" >> /usr/local/lib/php.ini &&  cd .. && rm -rf phpredis-5.3.4


#Change the LOCAL_UID to that of your user that owns the git repo files (or yours for local non-mac envs)
ARG LOCAL_UID=1000
#GitLab runner
RUN adduser --uid $LOCAL_UID --gecos 'gitlab-runner user' --disabled-password gitlab-runner
COPY docker_templates/gitlab-runner /etc/sudoers.d/

#Prepare docroot
RUN rm -rf /app/apache2/htdocs 

#Copy startup script, setup file, and permissions script
COPY docker_templates/startup.sh /
COPY docker_templates/setup_ca_permissions.sh /usr/local/bin/
COPY docker_templates/httpd.conf /app/apache2/conf/

ARG SERVERNAME=localhost:8088
RUN sed -i "s/localhost\:8088/$SERVERNAME/" /app/apache2/conf/httpd.conf

USER gitlab-runner

CMD ["./startup.sh"]
