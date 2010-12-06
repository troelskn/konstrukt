<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once 'konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfLanguageLoading extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root', null, null, new k_DefaultIdentityLoader(), new MyLanguageLoader());
  }
  function test_swedish() {
    $this->assertTrue($this->get('/?lang=sv'));
    $this->assertResponse(200);
    $this->assertText("Swedish (sv)");
  }
  function test_english() {
    $this->assertTrue($this->get('/?lang=en'));
    $this->assertResponse(200);
    $this->assertText("English (en)");
  }
  function test_fallback_on_english() {
    $this->assertTrue($this->get('/?lang=fr'));
    $this->assertResponse(200);
    $this->assertText("English (en)");
  }
}