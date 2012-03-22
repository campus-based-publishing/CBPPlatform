# INTRODUCTION

Campus-based Publishing Platform (CBP Platform) is a Web interface for authors and editors to create, manage and disseminate multi-format output. A key feature of the platform is the ability to create differing communities of editors and authors to drive the various imprints of the CBP Platform, with a core aim being to encourage the creative output of all contributors.

Detailed information about the platform is available at: http://www.jisc.ac.uk/whatwedo/programmes/inf11/inf11scholcomm/larkinpress.aspx

CBP Platform is built from a selection of open source elements, the primary codebase being that of Open Journal Systems (http://pkp.sfu.ca/ojs/).

# REQUIREMENTS

## Server Requirements

Requirements 1-3 adapted from (http://pkp.sfu.ca/ojs_download)

1.	PHP 4.2.x or later (including PHP 5.x) with MySQL support
2.	A database server: MySQL 3.23 or later
3.	UNIX-like OS recommended (such as Linux, FreeBSD, Solaris, Mac OS X, etc.).
4.	A Fedora Commons Repository with API-M access

## PHP Modules

The following modules are likely to be standard on most PHP 5.x installers, however:

1.	cURL 
2.	Zip 
3.	Tidy 
4.	LibXML & XSL 

# INSTALLATION

As CBP Platform is based on the Public Knowledge Project’s Open Journal Systems (OJS) platform (http://pkp.sfu.ca/ojs/) (version 2.3.6, June 30, 2011), the recommended process for installing CBP Platform is to follow and complete the OJS installation process (http://pkp.sfu.ca/ojs_download). This will ensure the appropriate file structure, permissions and database connectivity. 

Then, download the CBP Platform, and update the OJS installation by merging the CBP Platform source with the OJS source (overwriting/updating where appropriate) (a simple drag-and-drop should fulfil this in most cases).

A MySQL dump of the required CBP Platform database/table structure is at: /docs/cbpplatform-mysqldump-111222-1517.dmp (`--no-data` `--skip-add-drop-table`)

# CONFIGURATION

CBP Platform requires additional configuration options (on top of standard OJS configuration options) to be set in the config.inc.php file (see config.inc.template.php for examples):

## [general]
* `repository_url`
* `repository_username`
* `repository_password`
* `repository_short_url`
* `hydra_short_url`
* `proxy_tunnel`
* `component_namespace`
* `compiled_namespace`

## [printod]
* `sheet_thickness`
* `bleed`

## Note

A Fedora Commons Repository install is not compulsory; alternatively, the source (primarily file management and handler classes) can be modified to reference local files (however the platform was developed with Fedora Commons Repository integration as a primary functional requirement).

# TECHNICAL DOCUMENTATION

Full technical documentation is available in: /docs (Doxygen generated)

CBP Platform specific additions to the OJS platform are commented with the identifier %CBP%

However, the most pertinent additions are found in /classes/CBPPlatform/
The contents of this directory can also be repurposed for other use cases (specifically the eBook conversion and repository integration classes)

# ACKNOWLEDGEMENTS

CBP Platform utilises the following libraries, classes and other open-source elements:

* Open Journal Systems and its dependencies (http://pkp.sfu.ca/ojs/)
* Fedora Commons Repository (http://fedora-commons.org/) 
* Pest (http://github.com/educoder/pest)
* PHP CSS Parser (https://github.com/sabberworm/PHP-CSS-Parser)
* EPub PHP class (http://www.phpclasses.org/package/6115)
* phpMobi (https://github.com/raiju/phpMobi)
* mPDF (http://www.mpdf1.com/mpdf/)
* PHPDOCX (http://www.phpdocx.com/)
* PHPThumb (https://github.com/masterexploder/PHPThumb)
* Twitter Bootstrap (http://twitter.github.com/bootstrap/)