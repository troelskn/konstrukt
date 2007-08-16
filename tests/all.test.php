<?php
require_once 'support/simpletest.inc.php';
$files = glob("*.test.php");
foreach ($files as $file) {
  if ($file != "all.test.php") {
    include $file;
  }
}
simpletest_autorun($files);
