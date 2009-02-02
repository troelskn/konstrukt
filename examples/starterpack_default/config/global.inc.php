<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(
  get_include_path()
  . PATH_SEPARATOR . dirname(dirname(__FILE__))
  . PATH_SEPARATOR . dirname(dirname(__FILE__)).'/lib');

require_once 'konstrukt/trunk/lib/konstrukt/konstrukt.inc.php';
require_once 'smarty/libs/Smarty.class.php';
require_once dirname(__FILE__) . '/wiring.inc.php';

date_default_timezone_set('Europe/Paris');
set_error_handler('k_exceptions_error_handler');
spl_autoload_register('k_autoload');