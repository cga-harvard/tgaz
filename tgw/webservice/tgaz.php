<?php

require ("../../elroy.inc");
require ("./tgaz-lib.php");
require ("./placename.php");
#require ("./featuretype.php");
require ("./service-info.php");
require ("./search.php");

// use persistent connection with "p:" prepended to the hostname
$conn = mysqli_connect("p:$db_addr", "$db_user", "$db_pass", "$db_name", "$db_port");

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

$path_parts = explode('/', $url_path);
//FIXME check for required elements

$service = $path_parts[2];

//foreach ($path_parts as $part) {
//  echo '<br /> part = ' . $part;
//}
//echo '<br /> service:' . $service;

// regex parse:  api\/['placename'|'featuretype']\/['json'|'xml]\/(w+)

// for now pretend that the url will come in as:  /api/placename/:fmt/:id


if (false) {


} else {      //routing of the uri elements

  if ($service == "service-info") {
    get_service_info_json($conn);

  } elseif ($service == "placename") {

    // first try for fmt in path_part[3]
    // id in part[4]

    $fmt=$_GET["fmt"];
    if (!($fmt ))  {  // test for one of:  xml, geojson, json, http?
      $fmt = "json";
    }

    if (isset($path_parts[3])) { // and matches /hvd_\d+/
      get_placename($conn, $fmt, $path_parts[3]);
    } else {

//    ;

//      if ($id=$_GET["id"])  {
      if (isset($_GET["id"]))  {
        $id=$_GET["id"];
        get_placename($conn, $fmt, $id);

      } elseif ($namekey = $_GET["n"]) {
        search_placename($conn, $namekey);  //, null);
      } else {
        tlog(E_ERROR, "Id or Name not in request.");
        mysqli_close($conn);
        exit();
      }
    }

//    get_placename($conn, $fmt, $id);
  } else if ($service == "featuretype") {
    ;
  } else  {
    tlog(E_ERROR, "Unknown service : " . $service);
  }
}

mysqli_close($conn);

?>