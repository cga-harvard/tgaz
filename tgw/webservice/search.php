<?php

define('MAX_SEARCH_HITS', 200);
define('FIXED_KEY_CHAR','$');

/*
 *
 *
 */
function search_placename($conn, $name_key, $year_key, $fmt = 'json', $data_src = 'ANY', $ftype = 'ANY') {

  if(substr_compare($name_key, '$', -1, 1) === 0) {   //test for last char '$' to indicate no wildcard
    $name_key = substr($name_key, 0, -1);
  }  else {
    $name_key = $name_key . '%';
  }

  $query = "SELECT pn.sys_id, pn.data_src, pn.name, pn.transcription, pn.beg_yr, pn.end_yr, " .
      "pn.ftype_vn, pn.ftype_tr, " .
      "pn.parent_sys_id 'parent sys_id', pn.parent_vn 'parent name', pn.parent_tr 'parent transcription' " .
      "FROM mv_pn_srch pn JOIN spelling sp ON (sp.placename_id = pn.id) " .
      "WHERE sp.written_form LIKE ? ";

  if ($year_key) {
      $query .= "AND (pn.beg_yr >= ? AND pn.end_yr <= ? ) ";
  }

  if ($data_src != 'ANY') {
      $query .= "AND pn.data_src = '" . $data_src . "' ";
  }

  if ($ftype != 'ANY') {
      $query .= "AND pn.ftype_tr = '" . $ftype . "' ";  // FIXME   LIKE ?? Chinese vs Roman ?? some official id ??
  }

  $query .=    "ORDER BY pn.transcription, pn.sys_id limit " . MAX_SEARCH_HITS . ";";


  if (!$stmt = $conn->prepare($query)) {
      tlog(E_ERROR, "mysqli prepare failure: " . $conn->error);
      alt_response(500);
      return;
  }

  if ($year_key) {
      if (!$stmt->bind_param('sii', $name_key, $year_key, $year_key)) {       // 'sii' means '1 string and 2 int params'
          tlog(E_ERROR, "mysqli binding yr parameters failed: " . $stmt->errno . " - " . $stmt->error);
          alt_response(500);
          return;
      }
  } else {
      if (!$stmt->bind_param('s', $name_key)) {                             // 's' means 'one string param'
          tlog(E_ERROR, "mysqli binding parameters failed: " . $stmt->errno . " - " . $stmt->error);
          alt_response(500);
          return;
      }
  }

  if (!$stmt->execute()) {
     tlog("mysqli statement execute failed: (" . $stmt->errno . " - " . $stmt->error);
     alt_response(500);
     return;
  } else {
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

    switch($fmt) {
      case 'json':
        search_to_json($pns, $name_key, $year_key); break;
      case 'html':
        search_to_html($pns, $name_key, $year_key); break;
      case 'xml':
        search_to_xml($pns, $name_key, $year_key); break;
      default:
        tlog(E_WARNING, "Invalid fmt type: " . $fmt);
        //FIXME - output?? shouldn't really get here
    }
  }
}

function search_to_json($pns, $name_key, $year_key) {

    //reformat field display for json
    $pns_json = [];

    foreach($pns as $pn) {

        if ($pn['parent sys_id'] == null) {
          $parent_name = '';
        } else {
          $parent_name =  $pn['parent name'] . " (" . $pn['parent transcription'] . ")";
        }

        $pns_json[] = array(
          'sys_id'          => $pn['sys_id'],
          'uri'             => 'http://chgis.hmdc.harvard.edu/placename/' . $pn['sys_id'],
          'name'            => $pn['name'],
          'transcription'   => $pn['transcription'],
          'years'           => $pn['beg_yr'] . " ~ " . $pn['end_yr'],
          'parent sys_id'   => $pn['parent sys_id'],
          'parent name'     => $parent_name,
          'feature type'    => $pn['ftype_vn'] . " (" . $pn['ftype_tr'] . ")",
          'data source'     => $pn['data_src']
        );
    }

    $jt = "{\n";
    $indent = '  ';
    $depth = 0;          //depth of indent

    $jt .= jline('system', 'CHGIS - Harvard University & Fudan University', 1)
        .  jline('memo', "Results for query matching key '$name_key'"
        . ( $year_key ? " and year '$year_key'" : "")
        . ( (count($pns) >= MAX_SEARCH_HITS) ? '  Returned more than the maximum. Please refine your search.' : ''), 1)
        .  jline('count', count($pns), 1)
//        .  $indent . "\"hits\" : [\n"
        .  jarray('placenames', $pns_json, 1, true)
//        .  $indent . "},\n"
        . "}";

    header('Content-Type: text/json; charset=utf-8');
    echo $jt;
}

function search_to_xml($pns, $name_key, $year_key) {

    $t = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
       . "<search-results system=\"CHGIS - Harvard University and Fudan University\""
       . " count=\"" . count($pns) . "\">\n"

       . "  <placenames>\n"
       . ((count($pns) >= MAX_SEARCH_HITS) ? "    <memo>Returned more than the maximum. Please refine your search.</memo>\n" : "");

    foreach($pns as $pn) {
       $t .= "    <placename sys_id=\"" . $pn['sys_id'] . "\">\n"
           . "      <uri>" . 'http://chgis.hmdc.harvard.edu/placename/' . $pn['sys_id'] . "</uri>\n"
           . "      <name>" . $pn['name'] . "</name>\n"
           . "      <transcription>" . $pn['transcription'] . "</transcription>\n"
           . "      <years>" . $pn['beg_yr'] . " ~ " . $pn['end_yr'] . "</years>\n";

           if ($pn['parent sys_id'] == null) {
             $t .= "      <parent />\n";
           } else {
             $t .= "      <parent sys_id=\"" . $pn['parent sys_id'] . "\">" . $pn['parent name']
                . " (" . $pn['parent transcription'] . ")</parent>\n";
           }

           $t .= "      <feature-type>" . $pn['ftype_vn'] . " (" . $pn['ftype_tr'] . ")</feature-type>\n"
           . "      <data-source>" . $pn['data_src'] . "</data-source>\n"
           . "    </placename>\n";
    }

    $t .= "  </placenames>\n"
       . "</search-results>";

    header('Content-Type: text/xml; charset=utf-8');
    echo $t;
}

?>