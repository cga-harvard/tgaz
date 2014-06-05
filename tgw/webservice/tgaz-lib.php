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

/*
 *   response pages for exceptional conditions,
 *   e.g. not found
 */
function alt_response($http_code, $msg) {

  switch($http_code) {
    case 404 :
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        if (!$msg) { $msg = "The page that you have requested could not be found."; }
        echo "<p>$msg</p>";
        break;
    case 500 :
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        if (!$msg) { $msg = "The server encountered an unexpected condition which prevented it from fulfilling the request."; }
        echo "<p>$msg</p>";
        break;

  }
  exit();
}

function indent($n) {
  return str_repeat('  ', n);
}

function my_json_encode($var)
{

    switch (gettype($var)) {
        case 'boolean':
            return $var ? 'true' : 'false';

        case 'NULL':
            return 'null';

        case 'integer':
            return (int) $var;

        case 'double':
        case 'float':
            return  (float) $var;

        case 'string':
              return $var;

        case 'array':
           /*
            * As per JSON spec if any array key is not an integer
            * we must treat the the whole array as an object. We
            * also try to catch a sparsely populated associative
            * array with numeric keys here because some JS engines
            * will create an array with empty indexes up to
            * max_index which can cause memory issues and because
            * the keys, which may be relevant, will be remapped
            * otherwise.
            *
            * As per the ECMA and JSON specification an object may
            * have any string as a property. Unfortunately due to
            * a hole in the ECMA specification if the key is a
            * ECMA reserved word or starts with a digit the
            * parameter is only accessible using ECMAScript's
            * bracket notation.
            */

            // treat as a JSON object
            if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                $properties = array_map(array($this, 'name_value'),
                                        array_keys($var),
                                        array_values($var));

//                foreach($properties as $property) {
//                    if(Services_JSON::isError($property)) {
//                        return $property;
//                    }
//                }

                return '{' . join(',', $properties) . '}';
            }

            // treat it like a regular array
            $elements = array_map(array($this, 'my_json_encode'), $var);

//            foreach($elements as $element) {
//                if(Services_JSON::isError($element)) {
//                    return $element;
//                }
//            }

            return '[' . join(',', $elements) . ']';

        case 'object':

            // support toJSON methods.
/*            if (($this->use & SERVICES_JSON_USE_TO_JSON) && method_exists($var, 'toJSON')) {
                // this may end up allowing unlimited recursion
                // so we check the return value to make sure it's not got the same method.
                $recode = $var->toJSON();

                if (method_exists($recode, 'toJSON')) {

                    return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new Services_JSON_Error(class_name($var).
                        " toJSON returned an object with a toJSON method.");

                }

                return $this->_encode( $recode );
            }
*/
            $vars = get_object_vars($var);

            $properties = array_map(array($this, 'name_value'),
                                    array_keys($vars),
                                    array_values($vars));

/*            foreach($properties as $property) {
                if(Services_JSON::isError($property)) {
                    return $property;
                }
            }
*/
            return '{' . join(',', $properties) . '}';

        default:
            return '';
//            return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
//                ? 'null'
//                : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
    }
}

/**
* array-walking function for use in generating JSON-formatted name-value pairs
*
* @param    string  $name   name of key to use
* @param    mixed   $value  reference to an array element to be encoded
*
* @return   string  JSON-formatted name-value pair, like '"name":value'
* @access   private
*/
function name_value($name, $value)
{
    $encoded_value = my_json_encode($value);

//    if(Services_JSON::isError($encoded_value)) {
//        return $encoded_value;
//    }

    return my_json_encode(strval($name)) . ':' . $encoded_value;
}


?>