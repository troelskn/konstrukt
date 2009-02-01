#!/usr/bin/env php
<?php
set_include_path(
  get_include_path() . PATH_SEPARATOR . dirname(__FILE__));

require_once 'includes/pepper.php';
require_once 'phemto/phemto.php';
$discoverer = new Pepper_Discoverer();

$discoverer->readFile('lib/k2.inc.php');
$discoverer->readFile('lib/adapter.inc.php');
$discoverer->readFile('lib/charset.inc.php');

// $discoverer->readFile('lib/virtualbrowser.inc.php');
// $discoverer->readFile('lib/logging.inc.php');

// write api docs
echo "Creating API docs\n\n";
$writer = new Pepper_Transformer(new Pepper_StreamWriter(fopen(getcwd() . "/docs/apidocs.html", "w+")), "html");
foreach ($discoverer->getClasses() as $class) {
  echo "Processing: ".$class->getName()."\n";
  $writer->processClass($class);
}
$writer->finalize();
echo "\n";

// write class diagram
echo "Creating class diagram\n\n";
$writer = new Pepper_Transformer(new Pepper_GraphVizWriter(fopen(getcwd() . "/docs/classdiagram.png", "w+"), 'neato'), "dot");
foreach ($discoverer->getClasses() as $class) {
  echo "Processing: ".$class->getName()."\n";
  $writer->processClass($class);
}
$writer->finalize();
echo "\n";
