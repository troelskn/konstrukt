<?php
ini_set('include_path',
  ini_get('include_path')
  .PATH_SEPARATOR.dirname(dirname(__FILE__))."/lib"
);

require_once('../../std.inc.php');

//////////////////////////////////////////////////////////////////////////////
$application = new Root();

$application->registry->registerConstructor('database', create_function(
  '$className, $args, $registry',
  '
  $db = new $className("sqlite:../blog.sqlite", "root", "");
  $schema = file_get_contents("../blog.ddl");
  try {
    $db->query($schema);
  } catch (PDOException $ex) { }
  return $db;
  '
));

$application->registry->registerConstructor('table:blogentries', create_function(
  '$className, $args, $registry',
  'return new TableGateway("blogentries", $registry->get("database"));'
));

$application->dispatch();

