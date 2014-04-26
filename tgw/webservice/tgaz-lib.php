<?php


/*
 *  FIXME - add config item to turn on/off logging
 */
function tlog($logtype, $msg) {
  if (1) {
    $line  = "[ " . date('c') . " ] ";

    switch($logtype) {
      case E_ERROR :
        $line .= "ERROR   - "; break;
      case E_WARNING :
        $line .= "WARNING - "; break;
      case E_NOTICE :
        $line .= "INFO    - "; break;
      default :
        $line .= "DEBUG   - ";
    }

    $line .=  $msg . "\n";

    return error_log($line, 3, "tgaz.log");
  } else {
    return 0;
  }
}

function indent($n) {
  return str_repeat('  ', n);
}

?>