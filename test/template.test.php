<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/mock_objects.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';

class test_MockContext {
  public $url_return_value;
  function url() {
    return $this->url_return_value;
  }
}

class TestOfTemplate extends UnitTestCase {
  function test_render_includes_file_and_returns_output() {
    $context = new test_MockContext();
    $template = new k_Template("support/hello_world.tpl.php");
    $output = $template->render($context);
    $this->assertEqual("hello world", $output);
  }

  function test_render_doesnt_output_directly() {
    $context = new test_MockContext();
    $template = new k_Template("support/hello_world.tpl.php");
    ob_start();
    $output = $template->render($context);
    $this->assertEqual("", ob_get_clean());
  }

  function test_render_binds_url() {
    $context = new test_MockContext();
    $context->url_return_value = 'lorem-ipsum';
    $template = new k_Template("support/url.tpl.php");
    $output = $template->render($context);
    $this->assertEqual($output, "lorem-ipsum");
  }

  function test_render_throw_on_file_not_found() {
    $context = new test_MockContext();
    $template = new k_Template("some/path/which/cant/possibly/exist/../or/so/i/hope");
    try {
      $template->render($context);
      $this->fail("Expected exception not thrown");
    } catch (Exception $ex) {
      $this->pass("Exception caught");
    }
  }
}
