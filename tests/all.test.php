<?php
require_once 'support/simpletest.inc.php';
$files = Array();
foreach (glob("*.test.php") as $file) {
  if ($file != "all.test.php") {
    include $file;
    $files[] = realpath($file);
  }
}
simpletest_autorun($files);
