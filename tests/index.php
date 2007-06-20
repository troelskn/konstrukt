<?php
set_include_path('.');
require_once('env.php');
TestEnv::Load(dirname(__FILE__).'/config/default.php');
TestEnv::Load(dirname(__FILE__).'/config/user.php');

require_once(TestEnv::Get('path_simpletest').'unit_tester.php');
require_once(TestEnv::Get('path_simpletest').'web_tester.php');
require_once(TestEnv::Get('path_simpletest').'reporter.php');
require_once(TestEnv::Get('path_simpletest').'unit_tester.php');
require_once(TestEnv::Get('path_simpletest').'mock_objects.php');

require_once('support.php');

foreach (TestEnv::Get('dependencies') as $filename) {
  require_once($filename);
}
error_reporting(E_ALL);
if (isset($_GET['test'])) {
  $test = new GroupTest($_GET['test']);
  $test->addTestFile(TestEnv::Get('test_case_dir').$_GET['test']);
} else {
  $test = new GroupTest(TestEnv::Get('title'));
  foreach (TestEnv::Discover() as $filename) {
      $test->addTestFile($filename);
  }
}

if (SimpleReporter::inCli()) {
  exit($test->run(new TextReporter()) ? 0 : 1);
}

echo "<p>";
  echo "<a href='?'>*</a>";
foreach (TestEnv::Discover() as $shortname => $filename) {
  echo " | <a href='?test=".rawurlencode($shortname)."'>".$shortname."</a>";
}
echo "</p>";
error_reporting(E_ALL);
$test->run(new HtmlReporter());
?>