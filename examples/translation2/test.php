<?php
error_reporting(E_ALL ^~E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../../lib/' . PATH_SEPARATOR . get_include_path());

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
  function test_being_able_to_set_pageid() {
    $this->assertTrue($this->get('/template?lang=da'));
    $this->assertResponse(200);
    $this->assertText("Hvordan har du det?");
    $this->assertText("Hvordan har jeg det?");
  }
  function test_negotiate_language() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("How are you?");
  }
}
