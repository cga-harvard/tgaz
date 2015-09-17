<?php
require ("../CONNECTION_INFO.inc.php");
require ("./tgaz-lib.php");
require ("./placename.php");
#require ("./featuretype.php");
require ("./service-info.php");
require ("./search.php");
require ("./search_html.php");

// use persistent connection with "p:" prepended to the hostname
// echo "$db_addr $db_user<hr>";
$conn = mysqli_connect("p:$db_addr", "$db_user", "$db_pass", "$db_name");

$conn->set_charset("utf8");

if (!$conn) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

// echo 'Success..... ' . mysqli_get_host_info($conn);
// echo "<br />Charset... " . mysqli_get_charset($conn)->charset;

// parsing of the uri

$requrl = "$_SERVER[REQUEST_URI]" ;  // e.g. /placename.php?fmt=json&id=hvd_34376
$url_path = parse_url( $requrl, PHP_URL_PATH);

// echo "<br /> $requrl :: $url_path <hr>";

//use "explode" to parse
// e.g.: $request_parts = explode('/', $_GET['url']);
// or:           $parts = explode('/', $_SERVER['REQUEST_URI']);
//

$path_parts = explode('/', $url_path);
// echo $path_parts[2];

//routing of the uri elements


//  please note following settings related to the position in the path for the exploded elements
//  use the commented out echo $path_parts[2] above to determine the position of "placename" in the path
//  this number should be equal to the BASE_URL setting in CONNECTION-INFO plus the settings below  
//  for example if CONNECTION-INFO setting is: define('URL_BASE_POS', 0);   
//  then the setting 0 results in [URL_BASE_POS + 2] = placename position
//  it is also essential for the .htaccess at webroot to pass the query string to tgaz.php with the line:
//  RewriteRule ^tgaz\/placename(.*)$ /tgaz/api/tgaz.php
//  alter the .htaccess to find the codebase, like ^myfolder\/placename(.*)$ /myfolder/path/tgaz/api/tgaz.php

if (isset($path_parts[URL_BASE_POS + 2])) {
  $service = $path_parts[URL_BASE_POS + 2];


  if ($service == "service-info") {
    get_service_info_json($conn);

  } elseif ($service == "placename") {

// echo "<hr>$service";

    $fmt = "html";  //default

//  based on the setting of the "Placename" position, renumber the following elements
//  for example if the path from webroot is /tgaz/placename/json/hvd_9722
//  and placename is path_parts[2]  then json = 3  and hvd_9722 = 4
//  DEV = 4 (except one case of 5)   AWS = 3 (except one case of 4)
//  rev to use URL_BASE_POS

    if (isset($path_parts[URL_BASE_POS + 3])) {
      if ($path_parts[URL_BASE_POS + 3] == 'json' || $path_parts[URL_BASE_POS + 3] == 'xml' || $path_parts[URL_BASE_POS + 3] == 'rdf' ||
          $path_parts[URL_BASE_POS + 3] == 'html' || $path_parts[URL_BASE_POS + 3] == 'esgar') {
        get_placename($conn, $path_parts[URL_BASE_POS + 3], $path_parts[URL_BASE_POS + 4]);  // FIXME verify exists
      } else {
        get_placename($conn, $fmt, $path_parts[URL_BASE_POS + 3]);
      }

    } else {
      if (isset($_GET["fmt"])) {
        $fmt = $_GET["fmt"];
      }

      if (isset($_GET["id"]))  {
        get_placename($conn, $fmt, $_GET["id"]);
      } elseif (isset($_GET["n"])) {
        $n = $_GET["n"];
        $yr  = (isset($_GET["yr"]) ? $_GET["yr"] : null);
        $src = (isset($_GET["src"]) ? $_GET["src"] : null);
        $ftyp = (isset($_GET["ftyp"]) ? $_GET["ftyp"] : null);

//  altered for pagination 2014-10-09

        $p = (isset($_GET["p"]) ? $_GET["p"] : null);
        $t = (isset($_GET["total"]) ? $_GET["total"] : null);
        $pg = 0;
        if ($fmt == 'html') {
            $pg = (isset($_GET["pg"]) ? $_GET["pg"] : 1);
        }
        search_placename($conn, $n, $yr, $fmt, $src, $ftyp, $p, $pg, $t);
      } else {
        mysqli_close($conn);
        tlog(E_ERROR, "Invalid request: " . $url_path);
        alt_response(400, "CHGIS:  Invalid request.");
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
