<?php

// THIS IS JUST A PLACEHOLDER, BUT WITH A PROTYPE FOR PAGINATION

function search_to_html($pns, $name_key, $year_key, $src_key, $ftyp_key, $pof_key, $pg, $total) {

    $t = "<!DOCTYPE html>\n"
      . "<html>\n"
      . "<body>\n";

     // introductory remarks here

     // pagination links

//  HTML_SEARCH_HITS - this is number of pen per page defined in search.php [should have a better name]

     $pg_count = (int) ($total /  HTML_SEARCH_HITS);  // without remainder
     //echo "<p>==== page count is: " . $pg_count . "</p>";

     $item_start_num = 1 + HTML_SEARCH_HITS * ($pg - 1);
     $item_end_num = $item_start_num + HTML_SEARCH_HITS - 1;

     $pg_nav = "<p> Results " . $item_start_num . "-" . $item_end_num . " of " . $total . " &nbsp;&nbsp;&nbsp;&nbsp; go to results page " ;

     if ( $pg > 1 ) {
       $pg_nav .= "<a href=\"" . BASE_URL . "/placename?fmt=html&pg=" . ($pg - 1) . "\">back </a> ";
     }

     for ($i = 1; $i <= $pg_count; $i++) {
        if ($i == $pg) {
          $pg_nav .= "&nbsp;<b>$i</b> ";
        } else {
          $pg_nav .= "&nbsp;<a href=\"" . BASE_URL . "/placename?$name_key&fmt=html";
          if ( $year_key ) { $pg_nav .= "&yr=" . $year_key; }
          if ( $src_key ) { $pg_nav .= "&src=" . $src_key; }
          if ( $ftyp_key ) { $pg_nav .= "&ftyp=" . $ftyp_key; }
          if ( $pof_key ) { $pg_nav .= "&pof=" . $pof_key; }

          $pg_nav .="&pg=" . $i . "\"> $i </a> ";
        }
     }

     if ( $pg < $pg_count ) {
       $pg_nav .= " &nbsp;<a href=\"" . BASE_URL . "/placename?fmt=html&pg=" . ($pg + 1) . "\"> next </a> ";
     }


     $t .= $pg_nav . "</p>";



     $t .= "<div class=\"results\">";

    foreach ($pns as $pn) {
    $t .= "    <dl class=\"pn\">" . $pn['sys_id']
       .  "        <dt class=\"pnt\"></dt><dd class=\"pnd\"></dd>\n"
       // etc.
       . "</dl>\n";
    }

    $t .= "</div></body>\n"
       .  "</html>";


    header('Content-Type: text/html; charset=utf-8');
    echo $t;
}

?>
