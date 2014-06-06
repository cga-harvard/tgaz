<?php

define('MAX_SEARCH_HITS', 200);
define('FIXED_KEY_CHAR','$');

/*
 *
 *
 */
function search_placename($conn, $name_key, $year_key, $fmt = 'json') {



  if(substr_compare($name_key, '$', -1, 1) === 0) {   //test for last char '$' to indicate no wildcard
    $name_key = substr($name_key, 0, -1);
  }  else {
    $name_key = $name_key . '%';
  }

  $query = "SELECT pn.sys_id, pn.data_src, pn.name, pn.transcription, concat(pn.beg_yr, '-', pn.end_yr) years, " .
      "pn.ftype_vn, pn.ftype_tr, " .
      "parent.sys_id 'parent sys_id', parent.name 'parent name', parent.transcription 'parent transcription' " .
//      "parent.beg_yr 'parent begin year', parent.end_yr 'parent end year' " .
      "FROM mv_pn_srch pn LEFT JOIN part_of pof ON (pn.id = pof.child_id) " .
      "LEFT JOIN mv_pn_srch parent ON (parent.id = pof.parent_id) " .
      "WHERE pn.transcription LIKE ? ";

  if ($year_key) {
      $query .= "AND (pn.beg_yr >= ? AND pn.end_yr <= ? ) ";
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

    $pns2 = reduce_to_first_parent($pns);

    switch($fmt) {
      case 'json':
        search_to_json($pns2, $name_key, $year_key); break;
      case 'html':
        search_to_html($pns2, $name_key, $year_key); break;
      case 'xml':
        search_to_xml($pns2, $name_key, $year_key); break;
      default:
        tlog(E_WARNING, "Invalid fmt type: " . $fmt);
        //FIXME - output?? shouldn't really get here
    }
  }
}

// input:  ordered placenames
// process:  for repeated sys_ids, filter for the first parent in each group
// for alternative to display more than the first parent, see the function 'reduce parents' below
function reduce_to_first_parent($pns) {

  $pns3 = array();

  $prev = null;
  $max_parents = 6;      //maximum number of parents to display

  foreach($pns as $pn) {

    if ($pn['parent sys_id'] == null) {
      $parent = '';
    } else {
      $parent =  $pn['parent name'] . " (" . $pn['parent transcription'] . ")";
    }

    $parent_count = 0;

    if (($prev != null) && ($pn['sys_id'] == $prev['sys_id'])) {
        //skip later parents
    } else {

      $pns3[] = array(
        'sys_id'          => $pn['sys_id'],
        'uri'             => 'http://chgis.hmdc.harvard.edu/placename/' . $pn['sys_id'],
        'name'            => $pn['name'],
        'transcription'   => $pn['transcription'],
        'years'           => $pn['years'],
        'parent'          => $parent,
        'feature type'    => $pn['ftype_vn'] . " (" . $pn['ftype_tr'] . ")",
        'data source'     => $pn['data_src']
      );

      $prev = $pn;  //keep a reference to this pn for the next iteration
    }
  }

  return $pns3;
}


function search_to_json($pns, $name_key, $year_key) {

    $jt = "{\n";
    $indent = '  ';
    $depth = 0;          //depth of indent

    $jt .= jline('system', 'CHGIS - Harvard University & Fudan University', 1)
        .  jline('memo', "Results for query matching key '$name_key'"
        . ( $year_key ? " and year '$year_key'" : "")
        . ( (count($pns) >= MAX_SEARCH_HITS) ? '  Returned more than the maximum. Please refine your search.' : ''), 1)
        .  jline('count', count($pns), 1)
//        .  $indent . "\"hits\" : [\n"
        .  jarray('hits', $pns, 1, true)
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
           . "      <uri>" . $pn['uri'] . "</uri>\n"
           . "      <name>" . $pn['name'] . "</name>\n"
           . "      <transcription>" . $pn['transcription'] . "</transcription>\n"
           . "      <years>" . $pn['years'] . "</years>\n"
           . "      <parent>" . $pn['parent'] . "</parent>\n"
           . "      <feature-type>" . $pn['feature type'] . "</feature-type>\n"
           . "      <data-source>" . $pn['data source'] . "</data-source>\n"
           . "    </placename>\n";
    }


    $t .= "  </placenames>\n"
       . "</search-results>";



    header('Content-Type: text/xml; charset=utf-8');
    echo $t;
}

function search_to_html($pns, $name_key, $year_key) {

    $t = "<!DOCTYPE html>\n"
      . "<html>\n"
      . "<body>\n";

     // introductory remarks here

     // <div class="results">

    foreach ($pns as $pn) {
    $t .= "    <dl class=\"pn\">" . $pn['sys_id']
       .  "        <dt class=\"pnt\"></dt><dd class=\"pnd\"></dd>\n"
       // etc.
       . "</dl>\n";
    }

    $t .= "</body>\n"
       .  "</html>";

    header('Content-Type: text/html; charset=utf-8');
    echo $t;
}

// use this to capture multiple parents (NOT CURRENTLY USED CODE)
// input:  ordered placenames
// process:  for repeated sys_ids reformat as a single item with a compound field for multiple parents
/*
function reduce_parents($pns) {
  // create new array
  $pns2 = array();

  $prev = null;
  $max_parents = 6;      //maximum number of parents to display

  foreach($pns as $pn) {

    if ($pn['parent sys_id'] == null) {
      $parent = null;
    } else {
      $parent = array(
          'sys_id'          => $pn['parent sys_id'],
          'name'            => $pn['parent name'],
          'transcription'   => $pn['parent transcription'],
          'years'           => $pn['parent begin year'] . " ~ " . $pn['parent end year']
      );
    }

    $parent_count = 0;

    if (($prev != null) && ($pn['sys_id'] == $prev['sys_id'])) {
      if ($parent_count++ < $max_parents) {
        $pns2[count($pns2) - 1]['parents'][] =   $parent;
      }
    } else {

      $pns2[] = array(
        'sys_id'          => $pn['sys_id'],
        'name'            => $pn['name'],
        'transcription'   => $pn['transcription'],
        'years'           => $pn['years'],
        'parents'         => ($parent == null ? array() : array($parent)),
        'data source'     => $pn['data_src']
      );

      $prev = $pn;  //keep a reference to this pn for the next iteration
    }               //however, cannot append to this reference (php mystery)
  }

  return $pns2;
}
*/

?>