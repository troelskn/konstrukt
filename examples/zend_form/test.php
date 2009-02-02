<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleZendForm extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('ZfRegistrationForm');
  }
  function test_root_is_accessible() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertField("username", "");
    $this->assertField("password", "");
  }
  function test_submit_incomplete_form_shows_validation_failure() {
    $this->assertTrue($this->get('/'));
    $this->clickSubmit("Login");
    $this->assertText("Value is empty, but a non-empty value is required");
  }
  function test_incomplete_form_carries_values_over() {
    $this->assertTrue($this->get('/'));
    $this->setField("username", "LOREMIPSUM");
    $this->clickSubmit("Login");
    $this->assertField("username", "loremipsum");
  }
  function test_valid_form_redirects() {
    $this->assertTrue($this->get('/'));
    $this->setField("username", "LOREMIPSUM");
    $this->setField("password", "foobar");
    $this->clickSubmit("Login");
    $this->assertText("You have been registered .. or something");
  }
}