#!/usr/bin/python
# -*- coding: utf-8 -*-

"""
    version 1.0
      - does not use an rdf library such as rdflib
    date: 2015-06-22
    author:  W. Hays

    extract TGaz data for upload to Pelagios in RDF turtle format

    data filters:    CHGIS collection


"""

import mysql.connector
import codecs

def format_rdf(pn, spellings, preslocs):
    rdf =  "<http://chgis.hmdc.harvard.edu/placename/{}> a lawd:Place ;\n".format(pn[0]).encode('UTF-8')

    for (sp) in spellings:
      if sp[1] == "en":
        rdf += "  rdfs:label \"{}\"@en ;\n".format(sp[0]).encode('UTF-8')

    for (sp) in spellings:
#      print("written form: {}".format(sp[3].encode('UTF-8')))
      rdf += "  lawd:hasName [ lawd:primaryForm \"{}\"@{} ] ;\n".format(sp[0].encode('UTF-8'), sp[1])

    if float(pn[1]) != 0.0 and float(pn[2]) != 0.0:
      rdf += "  geo:location [ geo:lat {} ; geo:long {} ] ;\n".format(pn[1], pn[2]).encode('UTF-8')

    for (ploc) in preslocs:
      rdf += "  gn:countryCode \"{}\" ; \n".format(ploc[0]).encode('UTF-8')
      rdf += "  dcterms:description \"{}\" ; \n".format(ploc[1].encode('UTF-8'))

    #back to pn
    rdf += "  dcterms:temporal \"start={}; end={};\" ;\n".format(pn[3], pn[4]).encode('UTF-8')
    rdf += "  dcterms:subject \"{} {}\" ; \n".format(pn[5], pn[6].encode('UTF-8'))

    rdf += ". \n";
    return rdf


def get_placename(cnx, pnid):
    cursor = cnx.cursor()
    cursor.execute("SELECT sys_id, y_coord, x_coord, beg_yr, end_yr, ftype_en, ftype_vn FROM v_placename WHERE id = " + str(pnid))

    pn = cursor.fetchone()

    cursor.execute("SELECT written_form, lang FROM v_spelling WHERE placename_id = " + str(pnid))
    spellings = cursor.fetchall()

    cursor.execute("SELECT country_code, text_value FROM present_loc WHERE placename_id = " + str(pnid) + " AND type = 'location'")
    preslocs = cursor.fetchall()

    cursor.close()

    return format_rdf(pn, spellings, preslocs)


def main():
    print("TGaz dump of CHGIS records to Pelagios RDF")

    cnx = mysql.connector.connect(user='scott', password='tiger', host='127.0.0.1', database='tgw1')
    f = open('tgaz_pelagios_rdf.ttl', 'w')

  #write rdf header
    header = "@prefix cito: <http://purl.org/spar/cito/> .\n"
    header += "@prefix cnt: <http://www.w3.org/2011/content#> .\n"
    header += "@prefix dcterms: <http://purl.org/dc/terms/> .\n"
    header += "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n"
    header += "@prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .\n"
    header += "@prefix gn: <http://www.geonames.org/ontology#> .\n"
    header += "@prefix lawd: <http://lawd.info/ontology/> .\n"
    header += "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n"
    header += "@prefix skos: <http://www.w3.org/2004/02/skos/core#> .\n"
    f.write(header)

    id_cursor = cnx.cursor()  #buffered=True)
    id_query = ("SELECT id as pnid FROM placename WHERE data_src='CHGIS' AND beg_yr != end_yr limit 200")
    id_cursor.execute(id_query)

    pn_ids = id_cursor.fetchall()
    id_cursor.close()

    print("Count of placenames to output:  " + str(len(pn_ids)))

    for (pnid) in pn_ids:
      f.write(get_placename(cnx, pnid[0]))

    cnx.close()
    f.close()

    print("TGaz dump to Pelagios RDF == DONE")

if __name__ == "__main__":
    main()
