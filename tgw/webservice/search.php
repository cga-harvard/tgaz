<?php

define('MAX_SEARCH_HITS', 200);
define('HTML_SEARCH_HITS', 8);
define('FIXED_KEY_CHAR','$');


/*
 *  Note that the handling of the extraction and display of the earliest parent is from the materialized view
 *  while the search facet for parent is through an optional join of the part_of table.  To keep these separate
 *  the variables for the former use 'parent' while the later use 'pof'
 */
function search_placename($conn, $name_key, $year_key, $fmt, $src_key, $ftype_key, $pof_key, $pg_key) {

  if(substr_compare($name_key, '$', -1, 1) === 0) {   //test for last char '$' to indicate no wildcard
    $name_key = substr($name_key, 0, -1);
  }  else {
    $name_key = $name_key . '%';
  }

  if ($pof_key) {
    $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT pn.sys_id, pn.data_src, pn.name, pn.transcription, pn.beg_yr, pn.end_yr, " .
      "pn.ftype_vn, pn.ftype_tr, pn.x_coord, pn.y_coord, " .
      "pn.parent_sys_id 'parent sys_id', pn.parent_vn 'parent name', pn.parent_tr 'parent transcription' " .
      "FROM mv_pn_srch pn JOIN spelling sp ON (sp.placename_id = pn.id) " .
      "JOIN part_of pof ON (pof.child_id = pn.id) JOIN spelling spp ON (spp.placename_id = pof.parent_id) " .
      "WHERE sp.written_form LIKE ? ";
  } else {
    $query = "SELECT  SQL_CALC_FOUND_ROWS DISTINCT pn.sys_id, pn.data_src, pn.name, pn.transcription, pn.beg_yr, pn.end_yr, " .
      "pn.ftype_vn, pn.ftype_tr, pn.x_coord, pn.y_coord, " .
      "pn.parent_sys_id 'parent sys_id', pn.parent_vn 'parent name', pn.parent_tr 'parent transcription' " .
      "FROM mv_pn_srch pn JOIN spelling sp ON (sp.placename_id = pn.id) " .
      "WHERE sp.written_form LIKE ? ";
  }

  $bindParam = new BindParam();  // in tgaz_lib
  $bindParam->add('s', $name_key);

  if ($year_key) {
      $query .= "AND (pn.beg_yr <= ? AND pn.end_yr >= ? ) ";
      $bindParam->add('i', $year_key);  //begin
      $bindParam->add('i', $year_key);  //end
  }

  if ($src_key != null) {
      $src_key = strtoupper($src_key);
      $bindParam->add('s', $src_key);
      $query .= "AND pn.data_src = ? ";
  }

  if ($ftype_key != null) {
      $bindParam->add('s', $ftype_key);  //vernacular
      $bindParam->add('s', $ftype_key);  // transcription
      //other names (alt, English) deferred
      $query .= "AND (pn.ftype_vn = ? OR pn.ftype_tr = ?) ";
  }

  if ($pof_key != null) {
      $pof_key = $pof_key . '%';
      $bindParam->add('s', $pof_key);
      $query .= "AND spp.written_form LIKE ? ";
  }

  $query .=  "ORDER BY pn.transcription, pn.beg_yr ";

  if ($fmt == 'html') {
      $query .=  "limit ? , " . HTML_SEARCH_HITS . ";";
      $bindParam->add('i', $st_key);
  } else {
      $query .=  "limit " . MAX_SEARCH_HITS . ";";
  }

  if (!$stmt = $conn->prepare($query)) {
      tlog(E_ERROR, "mysqli prepare failure: " . $conn->error);
      alt_response(500);
      return;
  }

  if (!call_user_func_array( array($stmt, 'bind_param'), $bindParam->get())) {
      tlog(E_ERROR, "mysqli binding yr parameters failed: " . $stmt->errno . " - " . $stmt->error);
      alt_response(500);
      return;
  }

  if (!$stmt->execute()) {
     tlog(E_ERROR, "mysqli statement execute failure: " . $stmt->errno . " - " . $stmt->error);
     alt_response(500);
     return;
  }

    // workaround for lack of access to stmt->get_result method
    $pns = array();  //indexed

    $md = $stmt->result_metadata();
    $params = array();
    $fvals = array();

    while($field = $md->fetch_field()) {
        $params[] = &$fvals[$field->name];
    }

    call_user_func_array(array($stmt, 'bind_result'), $params);

    while ($stmt->fetch()) {
      // none of the "copy array" functions in PHP  actually create new arrays
      // hence the simple implementation here
        $cp = array();
        foreach($fvals as $key => $value) {
            $cp[$key] = $value;
        }
        $pns[] = $cp;
    }

    $stmt->close();

    // get total results (for query without limit)
    $pn_total = 0;  // this will be the total for the query without the limit
    if (!$stmt = $conn->prepare("SELECT FOUND_ROWS()")) {
        tlog(E_ERROR, "total rows prepare statement failed");
    } else {
      if (!$stmt->execute()) {
        tlog(E_ERROR, "total rows query exception: " . $stmt->errno . " - " . $stmt->error);
      } else {
        $stmt->bind_result($pn_total);
        $stmt->fetch();
        $stmt->close();
      }
    }

    switch($fmt) {
      case 'json':
        search_to_json($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $pn_total); break;
      case 'html':
        search_to_html($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $pg_key, $pn_total); break;
      case 'xml':
        search_to_xml($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $pn_total); break;
      default:
        tlog(E_WARNING, "Invalid fmt type: " . $fmt);
        //FIXME - output?? shouldn't really get here
    }

}

function search_to_json($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $total) {

    //reformat field display for json
    $pns_json = array();

    foreach($pns as $pn) {

        if ($pn['parent sys_id'] == null) {
          $parent_name = '';
        } else {
          $parent_name =  $pn['parent name'] . " (" . $pn['parent transcription'] . ")";
        }

        $pns_json[] = array(
          'sys_id'          => $pn['sys_id'],
          'uri'             => BASE_URL . '/placename/' . $pn['sys_id'],
          'name'            => $pn['name'],
          'transcription'   => $pn['transcription'],
          'years'           => $pn['beg_yr'] . " ~ " . $pn['end_yr'],
          'parent sys_id'   => $pn['parent sys_id'],
          'parent name'     => $parent_name,
          'feature type'    => $pn['ftype_vn'] . " (" . $pn['ftype_tr'] . ")",
          'xy coordinates'  => $pn['x_coord'] . ", " . $pn['y_coord'],
          'data source'     => $pn['data_src']
        );
    }

    $jt = "{\n";
    $indent = '  ';
    $depth = 0;          //depth of indent

    $jt .= jline('system', 'CHGIS - Harvard University & Fudan University', 1)
        .  jline('memo', "Results for query matching key '$name_key'"
        . ( $year_key ? " and year '$year_key'" : "")
        . ( $src_key ? " and data source '$src_key'" : "")
        . ( $ftype_key ? " and feature type '$ftype_key'" : "")
        . ( $pof_key ? " and parent '$pof_key'" : "")
        . ( (count($pns) >= MAX_SEARCH_HITS) ? '  Returned more than the maximum. Please refine your search.' : ''), 1)
        .  jline('count of displayed results', count($pns), 1)
        .  jline('count of total results', $total, 1)
        .  jarray('placenames', $pns_json, 1, true)
        . "}";

    header('Content-Type: text/json; charset=utf-8');
    echo $jt;
}

function search_to_xml($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $total) {

    $t = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
       . "<search-results system=\"CHGIS - Harvard University and Fudan University\""
       . " count-displayed=\"" . count($pns) . "\""
       . " count-total=\"" . $total . "\">\n"

       . "  <placenames>\n"
       . ((count($pns) >= MAX_SEARCH_HITS) ? "    <memo>Returned more than the maximum. Please refine your search.</memo>\n" : "");

    foreach($pns as $pn) {
       $t .= "    <placename sys_id=\"" . $pn['sys_id'] . "\">\n"
           . "      <uri>" . BASE_URL . '/placename/' . $pn['sys_id'] . "</uri>\n"
           . "      <name>" . $pn['name'] . "</name>\n"
           . "      <transcription>" . $pn['transcription'] . "</transcription>\n"
           . "      <years><begin>" . $pn['beg_yr'] . "</begin><end>" . $pn['end_yr'] . "</end></years>\n";

           if ($pn['parent sys_id'] == null) {
             $t .= "      <parent />\n";
           } else {
             $t .= "      <parent sys_id=\"" . $pn['parent sys_id'] . "\">" . $pn['parent name']
                . " (" . $pn['parent transcription'] . ")</parent>\n";
           }

           $t .= "      <feature-type>" . $pn['ftype_vn'] . " (" . $pn['ftype_tr'] . ")</feature-type>\n"
           . "          <xy-coordinates><x>" . $pn['x_coord'] . "</x><y>" . $pn['y_coord'] ."</y></xy-coordinates>\n"
           . "      <data-source>" . $pn['data_src'] . "</data-source>\n"
           . "    </placename>\n";
    }

    $t .= "  </placenames>\n"
       . "</search-results>";

    header('Content-Type: text/xml; charset=utf-8');
    echo $t;
}

?>