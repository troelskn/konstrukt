<?php
$GLOBALS['simpletest_configuration'] = Array();
$GLOBALS['simpletest_configuration']['test_server_url'] = "http://localhost/konstrukt/trunk/examples/";
set_include_path(get_include_path() . PATH_SEPARATOR . "/home/tkn/src/");
if (is_file(dirname(__FILE__).'/config.local.php')) {
  include dirname(__FILE__).'/config.local.php';
}
