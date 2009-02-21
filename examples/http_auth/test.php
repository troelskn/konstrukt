<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once 'konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleHttpAuthentication extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root', null, null, new MyIdentityLoader());
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("Authorization Example");
  }
  function test_restricted_page_prompts_for_authorization() {
    $this->assertTrue($this->get('/'));
    $this->click("restricted");
    $this->assertResponse(401);
    $this->assertAuthentication('Basic');
    $this->assertRealm('Restricted');
  }
  function test_valid_authorization_gains_access() {
    $this->assertTrue($this->get('/'));
    $this->click("restricted");
    $this->assertResponse(401);
    $this->authenticate('pirate', 'arrr');
    $this->assertResponse(200);
    $this->assertText("Hello pirate");
  }
  function test_invalid_authorization_is_forbidden_access() {
    $this->assertTrue($this->get('/'));
    $this->click("restricted");
    $this->assertResponse(401);
    $this->authenticate('pirate', 'arg');
    $this->assertResponse(401);
  }
  function test_authorized_user_without_privilege_is_forbidden_from_limited_area() {
    $this->assertTrue($this->get('/'));
    $this->click("restricted");
    $this->assertResponse(401);
    $this->authenticate('pirate', 'arrr');
    $this->assertResponse(200);
    $this->click("the dojo");
    $this->assertResponse(403);
  }
  function test_authorized_user_with_privilege_is_allowed_into_limited_area() {
    $this->assertTrue($this->get('/'));
    $this->click("restricted");
    $this->assertResponse(401);
    $this->authenticate('ninja', 'supersecret');
    $this->assertResponse(200);
    $this->click("the dojo");
    $this->assertResponse(200);
    $this->assertText("Welcome to the dojo, where only ninjas are allowed");
  }
}