#!/usr/bin/env php
<?php
/**
 * Recursivley find files in folder, matching regular expression
 */
function find_files($path, $pattern) {
  $search = array($path);
  $files = array();
  for ($ii = 0; $ii < count($search); ++$ii) {
    $dir = dir($search[$ii]);
    while (false !== ($entry = $dir->read())) {
      $fullname = $search[$ii] . DIRECTORY_SEPARATOR . $entry;
      if (is_file($fullname) && preg_match($pattern, $entry)) {
        $files[] = $fullname;
      } elseif ($entry != '.' && $entry != '..' && is_dir($fullname)) {
        $search[] = $fullname;
      }
    }
    $dir->close();
  }
  return $files;
}
$is_verbose = false;
foreach ($argv as $arg) {
  $is_verbose = $is_verbose || $arg == '--verbose' || $arg == '-v';
}
function verbose($str) {
  global $is_verbose;
  if ($is_verbose) {
    echo $str;
  }
}
$passes = 0;
$failures = 0;
$runs = 0;
foreach (find_files(realpath(dirname(__FILE__) . '/..'), '/test\.php$/') as $filename) {
  chdir(dirname($filename));
  $path = realpath(dirname(__FILE__) . '/../lib') . PATH_SEPARATOR . ini_get("include_path");
  $xml = shell_exec('php -d include_path=' . escapeshellarg($path) . ' ' . escapeshellarg(basename($filename)) . ' -x');
  verbose("-------------------------------------------\n");
  verbose("Running suite: " . $filename . "\n");
  $doc = new DomDocument();
  if (@$doc->loadXml($xml)) {
    $q = new DomXpath($doc);
    $passes += $q->query('//pass')->length;
    $failures += $q->query('//fail')->length;
    foreach ($q->query('//fail') as $fail) {
      verbose($fail->nodeValue . "\n");
    }
    verbose($q->query('//pass')->length . " passes, ");
    verbose($q->query('//fail')->length . " failues" . "\n");
  } else {
    $failures += 1;
    verbose($xml);
  }
  $runs++;
}
verbose("===========================================\n");
if ($failures == 0) {
  echo "Done .. OK\n";
} else {
  echo "Done .. $runs tests completed with $passes passes and $failures failues.\n";
  exit(1);
}
