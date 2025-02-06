# README: Providence version 2.0

### About CollectiveAccess

CollectiveAccess is collections management and presentation software maintained by Whirl-i-Gig and contributed to by the open-source community. The CollectiveAccess project began in 2003 as a response to the lack of non-commercial, affordable, open-source solutions for digital collections management. Almost two decades later, CollectiveAccess has projects on 5 continents, providing hundreds of institutions with configurable, up-to-date collections management software.

A web-based suite of applications providing a framework for management, description, and discovery of complex digital and physical collections in museum, archival, and research contexts, CollectiveAccess consists of two applications: Providence and Pawtucket2. Providence, the “back-end” cataloging component of CollectiveAccess, is highly configurable and can support a variety of metadata standards, data types, and media formats, including document, images, audio, video and 3d. Pawtucket2 is CollectiveAccess' general purpose public-access publishing tool, enabling creation of interactive web sites around data managed with Providence. (You can learn more about Pawtucket2 at https://github.com/collectiveaccess/pawtucket2)

CollectiveAccess is freely available under the open source GNU Public License version 3, meaning that it is free to download, use and share without licensing restrictions.

### About CollectiveAccess 2.0

This version of CollectiveAccess is compatible with PHP versions 8.2 and 8.3. We are currently testing compatibility with PHP 8.4, but it should be usable with that version as well.  It can be made to work with PHP versions as old as 7.4 if need be, but it is unsupported when used with pre 8.2 versions of PHP.

### What's New

It has been a while since the previous version, 1.7, was released. CollectiveAccess version 2.0 offers a wide range of new and improved features and functionality, including: 

* New, more flexible system for tracking changes, such as location history or provenance, over time.
* Improvements to search indexing and built-in search engine to better support hierarchical indexing, text searches including punctuation and non-roman characters, searches on complex accession numbers and more.
* Improved background processing system. Media processing and search indexing background tasks are now more launched more reliably as needed rather than relying on externally configured cron tasks.
* Reporting enhancements, including support for interactive user-provided reporting parameters and background processing of large exports.
* External export system to facilitate integration with digital preservation systems via configurable export of BagIT packages.
* New metadata element data types for file size values and references to media from YouTube, Vimeo, GoogleDocs, Internet Archive and other external services.
* Support for automated translation of profile text (field names, user interface, etc.) using machine translation services such as Google Translate and DeepL.
* Support for automated transcription of audio/video materials using OpenAI Whisper.
* New data import formats, including DublinCoreXML, EHive, METS, MODS, Musearch, SqlLite and iDigBio.
* New GraphQL-based API providing search, browse, introspection and editing functionality.
* Expanded data replication system for synchronization of two or more CollectiveAccess systems.
* Improved support for extraction of media metadata using MediaInfo and EXIFTool.
* ... and many many bug fixes and improvements ...

### Installation

To install CollectiveAccess version 2.0, first make sure your server meets all of the [requirements](https://docs.collectiveaccess.org/providence/user/setup/systemReq). Then follow the [installation instructions](https://docs.collectiveaccess.org/providence/user/setup/install/). 

### Updating from Providence version 1.7 or later

NOTE: The update process is relatively safe and rarely, if ever, causes data loss. That said, **BACKUP YOUR EXISTING DATABASE AND CONFIGURATION** prior to updating. You almost certainly will not need the backup, but if you do you'll be glad it's there.

To update from a version 1.7.x installation decompress the CollectiveAccess Providence 2.0 tar.gz or zip file, and replace the files in your existing installation with those in the update. Take care to preserve your media directory, local configuration directory (`app/conf/local`), any local print templates (`app/printTemplates`) and your setup.php file. For notes on this process see https://docs.collectiveaccess.org/providence/user/upgrades/upgrade-ca-inplace-git.

The configuration for tracking current location of objects has changed in version 2.0. If your installation uses location tracking for collection objects, the old configuration in ``app.conf`` will need to be updated to conform to the 2.0 configuration format. See https://docs.collectiveaccess.org/providence/user/reporting/history_tracking_current_value for details on configuration.

Once the updated code and configuration are in place, navigate in your web browser to the login screen. You will see a message like this:

```
Your database is out-of-date. Please install all schema migrations starting with migration #xxx. Click here to automatically apply the required updates, or see the update HOW-TO for instructions on applying database updates manually.
```
 
The migration number may vary depending upon the specific version of 1.7.x you're upgrading from. Between version 1.7 and 2.0 there have been several modifications to the CollectiveAccess database structure, as well as structural changes to better support sorting, search indexing and tracking of values (such as location history) over time. To implement these changes and complete the update run the ``caUtils`` commmand ```update-from-1-7```. Note that for larger systems ```update-from-1-7```, which regenerates all sortable values and rebuilds the search index, may take several hours to complete.

### Updating from Providence version 1.6 or earlier

To update from a version 1.6.x or older installation, you must first update to version 1.7, then follow the 1.7 update instructions.

### Installing development versions

The latest development versions are available on GitHub in branches prefixed with `dev/`. If you are not sure what to run, use a release. If you are looking to work with an in-development feature, you can install a development branch using these steps:

1. Clone this repository into the location where you wish it to run using `git clone https://github.com/collectiveaccess/providence`.
2. By default, the newly cloned repository will use the main branch, which contains code for the current release. Choose the `develop` branch by running from within the cloned repository `git checkout develop`.
3. Install the PHP package manager [Composer](https://getcomposer.org) if you do not already have it installed on your server.
4. Run `composer` from the root of the cloned repository with `composer.phar install`. This will download and install all required 3rd party software libraries. 
5. Follow the release version installation instructions to complete the installation.

### Useful Links

* Web site: https://collectiveaccess.org
* Documentation: https://docs.collectiveaccess.org
* Demo: https://demo.collectiveaccess.org/
* Installation instructions: https://docs.collectiveaccess.org/providence/user/setup/installation.html
* Forum: https://www.collectiveaccess.org/support

To report issues please use GitHub Issues: https://github.com/collectiveaccess/providence/issues 

### Other modules

Pawtucket2: https://github.com/collectiveaccess/pawtucket2 (The public access front-end application for Providence)
