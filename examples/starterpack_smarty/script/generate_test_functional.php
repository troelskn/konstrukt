#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../config/global.inc.php');

$component_name = @$_SERVER['argv'][1];
$component_name = preg_replace('~^components_~', '', $component_name);
$class_name = "components_" . $component_name;
if (!class_exists($class_name)) {
  echo "No component '$component_name' found. Aborting.\n";
  exit -1;
}

$base_name = str_replace('_', '/', strtolower($component_name));
$dir_tests = dirname(dirname(__FILE__)) . '/test/functional';
$destination_file_name = $dir_tests . DIRECTORY_SEPARATOR . str_replace('/', '_', $base_name) . '.test.php';

if (file_exists($destination_file_name)) {
  echo "'$destination_file_name' already exists. Aborting.\n";
  exit -1;
}

$component_uc_name = implode(array_map('ucfirst', explode('_', strtolower($component_name))));


$php = '<?php
// You need to have simpletest in your include_path
if (realpath($_SERVER[\'PHP_SELF\']) == __FILE__) {
  require_once \'simpletest/autorun.php\';
}
require_once dirname(__FILE__) . \'/../../config/global.inc.php\';
require_once \'konstrukt/virtualbrowser.inc.php\';

class WebTestOf'.$component_uc_name.' extends WebTestCase {
  function createBrowser() {
    $this->container = create_container();
    return new k_VirtualSimpleBrowser(\''.$class_name.'\', new k_InjectorAdapter($this->container));
  }
  function createInvoker() {
    return new SimpleInvoker($this);
  }
  function test_get_component() {
    $this->assertTrue($this->get(\'/\'));
    $this->assertResponse(200);
  }
}
';

echo "Writing file '$destination_file_name'.\n";
file_put_contents($destination_file_name, $php);
