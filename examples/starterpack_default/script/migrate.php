#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../config/global.inc.php');
$file_log = (dirname(__FILE__) . '/../var/migrations.log');
$dir_migrations = (dirname(__FILE__) . '/migrations');

if (!file_exists($file_log)) {
  touch($file_log);
}
if (!is_writable($file_log)) {
  throw new Exception("logfile isn't writable");
}
$logentries = array_filter(explode("\n", file_get_contents($file_log)));

$oustanding = array();
$d = dir($dir_migrations);
while (false !== ($entry = $d->read())) {
  $fullname = $dir_migrations . DIRECTORY_SEPARATOR . $entry;
  if (is_file($fullname) && is_executable($fullname) && !in_array($entry, $logentries)) {
    $oustanding[$entry] = $fullname;
  }
}
$d->close();
ksort($oustanding);
if (count($oustanding) == 0) {
  echo "Nothing to do.\n";
}
foreach ($oustanding as $entry => $fullname) {
  echo "Running migration: " . $entry ."\n";
  system($fullname, $retval);
  if ($retval === 0) {
    echo "OK.\n";
  } else {
    echo "External command failed with exit code ($retval).\n";
    exit($retval);
  }
  error_log($entry . "\n", 3, $file_log );
}
