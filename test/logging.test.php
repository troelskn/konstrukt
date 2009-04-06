<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';
require_once 'support/mocks.inc.php';


class TestOfLogging extends UnitTestCase {

  function test_trace_listener() {
    $debugger = new k_TestDebugListener();
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $components->setDebugger($debugger);
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($debugger->route, array('', 'foo', 'bar', 'cux'));
  }

}

class TestOfLogfileLogger extends UnitTestCase {

  function setUp() {
    if (!stream_wrapper_register('var', 'VariableStream')) {
      throw Exception("Failed to register protocol");
    }
  }

  function tearDown() {
    stream_wrapper_unregister('var');
  }

  function test_filebased_logger_writes_to_file_when_logging() {
    $GLOBALS['log'] = "";
    $debugger = new k_logging_LogDebugger('var://log');
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $components->setDebugger($debugger);
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertPattern('/\(dispatch/', $GLOBALS['log']);
  }

}

class TestOfWebDebugLogger extends UnitTestCase {

  function test_decorate_returns_response_on_html() {
    $logger = new k_logging_WebDebugger();
    $r1 = new k_HttpResponse(200);
    $r1->setContentType('text/html');
    $r2 = $logger->decorate($r1);
    $this->assertIsA($r2, 'k_HttpResponse');
  }

  function test_decorate_returns_response_on_non_html() {
    $logger = new k_logging_WebDebugger();
    $r1 = new k_HttpResponse(200);
    $r1->setContentType('application/json');
    $r2 = $logger->decorate($r1);
    $this->assertIsA($r2, 'k_HttpResponse');
  }

}

