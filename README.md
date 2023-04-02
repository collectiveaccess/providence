# README: Providence version 2.0

### About CollectiveAccess

CollectiveAccess is a web-based suite of applications providing a framework for management, description, and discovery of complex digital and physical collections in museum, archival, and research contexts. It is comprised of two applications. Providence is the “back-end” cataloging component of CollectiveAccess. It is highly configurable and supports a variety of metadata standards, data types, and media formats. Pawtucket2 is CollectiveAccess' general purpose public-access publishing tool. It provides an easy way to create web sites around data managed with Providence. (You can learn more about Pawtucket2 at https://github.com/collectiveaccess/pawtucket2)

CollectiveAccess is freely available under the open source GNU Public License version 3.

### About CollectiveAccess 2.0

This version of CollectiveAccess is compatible with PHP versions 7.4, 8.0, 8.1 and 8.2. 

### Installation

First make sure your server meets all of the [requirements](https://docs.collectiveaccess.org/wiki/Requirements). Then follow the [installation instructions](https://docs.collectiveaccess.org/wiki/Installing_Providence). 

### Updating from Providence version 1.7 or later

NOTE: The update process is relatively safe and rarely, if ever, causes data loss. That said BACKUP YOUR EXISTING DATABASE AND CONFIGURATION prior to updating. You almost certainly will not need it, but if you do you'll be glad it's there.

To update from a version 1.7.x installation decompress the CollectiveAccess Providence 2.0 tar.gz or zip file, and replace the files in your existing installation with those in the update. Take care to preserve your media directory, local configuration directory (`app/conf/local`), any local print templates (`app/printTemplates`) and your setup.php file.

Once the updated files are in place navigate in your web browser to the login screen. You will see this message:

```
Your database is out-of-date. Please install all schema migrations starting with migration #xxx. Click here to automatically apply the required updates, or see the update HOW-TO for instructions on applying database updates manually.
```
 
The migration number may vary depending upon the version you're upgrading from. Click on the `here` link to begin the database update process. 

The search engine and system for sorting data are new in version 2.0. After updating your 1.7.x installation you must run the ```caUtils update-from-1.7``` command.


### Updating from Providence version 1.6 or earlier

To update from a version 1.6.x or older installation you must first update to version 1.7, the follow the 1.7 update instructions.

### Installing development versions

The latest development version is always available in the `develop` branch (https://github.com/collectiveaccess/providence/tree/develop). Other feature-specific development versions are in branches prefixed with `dev/`. To install a development branch follow these steps:

1. clone this repository into the location where you wish it to run using `git clone https://github.com/collectiveaccess/providence`.
2. by default, the newly cloned repository will use the main branch, which contains code for the current release. Choose the `develop` branch by running from within the cloned repository `git checkout develop`.
3. install the PHP package manager [Composer](https://getcomposer.org) if you do not already have it installed on your server.
4. run `composer` from the root of the cloned repository with `composer.phar install`. This will download and install all required 3rd party software libraries. 
5. follow the release version installation instructions to complete the installation.

### Useful Links

* Web site: https://collectiveaccess.org
* Documentation: https://manual.collectiveaccess.org
* Demo: https://demo.collectiveaccess.org/
* Installation instructions: https://manual.collectiveaccess.org/providence/user/setup/installation.html
* Release Notes:  
  * https://manual.collectiveaccess.org/release_notes
* Forum: https://www.collectiveaccess.org/support

To report issues please use GitHub Issues.


### Other modules

Pawtucket2: https://github.com/collectiveaccess/pawtucket2 (The public access front-end application for Providence)
