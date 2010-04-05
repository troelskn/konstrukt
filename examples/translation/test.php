<?php
error_reporting(E_ALL);

// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once 'konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfLanguageLoading extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root', null, null, new k_DefaultIdentityLoader(), new MyLanguageLoader(), new SimpleTranslatorLoader());
  }
  function test_swedish() {
    $this->assertTrue($this->get('/?lang=sv'));
    $this->assertResponse(200);
    $this->assertText("Bork, bork, bork!");
    $this->assertText("Swedish meatballs");
  }
  function test_english() {
    $this->assertTrue($this->get('/?lang=en'));
    $this->assertResponse(200);
    $this->assertText("Hello");
    $this->assertText("Meatballs");
  }
  function test_fallback_on_english() {
    $this->assertTrue($this->get('/?lang=fr'));
    $this->assertResponse(200);
    $this->assertText("Hello");
    $this->assertText("Meatballs");
  }
  function test_that_global_function_works() {
    $this->assertTrue($this->get('/template?lang=sv'));
    $this->assertResponse(200);
    $this->assertText("Bork, bork, bork!");
  }
}