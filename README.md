# README: Providence version 1.7.9

[![Build Status](https://secure.travis-ci.org/collectiveaccess/providence.png?branch=master)](http://travis-ci.org/collectiveaccess/providence)

### About CollectiveAccess

CollectiveAccess is a web-based suite of applications providing a framework for management, description, and discovery of complex digital and physical collections in museum, archival, and research contexts. It is comprised of two applications. Providence is the “back-end” cataloging component of CollectiveAccess. It is highly configurable and supports a variety of metadata standards, data types, and media formats. Pawtucket2 is CollectiveAccess' general purpose public-access publishing tool. It provides an easy way to create web sites around data managed with Providence. (You can learn more about Pawtucket2 at https://github.com/collectiveaccess/pawtucket2)

CollectiveAccess is freely available under the open source GNU Public License version 3.

### About CollectiveAccess 1.7.9

Version 1.7.9 is a maintenance release with bug fixes and support for MySQL 8.0 and PHP 7.3 and 7.4. 


### Installation

First make sure your server meets all of the [requirements](https://docs.collectiveaccess.org/wiki/Requirements). Then follow the [installation instructions](https://docs.collectiveaccess.org/wiki/Installing_Providence). 

### Updating from Providence 1.7.8 or earlier

NOTE: The update process is relatively safe and rarely, if ever, causes data loss. That said BACKUP YOUR EXISTING DATABASE AND CONFIGURATION prior to updating. You almost certainly will not need it, but if you do you'll be glad it's there.

To update decompress the CollectiveAccess Providence 1.7.9 tar.gz or zip file, and replace the files in your existing installation with those in the update. Take care to preserve your media directory, local configuration directory (`app/conf/local`), any local print templates (`app/printTemplates`) and your setup.php file.

If you are updating from a version prior to 1.7, you must rename your existing setup.php (Eg. to `setup.php-old`) and copy the version 1.7.9 setup.php template in `setup.php-dist` to `setup.php`. Edit this file with your database login information, system name and other basic settings. You can reuse the settings in your old setup.php file as-is. Only the format of the setup.php file has changed. If you are updating from version 1.7.x you do not need to change your setup.php file.

Once the updated files are in place navigate in your web browser to the login screen. You will see this message:

```
Your database is out-of-date. Please install all schema migrations starting with migration #xxx. Click here to automatically apply the required updates, or see the update HOW-TO for instructions on applying database updates manually.
```
 
The migration number may vary depending upon the version you're upgrading from. Click on the `here` link to begin the database update process. 

Version 1.7 introduced zoomable page media for multipage documents such as PDFs, Microsoft Word or Powerpoint. Systems migrated from pre-1.7 versions of CollectiveAccess will not have these zoomable media versions available causing the built-in document viewer to fail. If your system includes multipage documents you should regenerate the media using the command-line `caUtils` utility in `support/bin`. The command to run (assuming your current working directory is `support/`) is:

```
bin/caUtils reprocess-media 
```

Be sure to run it as a user that has write permissions on all media. You do not need to reprocess media if you are updating from a 1.7.x system.


### Useful Links

* Web site: https://collectiveaccess.org
* Documentation: https://manual.collectiveaccess.org
* Demo: https://demo.collectiveaccess.org/
* System requirements: https://manual.collectiveaccess.org/setup/systemReq.html
* Forum: https://www.collectiveaccess.org/support
* Bug Tracker: https://clangers.collectiveaccess.org


### Other modules

Pawtucket2: https://github.com/collectiveaccess/pawtucket2 (The public access front-end application for Providence)
