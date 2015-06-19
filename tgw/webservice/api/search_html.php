<?php

//  order is critical  not yet reading $pg and $total correctly

function search_to_html($pns, $name_key, $year_key, $src_key, $ftype_key, $pof_key, $pg, $total) {

// function to strip off repeated wildcards %

    $stripwild = substr($name_key, 0, -1);
    $name_key = $stripwild;

// ck !vars for echo statement
  
    $ckcount = count($pns);
 
     if ($name_key != ''){
      $echo_nm = ' name=<b>'.$name_key .'</b>';
     }
     else {$echo_nm='';}

     if ($year_key != ''){
      $echo_yr = ' yr=<b>'.$year_key .'</b>';
     }
     else {$echo_yr='';}

     if ($ftype_key != ''){
      $echo_ft = ' type=<b>'.$ftype_key .'</b>';
     }
     else {$echo_ft='';}

     if ($pof_key != ''){      
      $echo_pof = ' parent=<b>'.$pof_key .'</b>';
     }
     else {$echo_pof='';}

     if ($src_key != ''){      
      $echo_src = ' src=<b>'.$src_key .'</b>';
     }
     else {$echo_src='';}

     if ($pg == 1) {
      $limit_start = 0;
     }
     else {
     $limit_start = (ITEMS_PER_PAGE * ($pg - 1));
     }
  
    $top = "<!DOCTYPE html>\n"
      . "<html>\n<head>\n";

    $top .="<title>TGAZ - Query Results</title>\n"
       . "<meta charset='utf-8' />\n"
       . "<link rel='icon' href='tgaz/graf/tgaz.ico'>\n"
       . "<link rel='stylesheet' href='/tgaz/css/api.css'/>\n"
       . "<link rel='stylesheet' href='http://cdn.leafletjs.com/leaflet-0.7/leaflet.css'/>\n"
       . "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>\n"
       . "<script src='http://static.outofedenwalk.com/ooe/vendor/leaflet/oms.min.js'></script>\n"
       . "<script src='http://cdn.leafletjs.com/leaflet-0.7/leaflet.js'></script>\n";

    $top .= "</head>\n<body>\n";
    $top .= "<div class=\"wrap\">";
//    $top .= $ckcount;
//    $top .= "\n total var: ";
//    $top .= $total . " pg var: $pg  limit-start: $limit_start";

    $top .= "<div class=\"banner\">"      
       . "<a href='/tgaz/'><img src='/tgaz/graf/TGAZ_API_icon.png'></a> :: search results :" .$echo_nm .$echo_yr .$echo_ft .$echo_pof .$echo_src . "</div><!-- end \"banner\" -->\n";

//  pagination section

//  first check for remainder with modulus, then set $pg_count
     $pg_calc = (int) ($total /  ITEMS_PER_PAGE);  // without remainder
     if ($pg_calc % ITEMS_PER_PAGE != 0){
      $pg_count = ($pg_calc + 1);
     }
     else {$pg_count = $pg_calc;}
 
     if ($pg == 0) {
      $item_start_num = 1; 
     }
     else {
     $item_start_num = (1 + (ITEMS_PER_PAGE * ($pg - 1)));
     }
   
//     if ($total < ITEMS_PER_PAGE) {

     if ($pg < $pg_count) {
     $item_end_num = ($item_start_num + ITEMS_PER_PAGE -1);
     }
     else {
     $item_end_num = $total;
     }

//     $item_start_num = 1;
//     $item_end_num = $item_start_num + ITEMS_PER_PAGE - 1;

     $pg_nav = "<p>Results: " . $item_start_num . "-" . $item_end_num . " of " . $total . " total hits &nbsp;&nbsp;" ;

// only show back link if there is more than one page of results

// removing BACK link and NEXT links because of too many key complications


// only show page links if the total is higher than ITEMS_PER_PAGE
     if ( $pg_count > 1 ) { 
      for ($i = 1; $i <= $pg_count; $i++) {
        if ($i == $pg) {
          $pg_nav .= "&nbsp;<b>$i</b> ";
        } else {
          $pg_nav .= "&nbsp;<a href=\"" . BASE_URL . "/placename?fmt=html&n=$name_key";  // changed $name_key to n=$name_key
          if ( $year_key ) { $pg_nav .= "&yr=" . $year_key; }
          if ( $src_key ) { $pg_nav .= "&src=" . $src_key; }
          if ( $ftype_key ) { $pg_nav .= "&ftyp=" . $ftype_key; }
          if ( $pof_key ) { $pg_nav .= "&pof=" . $pof_key; }
//  changed to $pg from $pg_key
          $pg_nav .="&pg=" . $i . "\"> $i </a> ";
        }
      }
     }

   // end of if ($pg > 1) wrapper

    $pages .= "<div class=\"pages\">" . $pg_nav . "</div><!-- end \"pages\" -->\n";

   

//  end pagination section


    $t .= "<div class=\"webmap_area\">"      
       . "<div id=\"map\" style=\"width: 600px; height: 260px\"></div>"
       . "</div><!-- end \"webmap_area\" -->\n";

    $max_x = 0;
    $max_y = 0;
    $min_x = 180;
    $min_y = 180;
// map setup - features  and get the max x and y
    $t .= "<script>\n";

    $t .= "var mapxy = [";
    foreach ($pns as $json) {

     if (($json['y_coord'] !=0) and ($json['x_coord'] !=0)) {

    $t .= "["
       . $json['y_coord'] .  ", "
       . $json['x_coord'] .  ", \"<a href='/placename/"
       . $json['sys_id'] .  "'>"
       . $json['name'] .  " "
       . $json['transcription'] .  " ("
       . $json['beg_yr'] .  " to "
       . $json['end_yr'] .  ")</a>\"],";
      $x = floatval($json['x_coord']);
       if ( $x > $max_x ) {
       $max_x = ($x + 0.2);
      }
      $y = floatval($json['y_coord']);
       if ( $y > $max_y ) {
       $max_y = ($y + 0.2);
      }
      $x2 = floatval($json['x_coord']);
       if ( $x2 < $min_x ) {
       $min_x = ($x2 - 0.2);
      }
      $y2 = floatval($json['y_coord']);
       if ( $y < $min_y ) {
       $min_y = ($y2 - 0.2);
      }
     } //ck x and y !=0 
    }

    $t .= "];\n"
       . "var southWest = L.latLng(" . $min_y . ", " . $min_x . "),
    northEast = L.latLng(" . $max_y . ", " . $max_x . "),
    bounds = L.latLngBounds(southWest, northEast);

var map = L.map('map').fitBounds(bounds);

        mapLink = '<a href=\"http://www.openstreetmap.org/copyright\">OSM</a>';
    L.tileLayer(
       'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'basemap &copy;' + mapLink + ' code &raquo; <a href=\"http://www.dbr.nu/bio\" target=\"_new\">Lex Berman</a>',
        maxZoom: 18,
    }).addTo(map);

// changed L.Marker to L.circleMarker, added ,{def}

var popup = L.popup();
var marker, i
for (i = 0; i < mapxy.length; i++){
    var markerLocation = new L.LatLng(mapxy[i][0], mapxy[i][1]);
    marker = new L.Marker(markerLocation, {
                        opacity: 0.5
                    });
    marker.bindPopup(mapxy[i][2], mapxy[i][3]);
    marker.on('mouseover', function(e) {
        popup.setLatLng(e.latlng)
        this.openPopup();
    });
    map.addLayer(marker);
};

    </script>";

// results loop  section

    $t .= "\n<div class=\"results\"> \n";
    $itck = 0;
    foreach ($pns as $htmloop) {
//     if ($htmloop === reset($pns))
//       {$t .= "FIRST ELEMENT is ". $htmloop['sys_id']  . "<p>";}
    $itck++;
    $ckcount = count($pns);
    $t .= " <dl class=\"pn\""  
       . "   <dt class=\"pnt\"><a href=\"placename/" . $htmloop['sys_id'] . "\">". $htmloop['sys_id']  ."</a>  <b>" . $htmloop['name'] . "</b> </dt> \n"
       . "    <dd class=\"pnd\">"
       . " (" . $htmloop['transcription']  . ") begin "
       . $htmloop['beg_yr'] 
       . " CE end " . $htmloop['end_yr']
       . " CE [" . $htmloop['x_coord'] . ", " . $htmloop['y_coord']
       . "]   </dd>\n";
    $t .= " </dl>\n"; 
    }
  
    $t .= "</div>\n<!-- end \"results\" --><p />"; 

    $t .= "<div class=\"license\"><license>\n"
       .  "License:  <a href=\"" . LIC_URI . "\" target=\"_blank\"><license>" . LIC ."</license></a>\n"
       .  "<br>Generated by the <a href=\"/tgaz\"><system>Temporal Gazetteer Service</system></a><br />\n"
       .  "</license></div>";

    $t .= "</div>\n<!-- end \"wrap\" -->"
       .  "</body>\n"
       .  "</html>";

    header('Content-Type: text/html; charset=utf-8');
    echo $top;
    echo $pages;
    echo $t;
}

?>
