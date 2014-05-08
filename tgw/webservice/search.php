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
function search_placename($conn, $name_key) { //, $year_key) {

//  $max_search_hits = 200;
//  echo "sp ... " . $name_key;

//FIXME add date params
//FIXME add parent in results

  if (is_roman($name_key)) {

    if(substr_compare($name_key, '$', -1, 1) === 0) {   //test for last char '$'
      $name_key = substr($name_key, 0, -1);
    }  else {
      $name_key = $name_key . '%';
    }

    $query = "SELECT pn.sys_id, sp_vn.written_form name, sp_tr.written_form transcription, " .
           "pn.ftype_vn 'feature type', pn.ftype_tr 'feature type transcription' " .
           "FROM v_placename pn JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id) " .
           "JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id) " .
           "JOIN script ON (sp_vn.script_id = script.id) " .
           "WHERE script.default_per_lang = 1 " .
           "AND sp_tr.trsys_id in ('py', 'rj') " .
           "AND sp_tr.written_form LIKE '" . $name_key . "' " .
           "ORDER BY transcription limit " . MAX_SEARCH_HITS . ";";
  } else {
    $query = "SELECT pn.sys_id, sp_vn.written_form name, sp_tr.written_form transcription, " .
           "pn.ftype_vn 'feature type', pn.ftype_tr 'feature type transcription' " .
           "FROM v_placename pn JOIN spelling sp_vn ON (pn.id = sp_vn.placename_id) " .
           "JOIN spelling sp_tr ON (pn.id = sp_tr.placename_id) " .
           "JOIN script ON (sp_vn.script_id = script.id) " .
           "WHERE script.default_per_lang = 1 " .
           "AND sp_tr.trsys_id in ('py', 'rj') " .
           "AND sp_vn.written_form LIKE '" . $name_key . "%' " .
           "ORDER BY name limit " . MAX_SEARCH_HITS . ";";
  }

  $pns = array();  //indexed

  if ($result = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $pns[] = $row;
    }

    mysqli_free_result($result);
    to_json_list($pns, $name_key);

  }  else { //error
    tlog(E_ERROR, "search_placename error");
    echo "search placename error";
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