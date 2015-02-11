# TGaz

Temporal Gazetteer Data Architecture for the CHGIS data model

##TGW

###Table definitions 

see .ddl files for MariaDB

###Migration from CHGIS V3

see the Schemas section below for the normalization of V3 to TGAZ

###Webservice

The API is written in .php and contains a few wrapping pages for examples, graphics and basic .css.

## Schemas

### CHGIS3 DDL for MySQL

This is the starting point for this project.

### CHGIS3-norm

A normalized relational schema based on CHGIS3

### CHGIS5-norm

An enhanced version supporting new structural features:

* More generalized and detailed handling of placename metadata:  language, script, transcription system, etc.
* Improved structure of GIS data including ...
* Additional elements to support Linked Data functionality

Includes ...

## Data transformation

* To XML per the CHGIS API
* To JSON per the CHGIS API
* To Pelagios RDF
* To CHGIS RDF

## License

TGaz is licensed under the [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl.html).
