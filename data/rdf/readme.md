## CHGIS Gazetteer RDF listing

#### generated:  2016-07-06
#### records:  71647
### Extracted from TGAZ  [http://maps.cga.harvard.edu/tgaz/] (http://maps.cga.harvard.edu/tgaz/)

### Schema
Based on Pelagios Gazetteer Interconnection Format
https://github.com/pelagios/pelagios-cookbook/wiki/Pelagios-Gazetteer-Interconnection-Format

### Purpose
Note:  this RDF is generated for automated ingest into Pelagios or any system that can read the schema

### Element Description

* @prefix  abbreviations for reference ontologies,  for example gn prefix = geonames term, such as:  gn:countryCode  defined at http://www.geonames.org/ontology#
* 
* rdfs:label  THE DEFAULT PLACENAME for this record (always in Romanized form)
* lawd:hasName [multiple allowed] THE SPECIFIC PLACENAME followed by language and script definition, such as @zh-Hant  (Chinese Traditional Chinese Script) see ISO 639-3
* geo:location  defines geographic coordinates
* gn:countryCode
* dcterms:coverage   Description of location in free text
* dcterms:temporal   provides a start and end year (non-standard) as free text
* dcterms:description  provides a geographic feature classification as free text
* dcterms:isPartOf  provides the URI of a container entity (such as admistrative unit)

#### License

TGaz is licensed under the [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl.html).
