<?php
require ("../../CONNECTION_INFO.inc");
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

// echo "<br /> $requrl :: $url_path";

//use "explode" to parse
// e.g.: $request_parts = explode('/', $_GET['url']);
// or:           $parts = explode('/', $_SERVER['REQUEST_URI']);
//

$path_parts = explode('/', $url_path);
// echo $path_parts[1];

//routing of the uri elements
if (isset($path_parts[3])) {
  $service = $path_parts[3];

  if ($service == "service-info") {
    get_service_info_json($conn);

  } elseif ($service == "placename") {

// echo "<hr>$service";

    $fmt = "html";  //default

    if (isset($path_parts[4])) {
      if ($path_parts[4] == 'json' || $path_parts[4] == 'xml' || $path_parts[4] == 'rdf' || $path_parts[4] == 'html') {
        get_placename($conn, $path_parts[4], $path_parts[5]);  // FIXME verify exists
      } else {
        get_placename($conn, $fmt, $path_parts[4]);
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
//  ok this must be the trouble
//  indeed the
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
