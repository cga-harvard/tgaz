<?php
$db_addr ='localhost';
$db_user = 'DB_USERNAME'; 
$db_pass = 'DB_PASSWORD';
$db_name = 'DB_NAME';
define('BASE_URL', 'http://APPLICATION-URL/dir'); // no trailing slash
// URL_BASE_POS assumes BASE_URL in /dir at webroot, add 1 for each additional sub-dir 
define('URL_BASE_POS', 0);  
define('LIC', 'CC BY-NC 4.0'); // overall license for entire gazetteer
define('LIC_URI', 'http://creativecommons.org/licenses/by-nc/4.0/');  // link to license
//  you should move this file and adjust path in tgaz.php & paths.php
?>
