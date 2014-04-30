<?php

// consider supporting different languages
function get_service_info_json($conn) {

  $pn_query = "SELECT count(*) as count from placename;";
  $pn_result = mysqli_query($conn, $pn_query) or die("{ \"Error\" : \"" . mysqli_error() . "\"}");   //FIX ME
  $pn = mysqli_fetch_array($pn_result, MYSQLI_ASSOC);
  mysqli_free_result($pn_result);


  $sinfo = array(
    'service name'          => 'China Historical GIS, Harvard University and Fudan University',
    'version no.'           => '5.0',
    'description'           => 'The China Historical Geographic Information System, CHGIS, project was launched '
                                . 'in January 2001 to establish a database of populated places and historical '
                                . 'administrative units for the period of Chinese history between 221 BCE and 1911 CE. '
                                . 'CHGIS provides a base GIS platform for researchers to use in spatial analysis, '
                                . 'temporal statistical modeling, and representation of selected historical units as '
                                . 'digital maps.',
     'size'                 =>  $pn['count'] . ' placename records available',
     'funding from'         =>  array('Henry Luce Foundation', 'National Endowment for the Humanities'),
     'license'              =>  'c. 2014'

  );

  header('Content-Type: text/json; charset=utf-8');
  echo json_encode($sinfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

}

?>