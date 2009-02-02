<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleSmarty extends WebTestCase {
  function createInvoker() {
    return new SimpleInvoker($this);
  }
  function createBrowser() {
    return new k_VirtualSimpleBrowser('HelloComponent');
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("Fred Irving Johnathan Bradley Peppergill");
  }
}
