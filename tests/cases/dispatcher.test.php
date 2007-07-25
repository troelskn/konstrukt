<?php
require_once('../examples/std.inc.php');

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
    $dispatcher = new k_Dispatcher();
    $user = $dispatcher->registry->identity;
    $this->assertIsA($user, 'k_Anonymous');
  }

  function test_custom_gateway_should_be_called_if_provided() {
    $gateway = new MockUserGateway('foo');
    $dispatcher = new k_Dispatcher();
    $dispatcher->userGateway = Array($gateway, 'callback');
    $user = $dispatcher->registry->identity;
    $this->assertEqual($user, "foo");
  }
}