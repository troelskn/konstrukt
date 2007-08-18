<?php
  /**
   * Simpletest bootstrap
   * Just include and put simpletest_autorun(__FILE__) at the end of the test-file
   */
if (is_file(dirname(__FILE__).'/config.default.php')) {
  include dirname(__FILE__).'/config.default.php';
 }

require_once 'simpletest/unit_tester.php';
require_once 'simpletest/web_tester.php';
require_once 'simpletest/reporter.php';
require_once 'simpletest/mock_objects.php';

class simpletest_autorun_TextReporter extends TextReporter
{
  var $is_verbose = FALSE;

  function simpletest_autorun_TextReporter($is_verbose = FALSE) {
    parent::TextReporter();
    $this->is_verbose = $is_verbose;
  }

  function paintMethodStart($test_name) {
    parent::paintMethodStart($test_name);
    if ($this->is_verbose) {
      $breadcrumb = $this->getTestList();
      array_shift($breadcrumb);
      echo "[".date("H:i:s")."] " . implode(" : ", $breadcrumb) . "\n";
    }
  }

  function paintSkip($message) {
    if ($this->is_verbose) {
      print "Skip: $message\n";
    }
  }
}

function simpletest_autorun($filename) {
  if (is_string($filename) && (realpath($_SERVER['SCRIPT_FILENAME']) != realpath($filename))) {
    return;
  }
  if (!is_array($filename)) {
    $filename = Array($filename);
  }
  error_reporting(E_ALL);
  if (SimpleReporter::inCli()) {
    $options = array();
    $arguments = array();
    foreach (array_slice(@$_SERVER['argv'], 1) as $arg) {
      if (preg_match('~^--([^=]+)=(.*)~', $arg, $reg)) {
        $options[$reg[1]] = $reg[2];
      } else if (preg_match('~^--([a-zA-Z0-9]+)$~', $arg, $reg)) {
        $options[$reg[1]] = TRUE;
      } else if (preg_match('~^-([a-zA-Z]+)$~', $arg, $reg)) {
        foreach (str_split($reg[1]) as $option) {
          $options[$option] = TRUE;
        }
      } else {
        $arguments[] = $arg;
      }
    }
    if (isset($options['help'])) {
      echo "Simpletest autorunner.\n";
      printf("Usage: php %s [-v|--verbose] [casename [testname]]", basename(@$_SERVER['argv'][0]));
      exit;
    }
    $casename = @$arguments[0];
    $testname = @$arguments[1];
  } else {
    $casename = @$_GET['c'];
    $testname = @$_GET['t'];
  }
  // set_time_limit(0);
  $test = new GroupTest("Automatic Test Runner");
  $testKlass = new ReflectionClass("SimpleTestCase");
  foreach (get_declared_classes() as $classname) {
    $klass = new ReflectionClass($classname);
    if ($klass->isSubclassOf($testKlass) && in_array($klass->getFileName(), $filename)) {
      $test->addTestCase(new $classname());
    }
  }
  if (SimpleReporter::inCli()) {
    $is_verbose = isset($options['v']) || isset($options['verbose']);
    $reporter = new simpletest_autorun_TextReporter($is_verbose);
    $time = microtime(TRUE);
    $result = $test->run(new SelectiveReporter($reporter, $casename, $testname));
    if ($is_verbose) {
      echo "Time taken: " . round(microtime(TRUE) - $time) . " sec\n";
    }
    exit($result ? 0 : 1);
  }
  $test->run(new SelectiveReporter(new HtmlReporter(), $casename, $testname));

}
