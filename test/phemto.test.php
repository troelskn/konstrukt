<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(__DIR__ . PATH_SEPARATOR . __DIR__ . '/../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';
// You need to have phemto in your include_path
require_once 'phemto/phemto.php';

class test_BasicComponent extends k_Component {}

interface test_Thingy {
  function callHome();
}

class test_ConcreteThingy implements test_Thingy {
  function callHome() {}
}

class test_DependingComponent extends k_Component {
  function __construct(test_Thingy $thingy) {
    $thingy->callHome();
  }
}

class TestOfPhemtoAdapter extends UnitTestCase {

  protected function makeHttp() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    return new k_HttpRequest('', '/foo/bar', new k_DefaultIdentityLoader(), null, null, $glob);
  }

  function test_can_create_simple_component_with_default_container() {
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_BasicComponent', $this->makeHttp());
    $this->assertIsA($root, 'test_BasicComponent');
  }

  function test_can_create_simple_component_with_phemto_container() {
    $injector = new Phemto();
    $components = new k_InjectorAdapter($injector);
    $root = $components->create('test_BasicComponent', $this->makeHttp());
    $this->assertIsA($root, 'test_BasicComponent');
  }

  function test_can_create_complex_component_with_phemto_container() {
    $injector = new Phemto();
    $components = new k_InjectorAdapter($injector);
    $root = $components->create('test_DependingComponent', $this->makeHttp());
    $this->assertIsA($root, 'test_DependingComponent');
  }

}
