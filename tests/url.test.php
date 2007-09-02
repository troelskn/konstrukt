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

Application state in Konstrukt
==============================
In order to be clear about the terminology, the following supplies a word list, which tries to
explicitly define how the terms are to be understood in the context of Konstrukt.

State
-----
"the condition of a person or thing, as with respect to circumstances or attributes."
Aka Application state.
Used to describe the variables, which can be set from outside (input), but also propagated.
A component can be said to be stateful, when it has state. In respect to Konstrukt, a stateful
component is one, which utilises automatic propagation of state.
The meaning of state is a bit narrower than the general use, in that it covers propagated state only.
The other type of state -- server side state, also called session state -- is better avoided, if
possible, since it makes the application stateful. The perrils of this, is described in various
articles about REST (I should probably refer some).

Maintaining State
-----------------
Principally, application state can be maintained by one of two methods; Server side state (aka. session),
stores state by serializing data to a file, and unserializing again. It relies on client side
state to work, since it needs to identify the request, in order to unserialize the correct
data. Server side state implies a lot of problems, related to concurrency and unforseen
side effects -- It's generally best to avoid in web applications, as far as possible.

The other method -- client side state -- stores state by transferring it to the client, and
getting it back with each request. State which is maintained this way, is said to be propagated,
because it copies itself over and over. In contrast, the server side state is stored in a
single location.

Propagate
---------
"to reproduce (itself, its kind, etc.), as an organism does."
The process of transferring state between requests, usually using the URL as the vessel.
Specifically, the query-string is useful for propagating secondary (application) state, while the
path section is used for referring resources.

State can be propagated within different types of scope.

* Local Scope
  State is propagated for the curent component, and its children. This is the default way, state is propagated.

* Context Scope
  State is exported to the curent components, its children and its parent context. Context scope is a superset of
  Local scope, but a subset of Global scope.

* Global Scope
  State is propagated to all components in the view. Global is a superset of Local scope.
  While global scope is very powerful, it should be used with care. It has a high risk of nameclashing,
  since the namespace is shared among all components.

Automated propagation
---------------------
Konstrukt provides mechanisms for automatic propagation of Local and Context scope. Utilising Local scope at
the root controller level will for all practical matters equal Global scope.

...

Local Scope is a possibility by setting $state
Context Scope is possible by setting $state + $contextStateParams
Global Scope isn't directly supported, but can be archieved by setting urlState on the root controller.

Todo: Still need to protect state params by namespaces.
Encapsulate urlState in an object, and pass it in through the parent context?

$ctrl = new MyController($context, 'foo', new k_UrlState('foo'));
$state['bar'] ==> $_GET['foo-bar']

rename urlstate ==> state
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
    $ctrl = new ExposedController(new MockContext());
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
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx);
    $this->assertQuerystringEqual(Array('foo' => 'default-foo'), $ctrl->url());
    $this->assertQuerystringEqual(Array('foo' => 'default-foo'), $ctx->url());
  }

  function test_stateful_controller_propagates_changes_to_context() {
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx);
    $ctrl->setUrlState('foo', 'something-different');
    $href = $ctx->url();
    $this->assertQuerystringEqual(Array('foo' => 'something-different'), $href);
  }

  function test_stateful_controller_doesnt_propagate_local_url_state_to_context() {
    $ctx = new ExposedController(new MockContext());
    $ctx->setUrlState('bar', 42);
    $ctrl = new StatefulController($ctx);
    $ctrl->setUrlState('bar', 11);
    $href = $ctx->url();
    $this->assertQuerystringEqual(Array('bar' => '42', 'foo' => 'default-foo'), $href);
  }

  function test_stateful_namespaced_controller_uses_namespaces_to_propagate_default_state() {
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx, '', 'ns');
    $this->assertQuerystringEqual(Array('ns-foo' => 'default-foo'), $ctrl->url());
  }

  function test_stateful_namespaced_controller_uses_namespaces_to_propagate_state() {
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx, '', 'ns');
    $ctrl->setUrlState('foo', 42);
    $this->assertQuerystringEqual(Array('ns-foo' => 42), $ctrl->url());
  }

  function test_stateful_namespaced_controller_uses_namespace_to_propagate_arguments() {
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx, '', 'ns');
    $this->assertQuerystringEqual(Array('ns-foo' => 42), $ctrl->url("", Array('foo' => 42)));
  }

  function test_stateful_namespaced_controller_propagates_state_to_context() {
    $ctx = new ExposedController(new MockContext());
    $ctrl = new StatefulController($ctx, '', 'ns');
    $ctrl->setUrlState('foo', 42);
    $this->assertQuerystringEqual(Array('ns-foo' => 42), $ctx->url());
  }

  function test_two_stateful_namespaced_controller_can_coexist() {
    $ctx = new ExposedController(new MockContext());
    $a = new StatefulController($ctx, '', 'a');
    $b = new StatefulController($ctx, '', 'b');
    $a->setUrlState('foo', 42);
    $b->setUrlState('foo', 11);
    $this->assertQuerystringEqual(Array('a-foo' => 42, 'b-foo' => 11), $ctx->url());
  }

  function test_stateful_controller_reads_from_input() {
    $ctx = new ExposedController(new MockContext());
    $ctx->setUrlState('foo', 42);
    $ctrl = new StatefulController($ctx);
    $this->assertQuerystringEqual(Array('foo' => 42), $ctx->url());
  }

  function test_stateful_namespaced_controller_reads_from_input() {
    $ctx = new ExposedController(new MockContext());
    $ctx->setUrlState('ns-foo', 42);
    $ctrl = new StatefulController($ctx, '', 'ns');
    $this->assertQuerystringEqual(Array('ns-foo' => 42), $ctx->url());
  }

}

simpletest_autorun(__FILE__);
