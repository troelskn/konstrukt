<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfRequest extends UnitTestCase
{
  protected $SERVER_BACKUP;
  protected $GET_BACKUP;
  protected $POST_BACKUP;

  function setUp() {
    $this->SERVER_BACKUP = $_SERVER;
    $this->GET_BACKUP = $_SERVER;
    $this->POST_BACKUP = $_SERVER;
    $_SERVER['SERVER_NAME'] = 'example.org';
    $_SERVER['REQUEST_URI'] = 'http://example.org';
    $_SERVER['REQUEST_METHOD'] = 'GET';
  }

  function tearDown() {
    $_SERVER = $this->SERVER_BACKUP;
    $_GET = $this->GET_BACKUP;
    $_POST = $this->POST_BACKUP;
  }

  function test_should_detect_request_method() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $this->assertEqual($registry->ENV['K_HTTP_METHOD'], 'POST');

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $this->assertEqual($registry->ENV['K_HTTP_METHOD'], 'GET');
  }

  function test_superglobal_get_parameter_should_be_in_GET() {
    $_GET['test2'] = "foo";
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $this->assertIdentical($registry->GET['test2'], "foo");
    unset($_GET['test2']);
  }

  function test_superglobal_post_parameter_should_be_in_POST() {
    $_POST['test'] = "foo";
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $this->assertIdentical($registry->POST['test'], "foo");
    unset($_POST['test']);
  }

  function test_superglobal_get_shouldnt_be_in_POST() {
    $_GET['test2'] = "foo";
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $this->assertNotIdentical(@$registry->POST['test2'], "foo");
    unset($_GET['test2']);
  }
}

class WebTestOfRequest extends ExtendedWebTestCase
{
  function test_web_case_available() {
    if (!$this->get($this->_baseUrl.'dispatcher/')) {
      $this->fail("failed connection to : " . $this->_baseUrl.'dispatcher/');
    }
  }

  function setUp() {
    $this->restart();
  }

  function test_http_method_get() {
    $this->get($this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/<h1>Controller_Foo/i");

    $this->get($this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/Last-Request-Method:GET/i");
  }

  function test_http_method_post() {
    $this->post($this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/Last-Request-Method:POST/i");
  }

  function test_http_method_put() {
    $this->request('PUT', $this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/Last-Request-Method:PUT/i");
  }

  function test_http_method_delete() {
    $this->request('DELETE', $this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/Last-Request-Method:DELETE/i");
  }

  function test_url_encoded_path_gets_decoded() {
    $raw = file_get_contents($this->_baseUrl.'dispatcher/foo/barnes%20%26%20noble');
    $this->assertTrue(preg_match("/barnes & noble/", utf8_decode($raw)));
  }

  function test_exotic_characters_in_path_gets_decoded() {
    $raw = file_get_contents($this->_baseUrl.'dispatcher/foo/' . rawurlencode('iñtërnâtiônàlizætiøn'));
    $this->assertTrue(preg_match("/iñtërnâtiônàlizætiøn/", utf8_decode($raw)));
  }

  function test_headers_may_override_request_method() {
    $this->setMaximumRedirects(1);
    $this->addHeader("Http-Method-Equivalent: DELETE");
    $this->post($this->_baseUrl.'dispatcher/foo');

    // simpletest doesn't have a way to set a header for a single request.
    // I'm a bit in doubt whether this is correct or not? A redirect might
    // require the agent to resend headers aswell ?
    $this->_browser->_user_agent->_additional_headers = Array();
    $this->get($this->_baseUrl.'dispatcher/foo');
    $this->assertWantedPattern("/Last-Request-Method:DELETE/i");
  }

  function test_www_authentication_works() {
    $this->get($this->_baseUrl.'dispatcher/restricted');

    $this->assertAuthentication('Basic');
    $this->assertResponse(401);
    $this->assertRealm('restricted');

    $this->authenticate('foo@bar', 'secret');

    $this->assertWantedPattern("/<p>You are in the clear/i");
  }
}

simpletest_autorun(__FILE__);
