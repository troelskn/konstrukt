<?php
require_once('../examples/std.inc.php');

class TestOfUrl extends UnitTestCase
{
  function getMockHttpRequestObject() {
    $request = new k_http_Request();
    $registry = $request->getRegistry();
    $registry->ENV['K_URL_BASE'] = "test://example.org/";
    return $request;
  }

  function test_request_generates_toplevel_url() {
    $request = $this->getMockHttpRequestObject();
    $this->assertEqual("test://example.org/", $request->url());
  }

  function test_controller_builds_url_from_name() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    $this->assertEqual("test://example.org/foo", $ctrl->url());
  }

  function test_nested_controllers_builds_url_from_names() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    $subctrl = new k_Controller($ctrl, 'bar');
    $this->assertEqual("test://example.org/foo/bar", $subctrl->url());
  }

  function test_relative_link_from_nested_controllers() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    $subctrl = new k_Controller($ctrl, 'bar');
    $this->assertEqual("test://example.org/foo", $subctrl->url(".."));
  }

  function test_relative_link_below_root_throws() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    try {
      $ctrl->url("../..");
      $this->fail("Expected exception not caught.");
    } catch (Exception $ex) {
      $this->assertEqual($ex->getMessage(), "Illegal path. Relative level extends below root.");
    }
  }

  function test_toplevel_link_from_nested_controllers() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    $subctrl = new k_Controller($ctrl, 'bar');
    $this->assertEqual("test://example.org/", $subctrl->url("/"));
  }

  function test_queryparams_are_appended() {
    $request = $this->getMockHttpRequestObject();
    $this->assertEqual("test://example.org/?foo=42", $request->url(null, Array('foo' => 42)));
  }

  function test_queryparams_are_urlencoded() {
    $request = $this->getMockHttpRequestObject();
    $this->assertEqual("test://example.org/?foo=%3F%3D%26%25", $request->url(null, Array('foo' => '?=&%')));
  }

  function test_queryparams_are_passed_through_nested_controllers() {
    $ctrl = new k_Controller($this->getMockHttpRequestObject(), 'foo');
    $subctrl = new k_Controller($ctrl, 'bar');
    $this->assertEqual("test://example.org/foo/bar?foo=42", $subctrl->url(null, Array('foo' => 42)));
  }
}