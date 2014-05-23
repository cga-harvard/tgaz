<?php

/*
 *   For increased efficiency, consider switching to prepared statements
 *
 */

/*
  placename:
    'id'              'beg_yr'           'geo_src'
    'sys_id'          'beg_rule_id'      'added_on'
    'ftype_id'        'end_yr'           'ftype_vn'
    'data_src'        'end_rule_id'      'ftype_alt'
    'data_src_ref'    'obj_type'         'ftype_tr'
    'snote_id'        'xy_type'          'ftype_en'
    'alt_of_id'       'x_coord'          'snote_ref'
    'lev_rank'        'y_coord'          'snote_text'

      plus values calculated here
    'self_uri'
    'sp_script_form' - written form for preferred script, e.g. simplified Chinese over traditional
    'sp_transcribed_form' - transcribed form for preferred system, e.g. Pinyin over WadeGiles

  spellings:
     'id'              'exonym_lang'     'note'
     'placename_id'    'trsys_id'        'script'
     'script_id'       'attested_by'     'trsys'
     'written_form'    'script_def

  partofs:
     'id'              'begin_year'      'parent_sys_id'
     'child_id'        'end_year'        'parent_vn'
     'parent_id'                         'parent_tr'

  precbys:
     'id'


  preslocs:
     'id'               'type'            'text_value'
     'placename_id'     'country_code'    'source'
                                          'attestation'

*/
function get_placename($conn, $fmt, $sys_id) {

  tlog(E_NOTICE, "Getting placename: " . $sys_id . " in format " . $fmt);


  //FIXME - explicitly list fields,
  //      - use  SQL 'coalesce' to replace possible nulls or do this in PHP

  $pn_query = "SELECT * FROM v_placename WHERE sys_id = ?;";  //'$sys_id'

  if (!$stmt = $conn->prepare($pn_query)) {
      tlog(E_ERROR, "mysqli prepare failure: " . mysqli_error());
      alt_response(500);
  }

  if (!$stmt->bind_param('s', $sys_id)) {                            // 's' means '1 string param'
      tlog(E_ERROR, "mysqli binding sys_id param failed: " . $stmt->errno . " - " . $stmt->error);
      alt_response(500);
  }

  if (!$stmt->execute()) {
     tlog("mysqli statement execute failed: (" . $stmt->errno . " - " . $stmt->error);
     alt_response(500);
     return;
  }

  $pn_result = $stmt->get_result();
  $pn = mysqli_fetch_array($pn_result, MYSQLI_ASSOC);
  mysqli_free_result($pn_result);

  if (count($pn) == 0) {
    tlog(E_NOTICE, "No placename found for id: " . $sys_id);
    alt_response(404, "No placename found for id: " . $sys_id);  //will exit here
  }

  $pn['self_uri'] = 'http://chgis.harvard.edu/placename/' . $pn['sys_id'];

  $spellings = get_deps($conn, "SELECT * FROM v_spelling WHERE placename_id = " . $pn['id'] . ";");

  //calculate preferred written forms  FIXME - use script.default_per_lang
  foreach ($spellings as $sp) {
    if ($sp['script_id'] != 0) {                      // has script
      if (!isset($pn['sp_script_form']) || ($sp['script_def'] == 1)) {   // assign if not assigned or use preferred
        $pn['sp_script_form']  = $sp['written_form'];
      }
    } elseif ($sp['trsys_id'] != 'na') {              // is transcription
      $pn['sp_transcribed_form'] = $sp['written_form'];
    }
  }

  $partofs = get_deps($conn, "SELECT * FROM v_partof WHERE child_id = " . $pn['id'] . " ORDER BY begin_year;");
  $precbys = get_deps($conn, "SELECT * FROM v_precby WHERE placename_id = " . $pn['id'] . ";");
  $preslocs = get_deps($conn, "SELECT * FROM present_loc WHERE placename_id = " . $pn['id'] . " AND type = 'location';");
// FIXME extract one preferred location

   // BETTER: use these methods in the to_xx call's parameter list to avoid unneeded work

  switch($fmt) {
    case 'json':
      to_json($pn, $spellings, $partofs, $precbys, $preslocs); break;
    case 'geojson':
      to_geojson($pn, $spellings); break;
//    case 'html5':
//      to_html5($pn, $spellings); break;
    case 'xml':
      to_xml($pn, $spellings, $partofs); break;
    case 'rdf':
      to_pelagios_rdf($pn, $spellings); break;
    default:
      tlog(E_WARNING, "Invalid fmt type: " . $fmt);
  }
}


function get_deps($conn, $query) {

  $deps = array();  //indexed

  if ($result = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $deps[] = $row;
    }

    mysqli_free_result($result);
  }  // else error

  return $deps;
}

function to_json($pn, $spellings, $partofs, $precbys, $preslocs) {

    $sp_json = array();  //indexed
    foreach ($spellings as $sp) {
        if ($sp['script_id'] != 0) {                      // has script
            $sp_json[] = array(
                $sp['script']     =>  $sp['written_form'],
                'exonym language' =>  $sp['exonym_lang'],    // FIXME test for null to exclude
                'attested by'     =>  $sp['attested_by'],
                'note'            =>  $sp['note']
            );
        } elseif ($sp['trsys_id'] != 'na') {              // is transcription
            $sp_json[] = array(
                'transcribed in ' . $sp['trsys']   =>  $sp['written_form'],
                'attested by'             =>  $sp['attested_by'],
                'note'                    =>  $sp['note']
            );
        }
    }

    $po_json = array();  //indexed
    foreach ($partofs as $po) {
        $po_json[] = array(
          'years'                 => $po['begin_year'] . " - " . $po['end_year'],
          'parent id'             => $po['parent_sys_id'],
          'name'                  => $po['parent_vn'],
          'transcribed'           => $po['parent_tr']
        );
    }

    $pb_json = array();  //indexed
    foreach ($precbys as $pb) {
        $pb_json[] = array(
          'preceded by id'             => $pb['pb_sys_id'],
          'name'                  => $pb['pb_vn'],
          'transcribed'           => $pb['pb_tr']
        );
    }

    $ploc_json = array();  //indexed
    foreach ($preslocs as $ploc) {
        $ploc_json[] = array(
           'country code'         => $ploc['country_code'],
           'text'                 => $ploc['text_value'],
           'source'               => $ploc['source'],
           'attestation'          => $ploc['attestation']
        );
    }

    $pn_json = array(
      'system'              => 'China Historical GIS, Harvard University and Fudan University',
      'license'             => 'c. 2014',
      'uri'                 => 'http://chgis.harvard.edu/placename/' . $pn['sys_id'],
      'sys_id'              => $pn['sys_id'],
      'sys_id of alternate' => $pn['alt_of_id'],

      'spellings'           => $sp_json,

      'feature_type' => array(
        'name'            => $pn['ftype_vn'],
        'alternate name'  => $pn['ftype_alt'],
        'transcription'   => $pn['ftype_tr'],
        'English'         => $pn['ftype_en']
      ),

      'temporal' => array(
        'years' => $pn['beg_yr'] . " - " . $pn['end_yr'],
        'begin/end_rules' => $pn['beg_rule_id'] . " - " . $pn['end_rule_id']
      ),

      'spatial' => array(
        'object_type' => $pn['obj_type'],
        'xy_type'     => $pn['xy_type'],
        'latitude'    => $pn['y_coord'],
        'longitude'   => $pn['x_coord'],
        'source'      => $pn['geo_src'],
        'present_location' => $ploc_json
        //present_jurisdiction
      ),

      'historical context' => array(
         'part of'      => $po_json,
         'preceded by'  => $pb_json
      ),


      'data source'   => $pn['data_src'],
      'source note'   => $pn['snote_text']
  );

  header('Content-Type: text/json; charset=utf-8');
  echo json_encode($pn_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
}

// file format the same for one or multiples - array of one or many
//
function to_geojson($pn, $spellings) {

  $geo_types = array( 'POINT' => 'Point', 'POLYGON' => 'Polygon', 'LINE' => 'LineString');

  $pn_json = array(
      'type'                => 'FeatureCollection',
      'features'            =>  array(               // indexed, container for multiples
                                array(
        'type'              => 'Feature',
        'geometry'          => array(
          'type'            =>  $geo_types[$pn['obj_type']],             // FIXME - or xy_type?
          'coordinates'     =>  array ($pn['y_coord'], $pn['x_coord']), // FIXME - polygon or line data ?
         ),
         'properties'       => array(
           'uri'            => 'http://chgis.harvard.edu/placename/' . $pn['sys_id'],
           'sys_id'         => $pn['sys_id'],

           //   'spellings'
           'script name'      =>  $pn['sp_script_form'],
           'transcribed name' => $pn['sp_transcribed_form'],

           'feature type'     => $pn['ftype_en'],
           'years'            => $pn['beg_yr'] . ' - ' . $pn['end_yr']
         )
      ))
  );

  header('Content-Type: text/json; charset=utf-8');
  echo json_encode($pn_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
}

function to_xml($pn, $spellings, $partofs, $preslocs) {
  header('Content-Type: text/xml; charset=utf-8');
  echo  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
  // DOCTYPE published ??

  echo '<placename id="' . $pn['id'] . '">';
  echo '  <system>China Historical GIS, Harvard University and Fudan University</system>';
  echo '  <license>c. 2014</license>';

  echo '  <uri>http://chgis.harvard.edu/placename/' . $pn['sys_id'] . '</uri>';

  echo ' <spellings>';
  foreach($spellings as $sp) {
    echo '    <spelling>';
    if ($sp['script_id'] != 0) {                      // has script
      echo '      <written-form script="' . $sp['script'] . '">' . $sp['written_form'] . '</written-form>';
    } elseif ($sp['trsys_id'] != 'na') {              // is transcription
      echo '      <transcription system="' . $sp['trsys'] . '">'  .$sp['written_form'] . '</transcription>';
    }
      echo '      <exonym-lang>' . $sp['exonym_lang']  . '</exonym-lang>';
      echo '      <attested-by>' . $sp['attested_by'] . '</attested-by>';
      echo '      <note>' . $sp['note'] . '</note>';

    echo '    </spelling>';
  }
  echo '  </spellings>';

  echo '  <feature-type>';
  echo '    <name>' . $pn['ftype_vn'] . '</name>';
  echo '    <alternate-name>' . $pn['ftype_alt'] . '</alternate-name>';
  echo '    <transcription>' . $pn['ftype_tr'] . '</transcription>';
  echo '    <translation lang="en">' . $pn['ftype_en'] . '</translation>';
  echo '  </feature-type>';

  echo '  <temporal>';
  echo '    <years begin-rule="' . $pn['beg_rule_id'] . '" end-rule="' . $pn['end_rule_id'] . '">' . $pn['beg_yr'] . ' - ' . $pn['end_yr'] . '</years>';
  echo '  </temporal>';

  echo '  <spatial>';
  echo '    <object-type>' . $pn['obj_type'] . '</object-type>';
  echo '    <coordinate-type>' . $pn['xy_type'] . '</coordinate-type>';
  echo '    <latitude-direction>N</latitude-direction>';                     //FIXME - calc N / S
  echo '    <degrees-latitude>' . $pn['y_coord'] . '</degrees-latitude>';
  echo '    <longitude-direction>E</longitude-direction>';                   //FIXME - calc E / W
  echo '    <degrees-longitude>' . $pn['x_coord'] . '</degrees-longitude>';
  echo '    <geo-source>' . $pn['geo_src'] . '</geo-source>';

  echo '    <present-location>';
//  foreach($preslocs as $ploc) {

//  }
  echo '    </present-location>';

  echo '  </spatial>';

  echo '  <historical-context>';
  echo '    <part-of-relationships>';
    foreach ($partofs as $po) {
      echo '      <part-of parent-id="' . $po['parent_sys_id'] . '" from="' . $po['begin_year'] . '" to="' . $po['end_year'] . '" >';
      echo '        <parent-name>' . $po['parent_vn'] . '</parent-name>';
      echo '        <transcribed-name>' . $po['parent_tr'] . '</transcribed-name>';
      echo '      </part-of>';
    }
  echo '    </part-of-relationships>';
  echo '  </historical-context>';

  echo '  <data-source>' . $pn['data_src'] . '</data-source>';

  echo '  <source-note>';
  echo '    <![CDATA[';
  echo '      ' . $pn['snote_text'];
  echo '    ]]>';
  echo '  </source-note>';

  echo '</placename>';

}

// refactor to accept array of pn since the format is the same
function to_pelagios_rdf($pn, $spellings) {
  header('Content-Type: text/turtle; charset=utf-8');

  echo "@prefix dcterms: <http://purl.org/dc/terms/> .\n";
  echo "@prefix osgeo: <http://data.ordnancesurvey.co.uk/ontology/geometry/> .\n";
  echo "@prefix pelagios: <http://pelagios.github.io/vocab/terms#> .\n";
  echo "@prefix pleiades: <http://pleiades.stoa.org/places/vocab#> .\n";
  echo "@prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .\n";
  echo "@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .\n";
  echo "@prefix skos: <http://www.w3.org/2004/02/skos/core#> .\n";
  echo "@prefix spatial: <http://geovocab.org/spatial#> .\n";
  echo "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n";

// foreach in $pnarray

  echo "<" . $pn['self_uri'] . "> a pelagios:PlaceRecord ;\n";
    echo "  dcterms:title \"" . $pn['sp_transcribed_form'] . "\" ;\n";                              //FIXME case where transcribed form is missing
    echo "  dcterms:description \"" . $pn['ftype_en'] . " in the jurisdiction of " . "\" ;\n";      // ftype + parent?? which one and what if none?
    echo "  dcterms:subject <http://????> ;\n";                                                     //  FIXME - uri for feature type; also chgis?
    echo "  skos:closeMatch <http://sws.geonames.org/" . "????" . "/> ;\n";                           // FIXME ?? in links?

    foreach($spellings as $sp) {
      echo "  pleiades:hasName [ rdfs:label \"" . $sp['written_form'] . "\" ] ;\n";
    }

    echo "  pleiades:hasLocation [ geo:lat \"" . $pn['y_coord'] . "\"^^xsd:double ; geo:long \"" . $pn['x_coord'] . "\"^^xsd:double ] ;\n";
    // FIXME 'spatial:P' is now missing from online version ?? ask Rainer

    //multiples here, no container; iterate through list of pn's
}

//function to_html5($pn, $spellings, $partofs) {

?>