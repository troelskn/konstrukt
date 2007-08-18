<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class MockUserGateway
{
  public $returnValue;

  function __construct($returnValue) {
    $this->returnValue = $returnValue;
  }

  function callback($username, $password) {
    $this->call[] = Array($username, $password);
    return $this->returnValue;
  }
}

class TestOfDispatcher extends UnitTestCase
{
  function test_default_user_gateway_returns_anonymous() {
    $dispatcher = new k_Dispatcher(new MockContext());
    $user = $dispatcher->registry->identity;
    $this->assertIsA($user, 'k_Anonymous');
  }

  function test_custom_gateway_should_be_called_if_provided() {
    $gateway = new MockUserGateway('foo');
    $dispatcher = new k_Dispatcher(new MockContext());
    $dispatcher->userGateway = Array($gateway, 'callback');
    $user = $dispatcher->registry->identity;
    $this->assertEqual($user, "foo");
  }
}

simpletest_autorun(__FILE__);
