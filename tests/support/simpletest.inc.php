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
  var $begin_time = NULL;

  function simpletest_autorun_TextReporter($is_verbose = FALSE) {
    parent::TextReporter();
    $this->is_verbose = $is_verbose;
  }

  function paintGroupStart($test_name, $size) {
    parent::paintGroupStart($test_name, $size);
    if (is_null($this->begin_time)) {
      $this->begin_time = microtime(TRUE);
    }
  }

  function paintMethodStart($test_name) {
    parent::paintMethodStart($test_name);
    if ($this->is_verbose) {
      $breadcrumb = $this->getTestList();
      array_shift($breadcrumb);
      echo "[".date("H:i:s")."] " . implode(" ", $breadcrumb) . "\n";
      if (ob_get_level() > 0) {
        ob_flush();
      }
    }
  }

  function paintSkip($message) {
    if ($this->is_verbose) {
      print "Skip: $message\n";
      if (ob_get_level() > 0) {
        ob_flush();
      }
    }
  }

  function paintFooter($test_name) {
    if ($this->getFailCount() + $this->getExceptionCount() == 0) {
      print "OK\n";
    } else {
      print "FAILURES!!!\n";
    }
    $print = "Test cases run: " . $this->getTestCaseProgress() .
      "/" . $this->getTestCaseCount() .
      ", Passes: " . $this->getPassCount() .
      ", Failures: " . $this->getFailCount() .
      ", Exceptions: " . $this->getExceptionCount();
    if ($this->is_verbose) {
      $print .= ", Time taken: " . round(microtime(TRUE) - $this->begin_time) . " sec";
    }
    print $print . "\n";
  }
}

class simpletest_autorun_HtmlReporter extends HtmlReporter
{
  var $is_verbose = FALSE;
  var $begin_time = NULL;

  function simpletest_autorun_HtmlReporter($is_verbose = FALSE) {
    parent::HtmlReporter();
    $this->is_verbose = $is_verbose;
  }

  function paintGroupStart($test_name, $size) {
    parent::paintGroupStart($test_name, $size);
    if (is_null($this->begin_time)) {
      $this->begin_time = microtime(TRUE);
    }
  }

  function paintPass($message) {
    parent::paintPass($message);
    if ($this->is_verbose) {
      print "<span class=\"pass\">Pass</span>: ";
      $breadcrumb = $this->getTestList();
      array_shift($breadcrumb);
      $case = $breadcrumb[0];
      $test = $breadcrumb[1];
      print "<a href=\"?c=".rawurlencode($case)."&verbose=1\">" . $case . "</a>";
      print "-&gt;";
      print "<a href=\"?c=".rawurlencode($case)."&t=".rawurlencode($test)."&verbose=1\">" . $test . "</a>";
      print "-&gt;" . htmlspecialchars($message) . "<br />\n";
      if (ob_get_level() > 0) {
        ob_flush();
      }
    }
  }

  function paintFail($message) {
    if ($this->is_verbose) {
      $this->_fails++;
      print "<span class=\"fail\">Fail</span>: ";
      $breadcrumb = $this->getTestList();
      array_shift($breadcrumb);
      $case = $breadcrumb[0];
      $test = $breadcrumb[1];
      print "<a href=\"?c=".rawurlencode($case)."&verbose=1\">" . $case . "</a>";
      print "-&gt;";
      print "<a href=\"?c=".rawurlencode($case)."&t=".rawurlencode($test)."&verbose=1\">" . $test . "</a>";
      print "-&gt;" . htmlspecialchars($message) . "<br />\n";
      if (ob_get_level() > 0) {
        ob_flush();
      }
    } else {
      parent::paintFail($message);
    }
  }

  function paintFooter($test_name) {
    $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
    print "<div style=\"";
    print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
    print "\">";
    print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
    print " test cases complete:\n";
    print "<strong>" . $this->getPassCount() . "</strong> passes, ";
    print "<strong>" . $this->getFailCount() . "</strong> fails and ";
    print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
    if ($this->is_verbose) {
      print " Time taken: <strong>" . round(microtime(TRUE) - $this->begin_time) . "</strong> sec.";
    } else {
      print " [<a href=\"?verbose=1\">verbose</a>]";
    }
    print "</div>\n";
    print "</body>\n</html>\n";
  }

  function _getCss() {
    return parent::_getCss() . ' .pass { color: green; }';
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
      print "Simpletest autorunner.\n";
      printf("Usage: php %s [-v|--verbose] [casename [testname]]", basename(@$_SERVER['argv'][0]));
      print "\n";
      exit;
    }
    $casename = @$arguments[0];
    $testname = @$arguments[1];
  } else {
    $casename = @$_GET['c'];
    $testname = @$_GET['t'];
    $options = $_GET;
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
  $is_verbose = isset($options['v']) || isset($options['verbose']);
  if (SimpleReporter::inCli()) {
    $reporter = new simpletest_autorun_TextReporter($is_verbose);
  } else {
    $reporter = new simpletest_autorun_HtmlReporter($is_verbose);
  }
  $result = $test->run(new SelectiveReporter($reporter, $casename, $testname));
  exit($result ? 0 : 1);
}
