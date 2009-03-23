#!/usr/bin/env php
<?php
set_include_path(
  get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . PATH_SEPARATOR . realpath(dirname(__FILE__)) . '/../lib');

require_once 'includes/pepper.php';
require_once 'phemto/phemto.php';
$discoverer = new Pepper_Discoverer();

$discoverer->readFile('lib/konstrukt/konstrukt.inc.php');
$discoverer->readFile('lib/konstrukt/adapter.inc.php');
$discoverer->readFile('lib/konstrukt/charset.inc.php');

// $discoverer->readFile('lib/konstrukt/virtualbrowser.inc.php');
// $discoverer->readFile('lib/konstrukt/logging.inc.php');

if (in_array('html', $_SERVER['argv']) || in_array('html+tracking', $_SERVER['argv'])) {
  // write api docs
  echo "Creating API docs\n\n";
  $writer = new Pepper_Transformer(new Pepper_StreamWriter(fopen(getcwd() . "/docs/apidocs.html", "w+")), "html", in_array('html+tracking', $_SERVER['argv']) ? 'tracking.tpl.html' : null);
  foreach ($discoverer->getClasses() as $class) {
    echo "Processing: ".$class->getName()."\n";
    $writer->processClass($class);
  }
  $writer->finalize();
  echo "\n";
}

if (in_array('diagram', $_SERVER['argv'])) {
  // write class diagram
  echo "Creating class diagram\n\n";
  $writer = new Pepper_Transformer(new Pepper_GraphVizWriter(fopen(getcwd() . "/docs/classdiagram.png", "w+"), 'neato'), "dot");
  foreach ($discoverer->getClasses() as $class) {
    echo "Processing: ".$class->getName()."\n";
    $writer->processClass($class);
  }
  $writer->finalize();
  echo "\n";
}

if (count($_SERVER['argv']) == 1) {
  echo "Usage: " . $_SERVER['argv'][0] . " TARGET\n";
  echo "  TARGET can be one or both of:\n";
  echo "    html           Generates html apidocs\n";
  echo "    html+tracking  Generates html apidocs with tracking-code\n";
  echo "    diagram        Generates classdiagram, uysing GraphViz\n";
}