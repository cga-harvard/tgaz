<?php

require ("../../elroy.inc");
require ("./tgaz-lib.php");
require ("./placename.php");
#require ("./featuretype.php");

/*echo  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"; // otherwise wrong encoding despite character set
*/

$conn = mysqli_connect("$db_addr", "$db_user", "$db_pass", "$db_name", "$db_port");

if (!$conn) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

//echo 'Success..... ' . mysqli_get_host_info($conn);
//echo "<br />Charset... " . mysqli_get_charset($conn)->charset;

// parsing of the uri

$requrl = "$_SERVER[REQUEST_URI]" ;  // e.g. /placename.php?fmt=json&id=hvd_34376
$url_path = parse_url( $requrl, PHP_URL_PATH);
//echo "<br /> $requrl :: $url_path";

//use "explode" to parse
// e.g.: $request_parts = explode('/', $_GET['url']);
// or:           $parts = explode('/', $_SERVER['REQUEST_URI']);
//

// regex parse:  api\/['placename'|'featuretype']\/['json'|'xml]\/(w+)

// for now pretend that the url will come in as:  /api/placename/:fmt/:id


$obj_type = "placename";

$id=$_GET["id"];

if (!($id ))  {
  tlog(E_ERROR, "Id not in request.");
  mysqli_close($conn);
  exit();
}

$fmt=$_GET["fmt"];
if (!($fmt ))  {  // test for one of:  xml, geojson, json, http?
  $fmt = "json";
}

//echo "<br />param obj_type: >>'$obj_type'<<  eparam id: >>'$id'<<  param fmt: >>$fmt<<";



//routing of the uri elements

if ($obj_type == "placename") {
  get_placename($conn, $fmt, $id);
} else if ($obj_type == "featuretype") {
  ;
} else  {
  tlog(E_ERROR, "Unknown object type : " . $obj_type);
}

mysqli_close($conn);

?>