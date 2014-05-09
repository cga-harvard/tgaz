<?php

define('MAX_SEARCH_HITS', 200);
define('FIXED_KEY_CHAR','$');


//function get_max_hits() { return 200; }

/*
 *
 *
 *    update query when the default script/trsys issue is resolved
 *
 *
 */
function search_placename($conn, $name_key, $year_key) {

//  echo "sp ... " . $name_key;

//FIXME multiple parent years

  if (is_roman($name_key)) {

    if(substr_compare($name_key, '$', -1, 1) === 0) {   //test for last char '$'
      $name_key = substr($name_key, 0, -1);
    }  else {
      $name_key = $name_key . '%';
    }

    $query = "SELECT pn.sys_id, pn.name, pn.transcription, concat(pn.beg_yr, '-', pn.end_yr) years, " .
        "pn.ftype_vn 'feature type', pn.ftype_tr 'feature type transcription', " .
        "parent.name 'parent name', parent.transcription 'parent transcription', " .
        "concat(parent.beg_yr, '-', parent.end_yr) 'parent years' " .
        "FROM mv_pn_srch pn LEFT JOIN part_of pof ON (pn.id = pof.child_id) " .
        "LEFT JOIN v_pn_srch parent ON (parent.id = pof.parent_id) " .
        "WHERE pn.transcription LIKE ? ";

    if ($year_key) {
        $query .= "AND (pn.beg_yr >= ? AND pn.end_yr <= ? ) ";
    }

    $query .=    "ORDER BY pn.transcription, pn.sys_id limit " . MAX_SEARCH_HITS . ";";

  } else {

    $query = "SELECT pn.sys_id, pn.name, pn.transcription, concat(pn.beg_yr, '-', pn.end_yr) years, " .
        "pn.ftype_vn 'feature type', pn.ftype_tr 'feature type transcription', " .
        "parent.name 'parent name', parent.transcription 'parent transcription', " .
        "concat(parent.beg_yr, '-', parent.end_yr) 'parent years' " .
        "FROM mv_pn_srch pn LEFT JOIN part_of pof ON (pn.id = pof.child_id) " .
        "LEFT JOIN v_pn_srch parent ON (parent.id = pof.parent_id) " .
        "WHERE pn.name LIKE ? " .
        "ORDER BY pn.name, pn.sys_id limit " . MAX_SEARCH_HITS . ";";

           $name_key .= '%';
  }

  if (!$stmt = $conn->prepare($query)) {
      tlog(E_ERROR, "mysqli prepare failure: " . $stmt->errno . " - " . $stmt->error);
      echo "error";
      return;
  }

//echo 'name key = ' . $name_key;
//echo 'query = ' . $query;

  if ($year_key) {
      echo 'yr param not null';
      if (!$stmt->bind_param('sii', $name_key, $year_key, $year_key)) {       // 'sii' means '1 string and 2 int params'
          tlog(E_ERROR, "mysqli binding yr parameters failed: " . $stmt->errno . " - " . $stmt->error);
          echo "error";
          return;
      }
  } else {
      if (!$stmt->bind_param('s', $name_key)) {                             // 's' means 'one string param'
          tlog(E_ERROR, "mysqli binding parameters failed: " . $stmt->errno . " - " . $stmt->error);
          echo "error";
          return;
      }
  }

  if (!$stmt->execute()) {
     tlog("mysqli statement execute failed: (" . $stmt->errno . " - " . $stmt->error);
     echo "error";
     return;
  } else {

    $pns = array();  //indexed

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
       $pns[] = $row;
    }

    $result->free();
    $stmt->close();

    to_json_list($pns, $name_key);
  }

}

function to_json_list($pns, $name_key) {
//  echo 'test unicode_ord: ' . unicode_ord("⽇");

  $wrapper = array(
      'memo'       => "Results for query matching key '" . $name_key . "'." .
                       ( (count($pns) >= MAX_SEARCH_HITS) ? '  Returned more than the maximum. Please refine your search.' : ''),
      'count'      => count($pns),
      'hits'       => $pns
  );

  header('Content-Type: text/json; charset=utf-8');
  echo json_encode($wrapper, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

}

// input:  ordered placenames
// process:  for repeated sys_ids reformat as a single item with a compound field for multiple parents
// ?? now that there is a materialized view, perhaps this can be done there?
// if here, perhaps specific to json:  parents : [ "p1" : "1911-1913", "p2" : "1914-1915" ]
function reduce_parents($pns) {
  // create new array
  pns2 = array();

  $prev = null;
  $max = 6;      //maximum number of parents to concatenate

  foreach($pns as $pn) {
    if ($pn['id'] == $prev['id']) {
        // append parent to prev.parent

    } else {
      pns2[] = $pn;
    }
  }

  return pns2;
}

function is_roman($str) {
  $c = mb_substr($str, 0, 1, 'utf-8');
  $v = unicode_ord($c);
  return ($v > 31 && $v < 254);
}

/*
 * found at: http://stackoverflow.com/questions/9361303/can-i-get-the-unicode-value-of-a-character-or-vise-versa-with-php
 * true source unknown
 *
 */
function unicode_ord($c) {
    if (ord($c{0}) >=0 && ord($c{0}) <= 127)
        return ord($c{0});
    if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
        return (ord($c{0})-192)*64 + (ord($c{1})-128);
    if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
        return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
    if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
        return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
    if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
        return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
    if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
        return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
    if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
        return FALSE;
    return 0;
}

?>