<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfComponent extends UnitTestCase
{
  function test_render_includes_file_and_returns_output() {
    $context = new MockContext();
    $controller = new k_Controller($context);
    $output = $controller->render("support/hello_world.tpl.php");
    $this->assertEqual("hello world", $output);
  }

  function test_render_doesnt_output_directly() {
    $context = new MockContext();
    $controller = new k_Controller($context);
    ob_start();
    $controller->render("support/hello_world.tpl.php");
    $this->assertEqual("", ob_get_clean());
  }

  function test_render_binds_url() {
    $context = new MockContext();
    $controller = new k_Controller($context, "foo");
    $output = $controller->render("support/call_url.tpl.php");
    $this->assertEqual($controller->url(), $output);
  }

  function test_render_binds_e() {
    $context = new MockContext();
    $controller = new k_Controller($context, "foo");
    $output = $controller->render("support/echo.tpl.php");
    $this->assertEqual($output, "&lt;foo/&gt;");
  }

  function test_render_throw_on_file_not_found() {
    $context = new MockContext();
    $controller = new k_Controller($context);
    try {
      $controller->render("some/path/which/cant/possibly/exist/../or/so/i/hope");
      $this->fail("Expected exception not thrown");
    } catch (Exception $ex) {
      $this->pass("Exception caught");
    }
  }
}

simpletest_autorun(__FILE__);
