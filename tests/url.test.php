<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfUrl extends UnitTestCase
{
  function getMockHttpRequestObject() {
    return new MockContext();
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

/*
(draft documentation aka notes to self)

Terminology on state
====================
In order to be clear about the terminology, the following supplies a word list, which tries to
explicitly define how the terms are to be understood in the context of Konstrukt.

State
-----
"the condition of a person or thing, as with respect to circumstances or attributes."
Used to describe the variables, which can be set from outside (input), but also propagated.
A component can be said to be stateful, when it has state. In respect to Konstrukt, a stateful
component is one, which utilises automatic propagation of state.
The meaning of state is a bit narrower than the general use, in that it covers propagated state only.
The other type of state -- server side state, also called session state -- is better avoided, if
possible, since it makes the application stateful. The perrils of this, is described in various
articles about REST (I should probably refer some).

Propagate
---------
"to reproduce (itself, its kind, etc.), as an organism does."
The process of transferring state between requests, using the URL as vessel.
Also called client side state, or propagated state.

URL State
---------
State, which is propagated over the query-string (GET-parameters). In Konstrukt, this implies
automatic propagation. It is possible to propagate state over URL, manually, but this isn't
what is meant, when using the terms described here.
There are two different scopes of URL state, determining how they are propagated -- Or rather, *where*
they are propagated.

* Local Scope
  State is propagated for this component, and its children. This is the default way, state is propagated.

* Global Scope
  State is propagated to all components in the view. Global is a superset of Local scope.
  While global scope is very powerful, it should be used with care. It has a high risk of nameclashing,
  since the namespace is shared among all components.

*/

class TestOfViewstate extends UnitTestCase
{
  function assertQuerystringEqual($arr, $href) {
    $tmp = Array();
    parse_str(parse_url($href, PHP_URL_QUERY), $tmp);
    ksort($arr);
    ksort($tmp);
    $this->assertEqual($arr, $tmp);
  }

  function test_set_url_state() {
    $ctrl = new k_Controller(new MockContext());
    $ctrl->setUrlState('foo', 42);
    $href = $ctrl->url();
    $this->assertQuerystringEqual(Array('foo' => '42'), $href);
  }

  function test_stateful_controller_initialises_with_default_value() {
    $ctrl = new StatefulController(new MockContext());
    $href = $ctrl->url();
    $this->assertQuerystringEqual(Array('foo' => 'default-foo'), $href);
  }

  function test_stateful_controller_allows_change_of_initial_value() {
    $ctrl = new StatefulController(new MockContext());
    $ctrl->setUrlState('foo', 42);
    $href = $ctrl->url();
    $this->assertQuerystringEqual(Array('foo' => '42'), $href);
  }

  function test_stateful_controller_propagates_initial_value_to_context() {
    $ctx = new k_Controller(new MockContext());
    $ctx->setUrlState('foo', 42);
    $ctrl = new StatefulController($ctx);
    $href = $ctrl->url();
    $this->assertQuerystringEqual(Array('foo' => 'default-foo'), $href);
  }

  function test_stateful_controller_propagates_changes_to_context() {
    $ctx = new k_Controller(new MockContext());
    $ctrl = new StatefulController($ctx);
    $ctx->setUrlState('foo', 'something-different');
    $href = $ctrl->url();
    $this->assertQuerystringEqual(Array('foo' => 'something-different'), $href);
  }

  function test_stateful_controller_doesnt_propagate_local_url_state_to_context() {
    $ctx = new k_Controller(new MockContext());
    $ctx->setUrlState('bar', 42);
    $ctrl = new StatefulController($ctx);
    $ctrl->setUrlState('bar', 11);
    $href = $ctx->url();
    $this->assertQuerystringEqual(
      Array('bar' => '42', 'foo' => 'default-foo'),
      $href);
  }
}

simpletest_autorun(__FILE__);
