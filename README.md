# README: Providence version 1.8

[![Build Status](https://secure.travis-ci.org/collectiveaccess/providence.png?branch=master)](http://travis-ci.org/collectiveaccess/providence)

### About CollectiveAccess

CollectiveAccess is a web-based suite of applications providing a framework for management, description, and discovery of complex digital and physical collections in museum, archival, and research contexts. It is comprised of two applications. Providence is the “back-end” cataloging component of CollectiveAccess. It is highly configurable and supports a variety of metadata standards, data types, and media formats. Pawtucket2 is CollectiveAccess' general purpose public-access publishing tool. It provides an easy way to create web sites around data managed with Providence. (You can learn more about Pawtucket2 at https://github.com/collectiveaccess/pawtucket2)

CollectiveAccess is freely available under the open source GNU Public License version 3.

### About CollectiveAccess 1.8

Note that this version is not yet compatible with PHP version 8. Please use versions 7.2, 7.3 or 7.4.


### Installation

First make sure your server meets all of the [requirements](https://docs.collectiveaccess.org/wiki/Requirements). Then follow the [installation instructions](https://docs.collectiveaccess.org/wiki/Installing_Providence). 

### Updating from Providence version 1.7 or later

NOTE: The update process is relatively safe and rarely, if ever, causes data loss. That said BACKUP YOUR EXISTING DATABASE AND CONFIGURATION prior to updating. You almost certainly will not need it, but if you do you'll be glad it's there.

To update from a version 1.7.x installation decompress the CollectiveAccess Providence 1.7.8 tar.gz or zip file, and replace the files in your existing installation with those in the update. Take care to preserve your media directory, local configuration directory (`app/conf/local`), any local print templates (`app/printTemplates`) and your setup.php file.

Once the updated files are in place navigate in your web browser to the login screen. You will see this message:

```
Your database is out-of-date. Please install all schema migrations starting with migration #xxx. Click here to automatically apply the required updates, or see the update HOW-TO for instructions on applying database updates manually.
```
 
The migration number may vary depending upon the version you're upgrading from. Click on the `here` link to begin the database update process. 


### Updating from Providence version 1.6 or earlier

NOTE: The update process is relatively safe and rarely, if ever, causes data loss. That said BACKUP YOUR EXISTING DATABASE AND CONFIGURATION prior to updating. You almost certainly will not need it, but if you do you'll be glad it's there.

To update from a version 1.6.x or older installation decompress the CollectiveAccess Providence 1.7.8 tar.gz or zip file, and replace the files in your existing installation with those in the update. Take care to preserve your media directory, local configuration directory (`app/conf/local`), and any local print templates (`app/printTemplates`). 

Next rename your existing setup.php to something else (Eg. `setup.php-old`). Then copy the version 1.7.8 setup.php template in `setup.php-dist` to `setup.php`. Edit this file with your database login information, system name and other basic settings. You can reuse the settings in your old setup.php file as-is. Only the format of the setup.php file has changed. 

Once the updated files are in place navigate in your web browser to the login screen. You will see this message:

```
Your database is out-of-date. Please install all schema migrations starting with migration #xxx. Click here to automatically apply the required updates, or see the update HOW-TO for instructions on applying database updates manually.
```
 
The migration number will vary depending upon the precise version you have installed, but in all cases will be less than 127.
 
Click on the `here` link to begin the database update process. The process may take several minutes with older installations requiring additional time. 

Version 1.7 introduced zoomable page media for multipage documents such as PDFs, Microsoft Word or Powerpoint. Systems migrated from pre-1.7 versions of CollectiveAccess will not have these zoomable media versions available causing the built-in document viewer to fail. If your system includes multipage documents you should regenerate the media using the command-line `caUtils` utility in `support/bin`. The command to run (assuming your current working directory is `support/`) is:

```
bin/caUtils reprocess-media 
```

Be sure to run it as a user that has write permissions on all media.

### Installing development versions

The latest development version is always available in the `develop` branch (https://github.com/collectiveaccess/providence/tree/develop). Other feature-specific development versions are in branches prefixed with `dev/`. To install a development branch follow these steps:

1. clone this repository into the location where you wish it to run using `git clone https://github.com/collectiveaccess/providence`.
2. by default, the newly cloned repository will use the main branch, which contains code for the current release. Choose the `develop` branch by running from within the cloned repository `git checkout develop`.
3.  install the PHP package manager [Composer](https://getcomposer.org) if you do not already have it installed on your server.
4. run `composer` from the root of the cloned repository with `composer.phar install`. This will download and install all required 3rd party software libraries. 
5. follow the release version installation instructions to complete the installation.

### Useful Links

* Web site: https://collectiveaccess.org
* Documentation: https://docs.collectiveaccess.org
* Demo: https://demo.collectiveaccess.org/
* Installation instructions: https://docs.collectiveaccess.org/wiki/Installing_Providence
* Upgrade instructions: https://docs.collectiveaccess.org/wiki/Upgrading_Providence
* Release Notes:  
  * https://docs.collectiveaccess.org/wiki/Release_Notes_for_Providence_1.7
  * https://docs.collectiveaccess.org/wiki/Release_Notes_for_Providence_1.7.8
* Forum: https://www.collectiveaccess.org/support/forum
* Bug Tracker: https://clangers.collectiveaccess.org


### Other modules

Pawtucket2: https://github.com/collectiveaccess/pawtucket2 (The public access front-end application for Providence)
