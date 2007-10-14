<?php
ini_set('include_path',
  ini_get('include_path')
  .PATH_SEPARATOR.dirname(dirname(__FILE__))."/lib"
);

require_once '../std.inc.php';

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
