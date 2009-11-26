<?php
error_reporting(0);
require_once 'simpletest/autorun.php';
require_once 'konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfLanguageLoading extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root', null, null, new k_DefaultIdentityLoader(), new MyLanguageLoader(), new SimpleTranslatorLoader());
  }
  function test_danish() {
    $this->assertTrue($this->get('/?lang=da'));
    $this->assertResponse(200);
    $this->assertText("Hvordan har du det?");
  }
  function test_fallback_to_danish() {
    $this->assertTrue($this->get('/?lang=gr'));
    $this->assertResponse(200);
    $this->assertText("Hvordan har du det?");
  }
  function test_that_global_function_works() {
    $this->assertTrue($this->get('/template?lang=da'));
    $this->assertResponse(200);
    $this->assertText("Hvordan har du det?");
  }
  function test_negotiate_language() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("How are you?");
  }
}