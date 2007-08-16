<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfController extends UnitTestCase
{
  function test_controller_assigns_parent_in_constructor() {
    $context = new MockContext();
    $ctrl = new k_Controller($context);
    $this->assertReference($ctrl->context, $context);
  }

  function test_default_method_handlers_throws_501() {
    $ctrl = new k_Controller(new MockContext());

    try {
      $ctrl->GET();
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 501);
    }

    try {
      $ctrl->POST();
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 501);
    }

    try {
      $ctrl->HEAD();
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 501);
    }

    try {
      $ctrl->PUT();
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 501);
    }

    try {
      $ctrl->DELETE();
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 501);
    }
  }

  function test_find_controller_by_subspace() {
    $ctrl = new ExposedController(new MockContext());
    $ctrl->subspace = "foo";
    $next = $ctrl->findNext();
    $this->assertEqual($next, "foo");
  }

  function test_find_next_controller_from_deep_subspace() {
    $ctrl = new ExposedController(new MockContext());
    $ctrl->subspace = "foo/bar";
    $next = $ctrl->findNext();
    $this->assertEqual($next, "foo");
  }

  function test_unmapped_forward_throws_404() {
    $ctrl = new ExposedController(new MockContext());

    try {
      $ctrl->forward("foo");
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 404);
    }
  }

  function test_mapped_but_nonexistent_forward_throws_500() {
    $ctrl = new ExposedController(new MockContext());
    $ctrl->map['foo'] = 'class_doesnt_exist';

    try {
      $ctrl->forward("foo");
      $this->fail("Expected exception not caught.");
    } catch (k_http_Response $response) {
      $this->assertEqual($response->status, 500);
    }
  }

  function test_mapped_existing_forward_executes_and_returns() {
    $ctrl = new ExposedController(new MockContext());
    $ctrl->map['foo'] = 'mockcontroller';

    $next = $ctrl->forward("foo");

    $this->assertEqual($next, "MockController");
  }

  function test_handle_final_controller_with_allowed_http_method_calls_handler() {
    $ctx = new MockContext();
    $ctx->properties = Array(
      'ENV' => Array('K_HTTP_METHOD' => 'GET')
    );
    $ctrl = new MockGETController($ctx);
    $response = $ctrl->handleRequest();

    $this->assertTrue(is_string($response));
    $this->assertEqual($response, "MockGETController->GET");
  }
}

class WebTestOfRouting extends ExtendedWebTestCase
{
  function test_web_case_available() {
    if (!$this->get($this->_baseUrl.'dispatcher/')) {
      $this->fail("failed connection to : " . $this->_baseUrl.'dispatcher/');
    }
  }

  function test_request_root_renders_default() {
    $this->get($this->_baseUrl.'dispatcher/');
    $this->assertWantedPattern("/<h1>Default/i");
  }

  function test_link_to_foo_works() {
    $this->get($this->_baseUrl.'dispatcher/');
    $this->assertLink("Foo");
    $this->clickLink("Foo");
    $this->assertWantedPattern("/<h1>Controller_Foo/i");
  }

  function test_bar_show_is_wrapped_by_bar() {
    $this->get($this->_baseUrl.'dispatcher/bar');
    $this->assertWantedPattern("/Controller_Bar_Show/i");
    $this->assertWantedPattern("/Wrapped by Controller_Bar/i");
  }
}

simpletest_autorun(__FILE__);
