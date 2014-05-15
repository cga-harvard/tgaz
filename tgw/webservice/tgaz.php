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

//routing of the uri elements
if (isset($path_parts[2])) {
  $service = $path_parts[2];

  if ($service == "service-info") {
    get_service_info_json($conn);

  } elseif ($service == "placename") {

    $fmt = "json";  //default

    if (isset($path_parts[3])) {
      if ($path_parts[3] == 'json' || $path_parts[3] == 'xml' || $path_parts[3] == 'rdf') {
        get_placename($conn, $path_parts[3], $path_parts[4]);  // FIXME verify exists
      } else {
        get_placename($conn, $fmt, $path_parts[3]);
      }
    } else {

      if (isset($_GET["id"]))  {
        if (isset($_GET["fmt"])) {
          $fmt = $_GET["fmt"];
        }
        get_placename($conn, $fmt, $_GET["id"]);
      } elseif (isset($_GET["n"])) {
        if (isset($_GET["yr"])) {
          search_placename($conn, $_GET["n"], $_GET["yr"]);
        } else {
          search_placename($conn, $_GET["n"], null);
        }
      } else {
        tlog(E_ERROR, "Id or Name not in request.");
        mysqli_close($conn);
        exit();
      }
    }
  } else if ($service == "featuretype") {
    ;
  } else  {
    tlog(E_ERROR, "Unknown service : " . $service);
  }
}

mysqli_close($conn);

?>