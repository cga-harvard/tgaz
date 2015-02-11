<?php

require ("../../polyphony.inc");
require ("./tgaz-lib.php");

echo "<br /> building paths ... ";

$conn = mysqli_connect("p:$db_addr", "$db_user", "$db_pass", "$db_name");
  if (!$conn) {
      die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
  }

set_time_limit(800);  //default is 30 which times out, may need longer for complete processing

build_all_paths($conn);

mysqli_close($conn);
echo "<br /> done";

function build_all_paths($conn) {

  $children_query = "SELECT DISTINCT child_id FROM part_of LIMIT 40;";  // LIMIT 4800
  $result = mysqli_query($conn, $children_query) or die("<br />Error: " . mysqli_error($conn));
  $children = mysqli_fetch_all($result, MYSQLI_NUM);
  mysqli_free_result($result);
  echo "<br /> total distinct children count: " . count($children);

  // hash for child's paths; form is:  child_id => array( beg => , end => , path => )
  // use utility function "getParent($ppaths, $parent_id, $cbeg, $cend)" to find parent in  array
  $acc = array();

  foreach ($children as $child_id) {
     $pth = build_paths($conn, $child_id[0], 1, $acc);
  }

  echo "<br />count of processed placenames: " . count($acc);

  insert_to_table($conn, $acc);

}

// use years to verify correct time frame for choosing parents
// we also select parent from $ppaths, so we need more specific keys there to avoid overwriting
// in the case of multiple parents
function build_paths($conn, $child_id, $depth, &$acc) {  // $beg_yr, $end_yr

    static $maxdepth = 1;
    if ($depth > $maxdepth) {
      $maxdepth = $depth;
      echo "<br /> maxdepth is now: " . $maxdepth;
    }

    $pth = "";
    $parents_query = "SELECT parent_id, begin_year, end_year FROM part_of WHERE child_id = " . $child_id . ";";

    if ($result = mysqli_query($conn, $parents_query)) {
      $parents = mysqli_fetch_all($result, MYSQLI_ASSOC);
//      echo "<br /> count of parents: " . count($parents);

      mysqli_free_result($result);

      if (count($parents) > 0 && $depth <= 5) {
//        if (count($parents) > 5) echo '<br /> multiple parents: ' . $child_id;

        foreach ($parents as $parent) {
          if ($child_id == $parent['parent_id']) {
            echo "<br />BAD DATA: child_id == parent_id: " . $child_id;
            continue;
          }

          $ptid = $parent['parent_id'];

          // if has no path in paths table, then recurse
          if (!isset($acc["$ptid"])) {
            build_paths($conn, $ptid, $depth + 1, $acc);

          }

          $parent_path_match = get_parent_path($ptid, $parent['begin_year'], $parent['end_year'], $acc);
          if ($parent_path_match) {
            $pth = $ptid . $parent_path_match;
          } else {
            $pth = $ptid . "(" . $parent['begin_year'] . "_" . $parent['end_year'] . "):-";
          }
          $acc["$child_id"][] = array(
              "begin_year"     => $parent['begin_year'],
              "end_year"       => $parent['end_year'],
              "path"           => $pth
          );
        }

      } else {  // no parents, i.e. end of path
        $acc["$child_id"][] = array();  //empty
      }
    }
}

//find parent where the years match
//already tested for isset
function get_parent_path($ptid, $beg_yr, $end_yr, $acc) {

  $pathsarray = $acc[$ptid];

  //loop through each parent path assoc. array and check for viable match on years
  foreach ($pathsarray as $par) {
    if( isset($par['begin_year']) && isset($par['end_year'])) {
      if ($par['begin_year'] <= $beg_yr &&  $par['end_year'] >= $end_yr) {
//        if ($ptid == 11013) { echo "<br />  -- b: $beg_yr ; e: $end_yr *** $ptid " . '(' . $par['begin_year'] . '-' . $par['end_year'] . ')-' . $par['path']; }
        return '(' . $beg_yr . '_' . $end_yr . '):-' . $par['path'];
      }
    }
  }

  return null;
}


function insert_to_table($conn, $acc) {

  mysqli_query($conn,  "DROP TABLE IF EXISTS partof_path;") or die("<br />Error: " . mysqli_error());
  //this will drop the index, too

  $create_query = "CREATE TABLE partof_path (" .
                  "placename_id      INT UNSIGNED NOT NULL, " .
                  "path          VARCHAR(1028), " .
                  "INDEX poppn_idx (placename_id) " .
                  ") ENGINE = INNODB;";

  mysqli_query($conn, $create_query) or die("<br />Error: " . mysqli_error());

  $insert_query = "INSERT INTO partof_path VALUES (?, ?);";
//  $stmt = $conn->prepare($insert_query);

  if (!$stmt = $conn->prepare($insert_query)) {
      echo "<br />error with conn->prepare";
      return;
  }

  $stmt->bind_param("is", $pn_id, $pn_path);

  foreach ($acc as $pn_id => $path_info) {
    foreach($path_info as $pnar) {
      //$pn_id = $pn[
      $pn_path = isset($pnar['path']) ? $pnar['path'] : '';
//      echo "<br /> insert:  pn_id: $pn_id ; path: $pn_path ";
      $stmt->execute();
    }
  }
  $stmt->close();

}

?>
