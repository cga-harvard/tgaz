<?php

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
                                         'snote_uri'

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
     'id'              'pb_sys_id'       'pb_vn'
     '        '                          'pb_tr'



  preslocs:
     'id'               'type'            'text_value'
     'placename_id'     'country_code'    'source'
                                          'attestation'

  subunits:  [short for 'subordinate units' a.k.a. children]
     'id' [part_of.id]  'begin_year'      'parent_sys_id'
     'child_id'         'end_year'        'parent_vn'
     'parent_id'                         'parent_tr'
     'child_sys_id'    'child_vn'        'child_tr'

*/
function get_placename($conn, $fmt, $sys_id) {

  tlog(E_NOTICE, "Getting placename: " . $sys_id . " in format " . $fmt);


  //FIXME - explicitly list fields,
  //      - use  SQL 'coalesce' to replace possible nulls or do this in PHP

//$stmt = $mysqli->stmt_init();

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

// get_result doesn't work on HMDC setup due to omitted mysqlnd ("native driver")
// work around is to use bind + fetch
/*
  $pn_result = $stmt->get_result();
  $pn = mysqli_fetch_array($pn_result, MYSQLI_ASSOC);
*/

  $md = $stmt->result_metadata();
  $params = array();
  $pn = array();

  while($field = $md->fetch_field()) {
      $params[] = &$pn[$field->name];
  }

  call_user_func_array(array($stmt, 'bind_result'), $params);
  $stmt->fetch();

  //mysqli_free_result($pn_result);
  $stmt->close();

  if ($pn['sys_id'] == null) {
    tlog(E_NOTICE, "No placename found for id: " . $sys_id);
    alt_response(404, "CHGIS:  No placename found for id: " . $sys_id);  //will exit here
    return;
  }

  $pn['self_uri'] =  BASE_URL . '/placename/' . $pn['sys_id'];

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
  $subunits = get_deps($conn, "SELECT * FROM v_children WHERE parent_id = " . $pn['id'] . " ORDER BY child_vn, begin_year;");
// FIXME extract one preferred location


   // BETTER: use these methods in the to_xx call's parameter list to avoid unneeded work

  switch($fmt) {
    case 'json':
      to_json($pn, $spellings, $partofs, $precbys, $preslocs, $subunits); break;
    case 'geojson':
      to_geojson($pn, $spellings); break;
    case 'html':
      to_html($pn, $spellings, $partofs, $precbys, $preslocs, $subunits); break;
    case 'xml':
      to_xml($pn, $spellings, $partofs, $precbys, $preslocs, $subunits); break;
    case 'rdf':
      to_pelagios_rdf($pn, $spellings, $partofs, $preslocs); break;
    case 'esgar':
      to_esgar($pn, $spellings, $partofs, $precbys, $preslocs, $subunits); break;
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

/*
 *  json_encode for pre php 5.4 is missing flags to deliver unescaped unicode, escape slashes and pretty print
 *  workarounds with json_encode and related versions (PEAR) fail to render Chinese characters correctly
 *  so this function emits json the old-fashioned way which also solves the problem with "null" output
 */
function to_json($pn, $spellings, $partofs, $precbys, $preslocs, $subunits) {

    $jt = "{\n";
    $indent = '  ';
    $d = 0;          //depth of indent

    $sp_json = array();  //indexed
    foreach ($spellings as $sp) {
        if ($sp['script_id'] != 0) {                      // has script
            $sp_json[] = array(
                'written form'  =>  $sp['written_form'],
                'script' =>  $sp['script'],    // split from written form
                'exonym language' =>  $sp['exonym_lang'],    // FIXME test for null to exclude
                'attested by'     =>  $sp['attested_by'],
                'note'            =>  $sp['note']
            );
        } elseif ($sp['trsys_id'] != 'na') {              // is transcription
            $sp_json[] = array(
                'written form' =>  $sp['written_form'],
                'transcribed in' => $sp['trsys'],
                'attested by'             =>  $sp['attested_by'],
                'note'                    =>  $sp['note']
            );
        }
    }

    $po_json = array();  //indexed
    foreach ($partofs as $po) {
        $po_json[] = array(
          'begin year'                 => $po['begin_year'],
          'end year'                 => $po['end_year'],
          'parent id'             => $po['parent_sys_id'],
          'name'                  => $po['parent_vn'],
          'transcribed'           => $po['parent_tr']
        );
    }

    $su_json = array();
    foreach ($subunits as $su) {
        $su_json[] = array(
          'begin_year'            => $su['begin_year'],
          'end_year'              => $su['end_year'],
          'child id'              => $su['child_sys_id'],
          'name'                  => $su['child_vn'],
          'transcribed'           => $su['child_tr']
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

    $jt .= jline('system', 'China Historical GIS, Harvard University and Fudan University', 0)
        .  jline('license', LIC, 0)
        .  jline('uri', BASE_URL . '/placename/' . $pn['sys_id'], 0)
        .  jline('sys_id', $pn['sys_id'], 0)
        .  jline('sys_id of alternate', $pn['alt_of_id'], 0)

        .  jarray('spellings', $sp_json, 0)

        .  $indent . "\"feature_type\" : {\n"
        .  jline('name', $pn['ftype_vn'], 1)
        .  jline('alternate name', $pn['ftype_alt'], 1)
        .  jline('transcription', $pn['ftype_tr'], 1)
        .  jline('English', $pn['ftype_en'], 1, true)
        .  $indent . "},\n"

        .  $indent . "\"temporal\" : {\n"
        .  jline('begin', $pn['beg_yr'], 1)
        .  jline('begin rule', $pn['beg_rule_id'], 1)
        .  jline('end', $pn['end_yr'], 1)
        .  jline('end rule', $pn['end_rule_id'], 1, true)
        .  $indent . "},\n"

        .  $indent . "\"spatial\" : {\n"
        .  jline('object_type',      $pn['obj_type'], 1)
        .  jline('xy_type',          $pn['xy_type'], 1)
        .  jline('latitude',         $pn['y_coord'], 1)
        .  jline('longitude',        $pn['x_coord'], 1)
        .  jline('source',           $pn['geo_src'], 1)
        .  jarray('present_location', $ploc_json, 1, true)
        //present_jurisdiction
        .  $indent . "},\n"

        .  $indent . "\"historical_context\" : {\n"
        .  jarray('part of',         $po_json, 1)
        .  jarray('subordinate units',  $su_json, 1)
        .  jarray('preceded by',     $pb_json, 1, true)
        .  $indent . "},\n"


        .  jline('data source',     $pn['data_src'], 0)
        .  jline('source note',     $pn['snote_text'], 0)
        .  jline('source uri',     $pn['snote_uri'], 0, true)
        . "}";

  header('Content-Type: text/json; charset=utf-8');
  echo $jt;
}

//json line for single key/value
function jline($n, $v, $d, $last = false) {
  $s =  str_repeat('  ', $d) . "  \"$n\" : \"$v\"";
  if ($last) {
    $s .= "\n";
  } else {
    $s .= ",\n";
  }
  return $s;
}

// json: for array of associative arrays
function jarray($n, $a, $d, $last = false) {
  $s = str_repeat('  ', $d) . "  \"$n\" : [\n";
  $posa = 0;

  foreach ($a as $item) {
    $s .= str_repeat('  ', $d + 2) . "{\n";
    $posi = 0;
    foreach ($item as $key => $val) {
      $s .= str_repeat('  ', $d + 3) . "\"$key\" : \"$val\"";
      if (++$posi < count($item)) {
        $s .=  ",\n";
      } else {
        $s .= "\n";
      }
    }
    $s .= str_repeat('  ', $d + 2) . "}";
    if (++$posa < count($a)) {
        $s .=  ",\n";
    } else {
        $s .= "\n";
    }
  }

  $s .= str_repeat('  ', $d + 1) . "]";
  if ($last) {
    $s .= "\n";
  } else {
    $s .= ",\n";
  }

  return $s;
}

/*  add IDs for elements, added underscores for element names
 */
function to_esgar($pn, $spellings, $partofs, $precbys, $preslocs, $subunits) {

    $jt = "{\n";
    $indent = '  ';
    $d = 0;          //depth of indent

    $sp_json = array();  //indexed
    foreach ($spellings as $sp) {
        if ($sp['script_id'] != 0) {                      // has script
            $sp_json[] = array(
                'id'            =>  $sp['id'],
                'written_form'  =>  $sp['written_form'],
                'script' =>  $sp['script'],    // split from written form
                'exonym_language' =>  $sp['exonym_lang'],    // FIXME test for null to exclude
                'attested_by'     =>  $sp['attested_by'],
                'note'            =>  $sp['note']
            );
        } elseif ($sp['trsys_id'] != 'na') {              // is transcription
            $sp_json[] = array(
                'id'            =>  $sp['id'],
                'written_form' =>  $sp['written_form'],
                'transcribed_in' => $sp['trsys'],
                'attested_by'             =>  $sp['attested_by'],
                'note'                    =>  $sp['note']
            );
        }
    }

    $po_json = array();  //indexed
    foreach ($partofs as $po) {
        $po_json[] = array(
          'id'                    => $po['id'],
          'begin_year'            => $po['begin_year'],
          'end_year'              => $po['end_year'],
          'parent_id'             => $po['parent_sys_id'],
          'name'                  => $po['parent_vn'],
          'transcribed'           => $po['parent_tr']
        );
    }

    $su_json = array();
    foreach ($subunits as $su) {
        $su_json[] = array(
          'id'                    => $su['id'],
          'begin_year'            => $su['begin_year'],
          'end_year'              => $su['end_year'],
          'child_id'              => $su['child_sys_id'],
          'name'                  => $su['child_vn'],
          'transcribed'           => $su['child_tr']
        );
    }

    $pb_json = array();  //indexed
    foreach ($precbys as $pb) {
        $pb_json[] = array(
          'id'                    => $pb['id'],
          'preceded_by_id'        => $pb['pb_sys_id'],
          'name'                  => $pb['pb_vn'],
          'transcribed'           => $pb['pb_tr']
        );
    }

    $ploc_json = array();  //indexed
    foreach ($preslocs as $ploc) {
        $ploc_json[] = array(
           'id'         => $ploc['id'],
           'country_code'         => $ploc['country_code'],
           'text'                 => $ploc['text_value'],
           'source'               => $ploc['source'],
           'attestation'          => $ploc['attestation']
        );
    }

    $jt .= jline('system', 'China Historical GIS, Harvard University and Fudan University', 0)
        .  jline('license', LIC, 0)
        .  jline('uri', BASE_URL . '/placename/' . $pn['sys_id'], 0)
        .  jline('sys_id', $pn['sys_id'], 0)
        .  jline('sys_id_of_alternate', $pn['alt_of_id'], 0)

        .  jarray('spellings', $sp_json, 0)

        .  $indent . "\"feature_type\" : {\n"
        .  jline('id', $pn['ftype_id'], 1)
        .  jline('name', $pn['ftype_vn'], 1)
        .  jline('alternate_name', $pn['ftype_alt'], 1)
        .  jline('transcription', $pn['ftype_tr'], 1)
        .  jline('English', $pn['ftype_en'], 1, true)
        .  $indent . "},\n"

        .  $indent . "\"temporal\" : {\n"
        .  jline('begin', $pn['beg_yr'], 1)
        .  jline('begin_rule', $pn['beg_rule_id'], 1)
        .  jline('end', $pn['end_yr'], 1)
        .  jline('end_rule', $pn['end_rule_id'], 1, true)
        .  $indent . "},\n"

        .  $indent . "\"spatial\" : {\n"
        .  jline('object_type',      $pn['obj_type'], 1)
        .  jline('xy_type',          $pn['xy_type'], 1)
        .  jline('latitude',         $pn['y_coord'], 1)
        .  jline('longitude',        $pn['x_coord'], 1)
        .  jline('source',           $pn['geo_src'], 1)
        .  jarray('present_location', $ploc_json, 1, true)
        //present_jurisdiction
        .  $indent . "},\n"

        .  $indent . "\"historical_context\" : {\n"
        .  jarray('part_of',         $po_json, 1)
        .  jarray('subordinate_units',  $su_json, 1)
        .  jarray('preceded_by',     $pb_json, 1, true)
        .  $indent . "},\n"

        .  jline('data_source',     $pn['data_src'], 0)
        .  jline('source_note',     $pn['snote_text'], 0)
        .  jline('source_note_id',  $pn['snote_ref'], 0)
        .  jline('source_uri',      $pn['snote_uri'], 0, true)
        . "}";

  header('Content-Type: text/json; charset=utf-8');
  echo $jt;
}

// file format the same for one or multiples - array of one or many
//
function to_geojson($pn, $spellings) {

    $geo_types = array( 'POINT' => 'Point', 'POLYGON' => 'Polygon', 'LINE' => 'LineString');

    $jt = "{\n";
    $indent = '  ';
    $d = 0;          //depth of tree

    $jt .= jline('type', 'FeatureCollection', 0)
        .  str_repeat($indent, 1) . "\"features\" : [\n"
        .  str_repeat($indent, 2) . "{\n"
        .  jline('type', 'Feature', 2)
        .  str_repeat($indent, 3) . "\"geometry\" : {\n"
        .  jline('type',  $geo_types[$pn['obj_type']], 3)                               // FIXME - or xy_type?
        .  jline('coordinates', "{ " . $pn['y_coord'] . ", " . $pn['x_coord'] . "}", 3) // FIXME - polygon or line data ?
        .  str_repeat($indent, 3) . "}\n"

        .  str_repeat($indent, 3) . "\"properties\" : {\n"

        .  jline('uri', BASE_URL  . '/placename/' . $pn['sys_id'], 3)
        .  jline('sys_id', $pn['sys_id'], 3)

           //   'spellings'
        .  jline('script name', $pn['sp_script_form'], 3)
        .  jline('transcribed name', $pn['sp_transcribed_form'], 3)

        .  jline('feature type',  $pn['ftype_en'], 3)
        .  jline('years',         $pn['beg_yr'] . ' - ' . $pn['end_yr'], 3)
        .  str_repeat($indent, 3) . "}\n"  //end properties

        .  str_repeat($indent, 2) . "}\n"  //end feature
        .  str_repeat($indent, 1) . "]\n"  //end features
        . "}";

  header('Content-Type: text/json; charset=utf-8');
//  echo json_encode($pn_json); //, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );


  echo $jt;

}

function to_xml($pn, $spellings, $partofs, $precbys, $preslocs, $subunits) {
  header('Content-Type: text/xml; charset=utf-8');
  echo  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
  // DOCTYPE published ??
  // removed ref to placename id in the header
  echo '<placename>';
  echo '  <system>China Historical GIS, Harvard University and Fudan University</system>';
  echo '  <license>' . LIC . '</license>';

  echo '  <uri>' . BASE_URL . '/placename/' . $pn['sys_id'] . '</uri>';

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
  echo '    <begin>' . $pn['beg_yr'] . '</begin>';
  echo '    <begin-rule>' . $pn['beg_rule_id'] . '</begin-rule>';
  echo '    <end>' . $pn['end_yr'] . '</end>';
  echo '    <end-rule>' . $pn['end_rule_id'] . '</end-rule>';
  echo '  </temporal>';

  echo '  <spatial>';
  echo '    <object-type>' . $pn['obj_type'] . '</object-type>';
  echo '    <xy-type>' . $pn['xy_type'] . '</xy-type>';
  echo '    <latitude-direction>N</latitude-direction>';                     //FIXME - calc N / S
  echo '    <degrees-latitude>' . $pn['y_coord'] . '</degrees-latitude>';
  echo '    <longitude-direction>E</longitude-direction>';                   //FIXME - calc E / W
  echo '    <degrees-longitude>' . $pn['x_coord'] . '</degrees-longitude>';
  echo '    <geo-source>' . $pn['geo_src'] . '</geo-source>';

  echo '    <present-location>';
    foreach($preslocs as $ploc) {
      echo ' ' . $ploc['text_value'];
    }
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

  echo '    <subordinate-units>';
    foreach ($subunits as $su) {
      echo '      <subordinate-unit sys-id="' . $su['child_sys_id'] . '" from="' . $su['begin_year'] . '" to="' . $su['end_year'] . '" >';
      echo '        <name>' . $su['child_vn'] . '</name>';
      echo '        <transcribed-name>' . $su['child_tr'] . '</transcribed-name>';
      echo '      </subordinate-unit>';
    }
  echo '    </subordinate-units>';

  echo '  </historical-context>';

  echo '  <data-source>' . $pn['data_src'] . '</data-source>';

  echo '  <source-note>';
  echo '    <![CDATA[';
  echo '      ' . $pn['snote_text'];
  echo '    ]]>';
  echo '  </source-note>';

  echo '  <source-uri>';
  echo '    ' . $pn['snote_uri'];
  echo '  </source-uri>';

  echo '</placename>';

}

// refactor to accept array of pn since the format is the same

function to_pelagios_rdf($pn, $spellings, $partofs, $preslocs) {header('Content-Type: application/rdf+xml; charset=utf-8');


  echo "@prefix cito: <http://purl.org/spar/cito/> .\n";
  echo "@prefix cnt: <http://www.w3.org/2011/content#> .\n";
  echo "@prefix dcterms: <http://purl.org/dc/terms/> .\n";
  echo "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n";
  echo "@prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .\n";
  echo "@prefix gn: <http://www.geonames.org/ontology#> .\n";
  echo "@prefix lawd: <http://lawd.info/ontology/> .\n";
  echo "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n";
  echo "@prefix skos: <http://www.w3.org/2004/02/skos/core#> .\n";

// foreach in $pnarray

    echo "<" . BASE_URL . "/placename/" . $pn['sys_id'] . ">  a lawd:Place ;\n";
    echo "  rdfs:label \"" . $pn['sp_transcribed_form'] . "\" ;\n";   //FIXME if transcribed form missing

//    echo "  skos:closeMatch <http://sws.geonames.org/" . "????" . "/> ;\n";
//  names with lang declaration after @
    foreach($spellings as $sp) {
      echo "  lawd:hasName [ rdfs:label \"" . $sp['written_form'] . "\"@" . $sp['lang']  . " ] ;\n";
    }

    echo "  geo:location [ geo:lat " . $pn['y_coord'] . "; geo:long " . $pn['x_coord'] . " ] ;\n";


    //  items from $preslocs query
    foreach ($preslocs as $ploc) {
    echo "  gn:countryCode \"" . $ploc['country_code'] . "\" ; \n";
    echo "  dcterms:coverage \"" . $ploc['text_value'] . "\" ; \n";
    }

    echo "  dcterms:temporal \"start=" . $pn['beg_yr']  . "; end=" . $pn['end_yr']  . "\" ;\n";
    echo "  dcterms:description \"" . $pn['ftype_en'] . " " . $pn['ftype_vn']  .  "\" ;\n";

    //  items from $partofs query - with URI only for valid RDF
       foreach ($partofs as $po) {
    echo "  dcterms:isPartOf <" . BASE_URL . "/placename/" . $po['parent_sys_id'] . "> ;\n";
      }

    echo ".";

//  original with description after URI
/*
       foreach ($partofs as $po) {
    echo "  dcterms:isPartOf \"<http://chgis.hmdc.harvard.edu/placename/" . $po['parent_sys_id'] . ">";
         echo " part of " . $po['parent_tr'] . " "  . $po['parent_vn'] . " from "  . $po['begin_year'] . " to " . $po['end_year'] . " \" ;\n";
      }

    echo ".";

*/


    //multiples here, no container; iterate through list of pn's
}

//function to_html5($pn, $spellings, $partofs) {

function to_html($pn, $spellings, $partofs, $precbys, $preslocs, $subunits) {
  header('Content-Type:text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html>
   <head>
    <link rel="stylesheet" type="text/css"  href="/tgaz/css/api.css">
    <link rel="stylesheet" type="text/css"  href="/tgaz/css/btn.css">
   </head>

   <script type="text/javascript" src="' . BASE_URL . '/lib/jquery.min.js"></script>
   <script type="text/javascript">
    function toggleDiv(divId) {
     $("#"+divId).toggle();
    }
   </script> 

  <body>';
  echo '<div class="wrap">';
  echo '<div class="banner"><a href="/tgaz/"><img src="' . BASE_URL . '/graf/TGAZ_API_icon.png" alt="Temporal Gazetteer API"></a>';
  echo '  <div class="uri"> URI :: <a href="' . BASE_URL . '/placename/' . $pn['sys_id'] . '"> ' . BASE_URL . '/placename/' . $pn['sys_id'] . '</a></div>';
  echo '</div>';

  echo '<div class="btn-group">
    <a class="btn btn-mini" href="' . BASE_URL . '/placename/json/' . $pn['sys_id'] . '">JSON</a>
    <a class="btn btn-mini" href="' . BASE_URL . '/placename/xml/' . $pn['sys_id'] . '">XML</a>
    <a class="btn btn-mini" href="' . BASE_URL . '/placename/rdf/' . $pn['sys_id'] . '" title="save link as">RDF</a>
  </div></div>';
  echo '<div class="name">placename: <placename> <p \> ';

  echo ' <spellings>';
  foreach($spellings as $sp) {
    echo '    <div class ="spelling">';
    if ($sp['script_id'] != 0) {                      // has script
      echo '    <written-form script="' . $sp['script'] . '"><b>' . $sp['written_form'] . ' </b> (' . $sp['script'] . ')</written-form>  ';
    } elseif ($sp['trsys_id'] != 'na') {              // is transcription
      echo '    <transcription system="' . $sp['trsys'] . '">  '  .$sp['written_form'] . ' (' . $sp['trsys'] . ')</transcription>';

    }
      echo '    <exonym-lang>' . $sp['exonym_lang']  . '</exonym-lang>';
    if ($sp['attested_by'] !=''){
      echo '      <attested-by>  <font size=-1>attested by: ' . $sp['attested_by'] . '</font></attested-by>';
    }

      echo '    <note>' . $sp['note'] . '</note>';
    echo '    </div><p />';
  }
  echo '  </spellings>';
  echo '</div>';

  echo '<div class="type">type: ';
  echo '  <feature-type>';
  echo '    <name>' . $pn['ftype_vn'] . '</name>';
  echo '    <alternate-name>' . $pn['ftype_alt'] . '</alternate-name>';
  echo '    <transcription>' . $pn['ftype_tr'] . '</transcription>';
  echo '    <translation lang="en">' . $pn['ftype_en'] . '</translation>';
  echo '  </feature-type>';
  echo '</div>';

  echo '<div class="year">temporal span: ';
  echo '  <temporal>';
  echo '    <begin_year begin-rule="' . $pn['beg_rule_id'] . '">from ' . $pn['beg_yr'] . '</begin_year>  ';
  echo '    <end_year end-rule="' . $pn['end_rule_id'] . '">to ' . $pn['end_yr'] . '</end_year>';
  echo '  </temporal>';
  echo '</div>';

  echo '<div class="geo">spatial info: ';
  echo '  <spatial>';
  echo '    <object-type>' . $pn['obj_type'] . '</object-type>';
  echo '    <coordinate-type>' . $pn['xy_type'] . '</coordinate-type>';
  echo '    <latitude-direction>N</latitude-direction>';                     //FIXME - calc N / S
  echo '    <degrees-latitude>' . $pn['y_coord'] . '</degrees-latitude>';
  echo '    <longitude-direction>E</longitude-direction>';                   //FIXME - calc E / W
  echo '    <degrees-longitude>' . $pn['x_coord'] . '</degrees-longitude>';
  echo '    <geo-source>(geo data source: ' . $pn['geo_src'] . ')</geo-source>';

  echo '    <present-location>';

//  foreach($preslocs as $ploc) {

//  }
  echo '    </present-location>';

  echo '  </spatial>';
  echo '</div>';

  echo '<div class="relate">';  
  echo '  <historical-context>';

$po_length = sizeof($partofs);
if ($po_length > 0) {
  echo '    <div id="partof">';
  echo '     <part-of-relationships><div id="parent_slug">part of:</div>';

    foreach ($partofs as $po) {
      echo '<br><a href="';
      echo  BASE_URL . '/placename/' . $po['parent_sys_id'] . '">';
      echo ' <parent-name>' . $po['parent_vn'] . '</parent-name>';
      echo ' <transcribed-name>' . $po['parent_tr'] . '</transcribed-name></a>';
      echo ' from ' . $po['begin_year'] . ' to ' . $po['end_year'];
    }

  echo '    </part-of-relationships>';
  echo '   </div>';  // closing part of div to format sub-units div
} 
else {
echo '<div id="parent_slug">no parents</div>';
}

$su_length = sizeof($subunits);

if ($su_length > 0) {
  echo '
   <div id="subunits">
     <a href="javascript:toggleDiv(\'sulist\');" title="show subordinate units" style="background-color: #ccc; padding: 5px 10px;">sub units:</a>
       <div id="sulist" style="display:none">
  ';
            foreach ($subunits as $su) {
              echo '<a href="';
              echo  BASE_URL . '/placename/' . $su['child_sys_id'] . '">';
              echo  $su['child_vn'] . ' ';
              echo  $su['child_tr'] . '</a> ';
              echo 'from: ' . $su['begin_year'] . ' to ';
              echo $su['end_year'];
              echo ' ['   . $su['child_sys_id'] . ']<br>';
            }
  echo '
       </div>
    </div>
  ';
}
else {
echo '<div id="parent_slug">no subunits</div>';
}
  echo '  </historical-context>';
  echo '</div>';

  echo '<div class="src">data source: ';
  echo '  <data-source>' . $pn['data_src'] . '</data-source>';
  echo '</div>';

if ($pn['snote_text'] !='') {
  echo '<div class="note">source note: ';
  echo '  <source-note>' . $pn['snote_text'] . '</source-note>';
  echo '</div>';
}
if (($pn['snote_uri'] !='') AND  ($pn['snote_uri'] !='CHGIS')){
  echo '<div class="note">source uri: ';
  echo '  <source-note-uri><a href="' . $pn['snote_uri'] . '" target="_blank">' . $pn['snote_uri'] . '</a></source-note-uri>';
  echo '</div>';
}


  echo '<div class="license">';
  echo '  Copyright: ' . date('Y') . '  ';
  echo '  &copy; <copyright>' . $pn['data_src'] . '</copyright>,  License:  <a href="' . LIC_URI . '" target="_blank"><license>' . LIC .'</license></a><br />';
  echo '  <br \>Published by: <publisher>China Historical GIS [Harvard University and Fudan University]</publisher> ';
  echo '  <p>Generated by the <a href="/tgaz"><system>Temporal Gazetteer Service</system></a><br />';
  echo '</div>';

  echo '</placename>';
  echo '</div>';
  echo '</body></html>';
}

?>
