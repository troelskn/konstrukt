<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';
require_once 'support/mocks.inc.php';


class TestOfLogging extends UnitTestCase {

  function test_trace_listener() {
    $debugger = new k_TestDebugListener();
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), null, null, $glob);
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
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $components->setDebugger($debugger);
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertPattern('/\(dispatch/', $GLOBALS['log']);
  }

}

class test_DummyOutputAccess implements k_adapter_OutputAccess {
  public $headers = array();
  public $content = "";
  public $session_state = true;
  function header($string, $replace = true, $http_response_code = null) {
    $this->headers[]  = array($string, $replace, $http_response_code);
  }
  function write($bytes) {
    $this->content .= $bytes;
  }
  function endSession() {
    $this->session_state = false;
  }
}


class TestOfWebDebugLogger extends UnitTestCase {

  function test_decorate_returns_response_on_html() {
    $logger = new k_logging_WebDebugger();
    $r1 = new k_HtmlResponse("foo");
    $r2 = $logger->decorate($r1);
    $this->assertIsA($r2, 'k_Response');
  }

  function test_decorate_returns_response_on_non_html() {
    $logger = new k_logging_WebDebugger();
    $r1 = new k_JsonResponse("'foo'");
    $r2 = $logger->decorate($r1);
    $this->assertIsA($r2, 'k_Response');
  }

  function test_when_wrapped_in_decorator_it_should_report_the_caller() {
    $logger = new k_MultiDebugListener();
    $logger->add(new k_logging_WebDebugger());
    $logger->log(42);
    $response = $logger->decorate(new k_HtmlResponse(""));
    $capture = new test_DummyOutputAccess();
    $response->out($capture);
    $this->assertNoPattern('~konstrukt/konstrukt.*\.inc\.php~', $capture->content);
  }

}

