<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . dirname(dirname(__FILE__))
  . PATH_SEPARATOR . dirname(dirname(__FILE__)).'/lib');

require_once 'konstrukt/konstrukt.inc.php';
set_error_handler('k_exceptions_error_handler');
spl_autoload_register('k_autoload');

if (is_file(dirname(__FILE__) . '/local.inc.php')) {
  require_once dirname(__FILE__) . '/local.inc.php';
} else {
  require_once dirname(__FILE__) . '/default.inc.php';
}

