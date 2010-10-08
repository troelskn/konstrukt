<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(__DIR__ . PATH_SEPARATOR . __DIR__ . '/../../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleForm extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('RegistrationForm');
  }
  function test_root_is_accessible() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("Registration Form");
  }
  function test_submit_incomplete_form_shows_validation_failure() {
    $this->assertTrue($this->get('/'));
    $this->clickSubmit("Register");
    $this->assertText("You must enter your first name");
  }
  function test_incomplete_form_carries_values_over() {
    $this->assertTrue($this->get('/'));
    $this->setField("first_name", "John");
    $this->clickSubmit("Register");
    $this->assertField("first_name", "John");
  }
}