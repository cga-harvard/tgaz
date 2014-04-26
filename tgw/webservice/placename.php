<?php


function get_placename($conn, $fmt, $sys_id) {

  $pn_query = "SELECT * FROM v_placename WHERE sys_id = '$sys_id';";
#  $sp_query = "SELECT * FROM spelling WHERE placename_id = '$id';";

  $pn_result = mysqli_query($conn, $pn_query) or die("<br />Error: " . mysqli_error());

  $pn_row = mysqli_fetch_row($pn_result);
  $pn = array(
    'id'              => $pn_row[0],              // Make this an assoc array ?
    'sys_id'          => $pn_row[1],
    'ftype_id'        => $pn_row[2],
    'data_src'        => $pn_row[3],
    'data_src_ref'    => $pn_row[4],
    'snote_id'        => $pn_row[5],
    'alt_of_id'       => $pn_row[6],
    'lev_rank'        => $pn_row[7],
    'beg_yr'          => $pn_row[8],
    'beg_rule'        => $pn_row[9],
    'end_yr'          => $pn_row[10],
    'end_rule'        => $pn_row[11],
    'obj_type'        => $pn_row[12],             #| enum('POINT','POLYGON','LINE','ENTITY')
    'xy_type'         => $pn_row[13],             #| enum('centroid','point','midpoint','point location','N/A')
    'x_coord'         => $pn_row[14],
    'y_coord'         => $pn_row[15],
    'geo_src'         => $pn_row[16],
    'added_on'        => $pn_row[17],
    'ftype_vn'        => $pn_row[18],
    'ftype_alt'       => $pn_row[19],
    'ftype_tr'        => $pn_row[20],
    'ftype_en'        => $pn_row[21],
    'snote_ref'       => $pn_row[22],
    'snote_text'      => $pn_row[23]
  );

//  echo "<p> $row[0]</p> <p>" . $pn['id'] . "</p>";
  mysqli_free_result($pn_result);
//  echo "<p> $row[0]</p> <p>" . $pn['id'] . "</p>";

  $spellings = get_spellings($conn, $pn['id']);

  $partofs = get_partofs($conn, $pn['id']);
echo "<p>count of partofs array: " . count($partofs) . "</p>" ;

  if ($fmt == "json") {
    to_json($pn, $spellings, $partofs);
  }

}

function get_spellings($conn, $pn_id) {
  #echo "pn_id: " . $pn_id;
  $sp_query = "SELECT * FROM v_spelling WHERE placename_id = " . $pn_id . ";";   // replace with prepared statement ??

#  $sp_result = mysqli_query($conn, $pn_query) or die("<br />Error: " . mysqli_error());

  $spellings = array();  // indexed array of associative arrays for the rows

  if ($sp_result = mysqli_query($conn, $sp_query)) {

    while ($sp_row = mysqli_fetch_row($sp_result)) {
      $spellings[] = array(
          #'id'              =>   $sp_row[0],      //don't need these
          #'placename_id'    =>   $sp_row[1],
          'script_id'        =>   $sp_row[2],
          'written_form'     =>   $sp_row[3],
          'exonym_lang'      =>   $sp_row[4],
          'trsys_id'         =>   $sp_row[5],
          'attested_by'      =>   $sp_row[6],
          'note'             =>   $sp_row[7],
          'script'           =>   $sp_row[8],
          'trsys'            =>   $sp_row[9]
        );
    }

    mysqli_free_result($sp_result);
  }  // else error
  return $spellings;
}

//not used for pelagios
function get_partofs($conn, $pn_id) {
echo "<p>pn_id: " . $pn_id . "</p>";

  $query = "SELECT * FROM v_partof WHERE child_id = " . $pn_id . " ORDER BY begin_year;";   // replace with prepared statement ??

  $partofs = array();

  if ($result = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_row($result)) {
      $partofs[] = array(
          #'id'              =>   $row[0],      //don't need this
          'child_id'         =>   $row[1],
          'parent_id'        =>   $row[2],
          'begin_year'       =>   $row[3],
          'end_year'         =>   $row[4],
          'parent_sys_id'    =>   $row[5],
          'parent_vn'        =>   $row[6],
          'parent_tr'        =>   $row[7]
      );
    }

    mysqli_free_result($result);
  }  // else error

  return $partofs;   ;
}

/*
function get_precbys($conn, $pn_id) {  //not used for pelagios

  $query = "SELECT * FROM v_precby WHERE placename_id = " . $pn_id . ";";   // replace with prepared statement ??

}
*/

function to_json($pn, $spellings, $partofs) { // , $precby, $partof) {

    $sp_json = array();  //indexed
    foreach ($spellings as $sp) {
        if ($sp['script_id'] != 0) {                      // has script
            $sp_json[] = array(
                $sp['script']     =>  $sp['written_form'],
                'exonym language' =>  $sp['exonym_lang'],
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

    $pn_json = array(
      'system'              => 'CHGIS, Harvard University',
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
        'begin/end_rules' => $pn['beg_rule'] . " / " . $pn['end_rule']
      ),

      'spatial' => array(
        'object_type' => $pn['obj_type'],
        'xy_type'     => $pn['xy_type'],
        'longitude'   => $pn['x_coord'],
        'latitude'    => $pn['y_coord'],
        'source'      => $pn['geo_src']
      ),

      'historical context' => array(
         'part of' => $po_json
      ),

      'data source'   => $pn['data_src']  //,
      //'source note'   => $pn['snote_text']
  );

  echo json_encode($pn_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

  return;
}


/*
final function to_xml {

}

final function to_pelagios_rdf {

}
*/
?>