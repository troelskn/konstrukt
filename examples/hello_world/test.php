<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleHelloWorld extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root');
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("Example 1");
  }
  function test_click_to_navigate_to_subpage() {
    $this->assertTrue($this->get('/'));
    $this->click("say hello");
    $this->assertResponse(200);
    $this->assertText("Hello World");
  }
}