#!/usr/bin/php -qC
<?php
require_once('pepper.php');
$discoverer = new Pepper_Discoverer();
require_once("../../examples/std.inc.php");
$discoverer->readFile("../../lib/k/icontext.php");
$discoverer->readFile("../../lib/k/component.php");
$discoverer->readFile("../../lib/k/controller.php");
$discoverer->readFile("../../lib/k/dispatcher.php");
$discoverer->readFile("../../lib/k/http/request.php");
$discoverer->readFile("../../lib/k/http");
$discoverer->readFile("../../lib/k/anonymous.php");
$discoverer->readFile("../../lib/k/document.php");
$discoverer->readFile("../../lib/k/formbehaviour.php");
$discoverer->readFile("../../lib/k/fieldcollection.php");
$discoverer->readFile("../../lib/k/field.php");
$discoverer->readFile("../../lib/k/validator.php");
$discoverer->readFile("../../lib/k/memory.php");
$discoverer->readFile("../../lib/k/datalist.php");
$discoverer->readFile("../../lib/k/urlstate.php");
$discoverer->readFile("../../lib/k/urlbuilder.php");
$discoverer->readFile("../../lib/k/urlstatesource.php");
$discoverer->readFile("../../lib/k/registry.php");
$discoverer->readFile("../../lib/k/debugger.php");
$discoverer->readFile("../../lib/k/");

// write api docs
echo "Creating API docs\n\n";
$writer = new Pepper_Transformer(new Pepper_StreamWriter(fopen("../../docs/apidocs.html", "w+")), "html");
foreach ($discoverer->getClasses() as $class) {
  echo "Processing: ".$class->getName()."\n";
  $writer->processClass($class);
}
$writer->finalize();
echo "\n";

// write class diagram
echo "Creating class diagram\n\n";
$writer = new Pepper_Transformer(new Pepper_GraphVizWriter(fopen("../../docs/classdiagram.png", "w+")), "dot");
foreach ($discoverer->getClasses() as $class) {
  echo "Processing: ".$class->getName()."\n";
  $writer->processClass($class);
}
$writer->finalize();
echo "\n";
